<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\level\format\io\leveldb;

use pocketmine\entity\Entity;
use pocketmine\level\format\Chunk;
use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\level\format\io\ChunkUtils;
use pocketmine\level\format\io\exception\UnsupportedChunkFormatException;
use pocketmine\level\format\SubChunk;
use pocketmine\level\generator\Flat;
use pocketmine\level\generator\Generator;
use pocketmine\level\Level;
use pocketmine\level\LevelException;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\{
	ByteTag, CompoundTag, FloatTag, IntTag, LongTag, StringTag
};
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\tile\Tile;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\MainLogger;

class LevelDB extends BaseLevelProvider{

	//According to Tomasso, these aren't supposed to be readable anymore. Thankfully he didn't change the readable ones...
	const TAG_DATA_2D = "\x2d";
	const TAG_DATA_2D_LEGACY = "\x2e";
	const TAG_SUBCHUNK_PREFIX = "\x2f";
	const TAG_LEGACY_TERRAIN = "0";
	const TAG_BLOCK_ENTITY = "1";
	const TAG_ENTITY = "2";
	const TAG_PENDING_TICK = "3";
	const TAG_BLOCK_EXTRA_DATA = "4";
	const TAG_BIOME_STATE = "5";
	const TAG_STATE_FINALISATION = "6";

	const FINALISATION_NEEDS_INSTATICKING = 0;
	const FINALISATION_NEEDS_POPULATION = 1;
	const FINALISATION_DONE = 2;

	const TAG_VERSION = "v";

	const ENTRY_FLAT_WORLD_LAYERS = "game_flatworldlayers";

	const GENERATOR_LIMITED = 0;
	const GENERATOR_INFINITE = 1;
	const GENERATOR_FLAT = 2;

	const CURRENT_STORAGE_VERSION = 5; //Current MCPE level format version
	const CURRENT_LEVEL_CHUNK_VERSION = 4;
	const CURRENT_LEVEL_SUBCHUNK_VERSION = 0;

	/** @var Chunk[] */
	protected $chunks = [];

	/** @var \LevelDB */
	protected $db;

	public function __construct(Level $level, string $path){
		$this->level = $level;
		$this->path = $path;
		if(!file_exists($this->path)){
			mkdir($this->path, 0777, true);
		}
		$nbt = new NBT(NBT::LITTLE_ENDIAN);
		$nbt->read(substr(file_get_contents($this->getPath() . "level.dat"), 8));
		$levelData = $nbt->getData();
		if($levelData instanceof CompoundTag){
			$this->levelData = $levelData;
		}else{
			throw new LevelException("Invalid level.dat");
		}

		$this->db = new \LevelDB($this->path . "/db", [
			"compression" => LEVELDB_ZLIB_COMPRESSION
		]);

		if(isset($this->levelData->StorageVersion) and $this->levelData->StorageVersion->getValue() > self::CURRENT_STORAGE_VERSION){
			throw new LevelException("Specified LevelDB world format version is newer than the version supported by the server");
		}

		if(!isset($this->levelData->generatorName)){
			if(isset($this->levelData->Generator)){
				switch((int) $this->levelData->Generator->getValue()){ //Detect correct generator from MCPE data
					case self::GENERATOR_FLAT:
						$this->levelData->generatorName = new StringTag("generatorName", (string) Generator::getGenerator("FLAT"));
						if(($layers = $this->db->get(self::ENTRY_FLAT_WORLD_LAYERS)) !== false){ //Detect existing custom flat layers
							$layers = trim($layers, "[]");
						}else{
							$layers = "7,3,3,2";
						}
						$this->levelData->generatorOptions = new StringTag("generatorOptions", "2;" . $layers . ";1");
						break;
					case self::GENERATOR_INFINITE:
						//TODO: add a null generator which does not generate missing chunks (to allow importing back to MCPE and generating more normal terrain without PocketMine messing things up)
						$this->levelData->generatorName = new StringTag("generatorName", (string) Generator::getGenerator("DEFAULT"));
						$this->levelData->generatorOptions = new StringTag("generatorOptions", "");
						break;
					case self::GENERATOR_LIMITED:
						throw new LevelException("Limited worlds are not currently supported");
					default:
						throw new LevelException("Unknown LevelDB world format type, this level cannot be loaded");
				}
			}else{
				$this->levelData->generatorName = new StringTag("generatorName", (string) Generator::getGenerator("DEFAULT"));
			}
		}

		if(!isset($this->levelData->generatorOptions)){
			$this->levelData->generatorOptions = new StringTag("generatorOptions", "");
		}
	}

