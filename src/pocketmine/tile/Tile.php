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

/**
 * All the Tile classes and related classes
 */
namespace pocketmine\tile;

use pocketmine\block\Block;
use pocketmine\event\Timings;
use pocketmine\event\TimingsHandler;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;

abstract class Tile extends Position{

	const BREWING_STAND = "BrewingStand";
	const CHEST = "Chest";
	const ENCHANT_TABLE = "EnchantTable";
	const FLOWER_POT = "FlowerPot";
	const FURNACE = "Furnace";
	const ITEM_FRAME = "ItemFrame";
	const MOB_SPAWNER = "MobSpawner";
	const SIGN = "Sign";
	const SKULL = "Skull";
	const BED = "Bed";
	const DISPENSER = "Dispenser";
	const DROPPER = "Dropper";
	const CAULDRON = "Cauldron";
	const HOPPER = "Hopper";
	const BEACON = "Beacon";
	const ENDER_CHEST = "EnderChest";

	public static $tileCount = 1;

	private static $knownTiles = [];
	private static $shortNames = [];

	/** @var Chunk */
	public $chunk;
	public $name;
	public $id;
	public $attach;
	public $metadata;
	public $closed = false;
	public $namedtag;
	protected $lastUpdate;
	protected $server;
	protected $timings;

	/** @var TimingsHandler */
	public $tickTimer;

	public static function init(){
		self::registerTile(Bed::class);
		self::registerTile(Chest::class);
		self::registerTile(EnchantTable::class);
		self::registerTile(FlowerPot::class);
		self::registerTile(Furnace::class);
		self::registerTile(ItemFrame::class);
		self::registerTile(Sign::class);
		self::registerTile(Skull::class);
		self::registerTile(Hopper::class);
		self::registerTile(Beacon::class);
		self::registerTile(EnderChest::class);
	}

	/**
	 * @param string      $type
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 * @param             $args
	 *
	 * @return Tile|null
	 */
	public static function createTile($type, Level $level, CompoundTag $nbt, ...$args){
		if(isset(self::$knownTiles[$type])){
			$class = self::$knownTiles[$type];
			return new $class($level, $nbt, ...$args);
		}

		return null;
	}

	/**
	 * @param $className
	 *
	 * @return bool
	 */
	public static function registerTile($className) : bool{
		$class = new \ReflectionClass($className);
		if(is_a($className, Tile::class, true) and !$class->isAbstract()){
			self::$knownTiles[$class->getShortName()] = $className;
			self::$shortNames[$className] = $class->getShortName();
			return true;
		}

		return false;
	}

	/**
	 * Returns the short save name
	 * @return string
	 */
	public function getSaveId() : string{
		return self::$shortNames[static::class];
	}

	public function __construct(Level $level, CompoundTag $nbt){
		$this->timings = Timings::getTileEntityTimings($this);

		$this->namedtag = $nbt;
		$this->server = $level->getServer();
		$this->setLevel($level);
		$this->chunk = $level->getChunk($this->namedtag->x->getValue() >> 4, $this->namedtag->z->getValue() >> 4, false);
		assert($this->chunk !== null);

		$this->name = "";
		$this->lastUpdate = microtime(true);
		$this->id = Tile::$tileCount++;
		$this->x = $this->namedtag->x->getValue();
		$this->y = $this->namedtag->y->getValue();
		$this->z = $this->namedtag->z->getValue();

		$this->chunk->addTile($this);
		$this->getLevel()->addTile($this);
		$this->tickTimer = Timings::getTileEntityTimings($this);
	}

	public function getId(){
		return $this->id;
	}

	public function saveNBT(){
		$this->namedtag->id->setValue($this->getSaveId());
		$this->namedtag->x->setValue($this->x);
		$this->namedtag->y->setValue($this->y);
		$this->namedtag->z->setValue($this->z);
	}

	public function getCleanedNBT(){
		$this->saveNBT();
		$tag = clone $this->namedtag;
		unset($tag->x, $tag->y, $tag->z, $tag->id);
		if($tag->getCount() > 0){
			return $tag;
		}else{
			return null;
		}
	}

	/**
	 * @return Block
	 */
	public function getBlock() : Block{
		return $this->level->getBlock($this);
	}

	/**
	 * @return bool
	 */
	public function onUpdate() : bool{
		return false;
	}

	final public function scheduleUpdate(){
		$this->level->updateTiles[$this->id] = $this;
	}

	public function __destruct(){
		$this->close();
	}

	public function close(){
		if(!$this->closed){
			$this->closed = true;
			unset($this->level->updateTiles[$this->id]);
			if($this->chunk instanceof Chunk){
				$this->chunk->removeTile($this);
				$this->chunk = null;
			}
			if(($level = $this->getLevel()) instanceof Level){
				$level->removeTile($this);
				$this->setLevel(null);
			}

			$this->namedtag = null;
		}
	}

	public function getName(){
		return $this->name;
	}

}