	public static function getProviderName() : string{
		return "leveldb";
	}

	public function getWorldHeight() : int{
		return 256;
	}

	public static function isValid(string $path) : bool{
		return file_exists($path . "/level.dat") and is_dir($path . "/db/");
	}

	public static function generate(string $path, string $name, int $seed, string $generator, array $options = []){
		if(!file_exists($path)){
			mkdir($path, 0777, true);
		}

		if(!file_exists($path . "/db")){
			mkdir($path . "/db", 0777, true);
		}

		switch($generator){
			case Flat::class:
				$generatorType = self::GENERATOR_FLAT;
				break;
			default:
				$generatorType = self::GENERATOR_INFINITE;
			//TODO: add support for limited worlds
		}

		$levelData = new CompoundTag("", [
			//Vanilla fields
			new IntTag("DayCycleStopTime", -1),
			new IntTag("Difficulty", 2),
			new ByteTag("ForceGameType", 0),
			new IntTag("GameType", 0),
			new IntTag("Generator", $generatorType),
			new LongTag("LastPlayed", time()),
			new StringTag("LevelName", $name),
			new IntTag("NetworkVersion", ProtocolInfo::CURRENT_PROTOCOL),
			//new IntTag("Platform", 2), //TODO: find out what the possible values are for
			new LongTag("RandomSeed", $seed),
			new IntTag("SpawnX", 0),
			new IntTag("SpawnY", 32767),
			new IntTag("SpawnZ", 0),
			new IntTag("StorageVersion", self::CURRENT_STORAGE_VERSION),
			new LongTag("Time", 0),
			new ByteTag("eduLevel", 0),
			new ByteTag("falldamage", 1),
			new ByteTag("firedamage", 1),
			new ByteTag("hasBeenLoadedInCreative", 1), //badly named, this actually determines whether achievements can be earned in this world...
			new ByteTag("immutableWorld", 0),
			new FloatTag("lightningLevel", 0.0),
			new IntTag("lightningTime", 0),
			new ByteTag("pvp", 1),
			new FloatTag("rainLevel", 0.0),
			new IntTag("rainTime", 0),
			new ByteTag("spawnMobs", 1),
			new ByteTag("texturePacksRequired", 0), //TODO

			//Additional PocketMine-MP fields
			new CompoundTag("GameRules", []),
			new ByteTag("hardcore", 0),
			new StringTag("generatorName", Generator::getGeneratorName($generator)),
			new StringTag("generatorOptions", $options["preset"] ?? "")
		]);

		$nbt = new NBT(NBT::LITTLE_ENDIAN);
		$nbt->setData($levelData);
		$buffer = $nbt->write();
		file_put_contents($path . "level.dat", Binary::writeLInt(self::CURRENT_STORAGE_VERSION) . Binary::writeLInt(strlen($buffer)) . $buffer);


		$db = new \LevelDB($path . "/db", [
			"compression" => LEVELDB_ZLIB_COMPRESSION
		]);

		if($generatorType === self::GENERATOR_FLAT and isset($options["preset"])){
			$layers = explode(";", $options["preset"])[1] ?? "";
			if($layers !== ""){
				$out = "[";
				foreach(Flat::parseLayers($layers) as $result){
					$out .= $result[0] . ","; //only id, meta will unfortunately not survive :(
				}
				$out = rtrim($out, ",") . "]"; //remove trailing comma
				$db->put(self::ENTRY_FLAT_WORLD_LAYERS, $out); //Add vanilla flatworld layers to allow terrain generation by MCPE to continue seamlessly
			}
		}

		$db->close();

	}

	public function saveLevelData(){
		$this->levelData->NetworkVersion = new IntTag("NetworkVersion", ProtocolInfo::CURRENT_PROTOCOL);
		$this->levelData->StorageVersion = new IntTag("StorageVersion", self::CURRENT_STORAGE_VERSION);

		$nbt = new NBT(NBT::LITTLE_ENDIAN);
		$nbt->setData($this->levelData);
		$buffer = $nbt->write();
		file_put_contents($this->getPath() . "level.dat", Binary::writeLInt(self::CURRENT_STORAGE_VERSION) . Binary::writeLInt(strlen($buffer)) . $buffer);
	}

	public function unloadChunks(){
		foreach($this->chunks as $chunk){
			$this->unloadChunk($chunk->getX(), $chunk->getZ(), false);
		}
		$this->chunks = [];
	}

	public function getGenerator() : string{
		return (string) $this->levelData["generatorName"];
	}

	public function getGeneratorOptions() : array{
		return ["preset" => $this->levelData["generatorOptions"]];
	}

	public function getLoadedChunks() : array{
		return $this->chunks;
	}

	public function isChunkLoaded(int $x, int $z) : bool{
		return isset($this->chunks[Level::chunkHash($x, $z)]);
	}

	public function saveChunks(){
		foreach($this->chunks as $chunk){
			$this->saveChunk($chunk->getX(), $chunk->getZ());
		}
	}

	public function loadChunk(int $chunkX, int $chunkZ, bool $create = false) : bool{
		if(isset($this->chunks[$index = Level::chunkHash($chunkX, $chunkZ)])){
			return true;
		}

		$this->level->timings->syncChunkLoadDataTimer->startTiming();
		$chunk = $this->readChunk($chunkX, $chunkZ);
		if($chunk === null and $create){
			$chunk = Chunk::getEmptyChunk($chunkX, $chunkZ);
		}
		$this->level->timings->syncChunkLoadDataTimer->stopTiming();

		if($chunk !== null){
			$this->chunks[$index] = $chunk;

			return true;
		}else{
			return false;
		}
	}

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return Chunk|null
	 */
	private function readChunk($chunkX, $chunkZ){
		$index = LevelDB::chunkIndex($chunkX, $chunkZ);

		if(!$this->chunkExists($chunkX, $chunkZ)){
			return null;
		}

		try{
			/** @var SubChunk[] $subChunks */
			$subChunks = [];

			/** @var bool $lightPopulated */
			$lightPopulated = true;

			$chunkVersion = ord($this->db->get($index . self::TAG_VERSION));

			$binaryStream = new BinaryStream();

			switch($chunkVersion){
				case 4: //MCPE 1.1
					//TODO: check beds
				case 3: //MCPE 1.0
					for($y = 0; $y < Chunk::MAX_SUBCHUNKS; ++$y){
						if(($data = $this->db->get($index . self::TAG_SUBCHUNK_PREFIX . chr($y))) === false){
							continue;
						}

						$binaryStream->setBuffer($data, 0);
						$subChunkVersion = $binaryStream->getByte();

						switch($subChunkVersion){
							case 0:
								$blocks = $binaryStream->get(4096);
								$blockData = $binaryStream->get(2048);
								if($chunkVersion < 4){
									$blockSkyLight = $binaryStream->get(2048);
									$blockLight = $binaryStream->get(2048);
								}else{
									//Mojang didn't bother changing the subchunk version when they stopped saving sky light -_-
									$blockSkyLight = "";
									$blockLight = "";
									$lightPopulated = false;
								}

								$subChunks[$y] = new SubChunk($blocks, $blockData, $blockSkyLight, $blockLight);
								break;
							default:
								throw new UnsupportedChunkFormatException("don't know how to decode LevelDB subchunk format version $subChunkVersion");
						}
					}

					$binaryStream->setBuffer($this->db->get($index . self::TAG_DATA_2D), 0);

					$heightMap = array_values(unpack("v*", $binaryStream->get(512)));
					$biomeIds = $binaryStream->get(256);
					break;
				case 2: // < MCPE 1.0
					$binaryStream->setBuffer($this->db->get($index . self::TAG_LEGACY_TERRAIN));
					$fullIds = $binaryStream->get(32768);
					$fullData = $binaryStream->get(16384);
					$fullSkyLight = $binaryStream->get(16384);
					$fullBlockLight = $binaryStream->get(16384);

					for($yy = 0; $yy < 8; ++$yy){
						$subOffset = ($yy << 4);
						$ids = "";
						for($i = 0; $i < 256; ++$i){
							$ids .= substr($fullIds, $subOffset, 16);
							$subOffset += 128;
						}
						$data = "";
						$subOffset = ($yy << 3);
						for($i = 0; $i < 256; ++$i){
							$data .= substr($fullData, $subOffset, 8);
							$subOffset += 64;
						}
						$skyLight = "";
						$subOffset = ($yy << 3);
						for($i = 0; $i < 256; ++$i){
							$skyLight .= substr($fullSkyLight, $subOffset, 8);
							$subOffset += 64;
						}
						$blockLight = "";
						$subOffset = ($yy << 3);
						for($i = 0; $i < 256; ++$i){
							$blockLight .= substr($fullBlockLight, $subOffset, 8);
							$subOffset += 64;
						}
						$subChunks[$yy] = new SubChunk($ids, $data, $skyLight, $blockLight);
					}

					$heightMap = array_values(unpack("C*", $binaryStream->get(256)));
					$biomeIds = ChunkUtils::convertBiomeColors(array_values(unpack("N*", $binaryStream->get(1024))));
					break;
				default:
					throw new UnsupportedChunkFormatException("don't know how to decode chunk format version $chunkVersion");
			}

			$nbt = new NBT(NBT::LITTLE_ENDIAN);

			$entities = [];
			if(($entityData = $this->db->get($index . self::TAG_ENTITY)) !== false and strlen($entityData) > 0){
				$nbt->read($entityData, true);
				$entities = $nbt->getData();
				if(!is_array($entities)){
					$entities = [$entities];
				}
			}

			foreach($entities as $entityNBT){
				if($entityNBT->id instanceof IntTag){
					$entityNBT["id"] &= 0xff;
				}
			}

			$tiles = [];
			if(($tileData = $this->db->get($index . self::TAG_BLOCK_ENTITY)) !== false and strlen($tileData) > 0){
				$nbt->read($tileData, true);
				$tiles = $nbt->getData();
				if(!is_array($tiles)){
					$tiles = [$tiles];
				}
			}

			$extraData = [];
			if(($extraRawData = $this->db->get($index . self::TAG_BLOCK_EXTRA_DATA)) !== false and strlen($extraRawData) > 0){
				$binaryStream->setBuffer($extraRawData, 0);
				$count = $binaryStream->getLInt();
				for($i = 0; $i < $count; ++$i){
					$key = $binaryStream->getLInt();
					$value = $binaryStream->getLShort();
					$extraData[$key] = $value;
				}
			}

			$chunk = new Chunk(
				$chunkX,
				$chunkZ,
				$subChunks,
				$entities,
				$tiles,
				$biomeIds,
				$heightMap,
				$extraData
			);

			//TODO: tile ticks, biome states (?)

			$chunk->setGenerated(true);
			$chunk->setPopulated(true);
			$chunk->setLightPopulated($lightPopulated);

			return $chunk;
		}catch(UnsupportedChunkFormatException $e){
			//TODO: set chunks read-only so the version on disk doesn't get overwritten

			$logger = MainLogger::getLogger();
			$logger->error("Failed to decode LevelDB chunk: " . $e->getMessage());

			return null;
		}catch(\Throwable $t){
			$logger = MainLogger::getLogger();
			$logger->error("LevelDB chunk decode error");
			$logger->logException($t);

			return null;

		}
	}

	private function writeChunk(Chunk $chunk){
		$index = LevelDB::chunkIndex($chunk->getX(), $chunk->getZ());
		$this->db->put($index . self::TAG_VERSION, chr(self::CURRENT_LEVEL_CHUNK_VERSION));

		$subChunks = $chunk->getSubChunks();
		foreach($subChunks as $y => $subChunk){
			$key = $index . self::TAG_SUBCHUNK_PREFIX . chr($y);
			if($subChunk->isEmpty(false)){ //MCPE doesn't save light anymore as of 1.1
				$this->db->delete($key);
			}else{
				$this->db->put($key,
					chr(self::CURRENT_LEVEL_SUBCHUNK_VERSION) .
					$subChunks[$y]->getBlockIdArray() .
					$subChunks[$y]->getBlockDataArray()
				);
			}
		}

		$this->db->put($index . self::TAG_DATA_2D, pack("v*", ...$chunk->getHeightMapArray()) . $chunk->getBiomeIdArray());

		$extraData = $chunk->getBlockExtraDataArray();
		if(count($extraData) > 0){
			$stream = new BinaryStream();
			$stream->putLInt(count($extraData));
			foreach($extraData as $key => $value){
				$stream->putLInt($key);
				$stream->putLShort($value);
			}

			$this->db->put($index . self::TAG_BLOCK_EXTRA_DATA, $stream->getBuffer());
		}else{
			$this->db->delete($index . self::TAG_BLOCK_EXTRA_DATA);
		}

		//TODO: use this properly
		$this->db->put($index . self::TAG_STATE_FINALISATION, chr(self::FINALISATION_DONE));

		$this->writeTags($chunk->getTiles(), $index . self::TAG_BLOCK_ENTITY);
		$this->writeTags($chunk->getEntities(), $index . self::TAG_ENTITY);

		$this->db->delete($index . self::TAG_DATA_2D_LEGACY);
		$this->db->delete($index . self::TAG_LEGACY_TERRAIN);
	}

	/**
	 * @param Entity[]|Tile[] $targets
	 * @param string          $index
	 */
	private function writeTags(array $targets, string $index){
		$nbt = new NBT(NBT::LITTLE_ENDIAN);
		$out = [];
		foreach($targets as $target){
			if(!$target->closed){
				$target->saveNBT();
				$out[] = $target->namedtag;
			}
		}

		if(!empty($targets)){
			$nbt->setData($out);
			$this->db->put($index, $nbt->write());
		}else{
			$this->db->delete($index);
		}
	}

	public function unloadChunk(int $x, int $z, bool $safe = true) : bool{
		$chunk = $this->chunks[$index = Level::chunkHash($x, $z)] ?? null;
		if($chunk instanceof Chunk and $chunk->unload($safe)){
			unset($this->chunks[$index]);

			return true;
		}

		return false;
	}

	public function saveChunk(int $chunkX, int $chunkZ) : bool{
		if($this->isChunkLoaded($chunkX, $chunkZ)){
			$chunk = $this->getChunk($chunkX, $chunkZ);
			if(!$chunk->isGenerated()){
				throw new \InvalidStateException("Cannot save un-generated chunk");
			}
			$this->writeChunk($chunk);

			return true;
		}

		return false;
	}

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 * @param bool $create
	 *
	 * @return Chunk|null
	 */
	public function getChunk(int $chunkX, int $chunkZ, bool $create = false){
		$index = Level::chunkHash($chunkX, $chunkZ);
		if(isset($this->chunks[$index])){
			return $this->chunks[$index];
		}else{
			$this->loadChunk($chunkX, $chunkZ, $create);

			return $this->chunks[$index] ?? null;
		}
	}

	/**
	 * @return \LevelDB
	 */
	public function getDatabase() : \LevelDB{
		return $this->db;
	}

	public function setChunk(int $chunkX, int $chunkZ, Chunk $chunk){
		$chunk->setX($chunkX);
		$chunk->setZ($chunkZ);

		if(isset($this->chunks[$index = Level::chunkHash($chunkX, $chunkZ)]) and $this->chunks[$index] !== $chunk){
			$this->unloadChunk($chunkX, $chunkZ, false);
		}

		$this->chunks[$index] = $chunk;
	}

	public static function chunkIndex(int $chunkX, int $chunkZ) : string{
		return Binary::writeLInt($chunkX) . Binary::writeLInt($chunkZ);
	}

	private function chunkExists(int $chunkX, int $chunkZ) : bool{
		return $this->db->get(LevelDB::chunkIndex($chunkX, $chunkZ) . self::TAG_VERSION) !== false;
	}

	public function isChunkGenerated(int $chunkX, int $chunkZ) : bool{
		if($this->chunkExists($chunkX, $chunkZ) and ($chunk = $this->getChunk($chunkX, $chunkZ, false)) !== null){
			return true;
		}

		return false;
	}

	public function isChunkPopulated(int $chunkX, int $chunkZ) : bool{
		$chunk = $this->getChunk($chunkX, $chunkZ);
		if($chunk instanceof Chunk){
			return $chunk->isPopulated();
		}else{
			return false;
		}
	}

	public function close(){
		$this->unloadChunks();
		$this->db->close();
		$this->level = null;
	}
}