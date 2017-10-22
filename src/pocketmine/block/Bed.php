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

namespace pocketmine\block;

use pocketmine\event\TranslationContainer;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Bed as TileBed;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;

class Bed extends Transparent{
	const BITFLAG_OCCUPIED = 0x04;
	const BITFLAG_HEAD = 0x08;

	protected $id = self::BED_BLOCK;

	protected $itemId = Item::BED;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getHardness() : float{
		return 0.2;
	}

	public function getName() : string{
		return "Bed Block";
	}

	protected function recalculateBoundingBox(){
		return new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x + 1,
			$this->y + 0.5625,
			$this->z + 1
		);
	}

	public function isHeadPart() : bool{
		return ($this->meta & self::BITFLAG_HEAD) !== 0;
	}

	/**
	 * @return bool
	 */
	public function isOccupied() : bool{
		return ($this->meta & self::BITFLAG_OCCUPIED) !== 0;
	}

	public function setOccupied(bool $occupied = true){
		if($occupied){
			$this->meta |= self::BITFLAG_OCCUPIED;
		}else{
			$this->meta &= ~self::BITFLAG_OCCUPIED;
		}

		$this->getLevel()->setBlock($this, $this, false, false);

		if(($other = $this->getOtherHalf()) !== null and !$other->isOccupied()){
			$other->setOccupied($occupied);
		}
	}

	/**
	 * @param int  $meta
	 * @param bool $isHead
	 *
	 * @return int
	 */
	public static function getOtherHalfSide(int $meta, bool $isHead = false) : int{
		$rotation = $meta & 0x03;
		$side = -1;

		switch($rotation){
			case 0x00: //South
				$side = Vector3::SIDE_SOUTH;
				break;
			case 0x01: //West
				$side = Vector3::SIDE_WEST;
				break;
			case 0x02: //North
				$side = Vector3::SIDE_NORTH;
				break;
			case 0x03: //East
				$side = Vector3::SIDE_EAST;
				break;
		}

		if($isHead){
			$side = Vector3::getOppositeSide($side);
		}

		return $side;
	}

	/**
	 * @return Bed|null
	 */
	public function getOtherHalf(){
		$other = $this->getSide(self::getOtherHalfSide($this->meta, $this->isHeadPart()));
		if($other instanceof Bed and $other->getId() === $this->getId() and $other->isHeadPart() !== $this->isHeadPart() and (($other->getDamage() & 0x03) === ($this->getDamage() & 0x03))){
			return $other;
		}

		return null;
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		if($player !== null){
			$other = $this->getOtherHalf();
			if($other === null){
				$player->sendMessage(TextFormat::GRAY . "This bed is incomplete");

				return true;
			}elseif($player->distanceSquared($this) > 4 and $player->distanceSquared($other) > 4){
				//MCPE doesn't have messages for bed too far away
				return true;
			}

			$time = $this->getLevel()->getTime() % Level::TIME_FULL;

			$isNight = ($time >= Level::TIME_NIGHT and $time < Level::TIME_SUNRISE);

			if(!$isNight){
				$player->sendMessage(new TranslationContainer(TextFormat::GRAY . "%tile.bed.noSleep"));

				return true;
			}

			$b = ($this->isHeadPart() ? $this : $other);

			if($b->isOccupied()){
				$player->sendMessage(new TranslationContainer(TextFormat::GRAY . "%tile.bed.occupied"));

				return true;
			}

			$player->sleepOn($b);
		}

		return true;

	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $facePos, Player $player = null) : bool{
		$down = $this->getSide(Vector3::SIDE_DOWN);
		if(!$down->isTransparent()){
			$meta = (($player instanceof Player ? $player->getDirection() : 0) - 1) & 0x03;
			$next = $this->getSide(self::getOtherHalfSide($meta));
			if($next->canBeReplaced() === true and !$next->getSide(Vector3::SIDE_DOWN)->isTransparent()){
				$this->getLevel()->setBlock($blockReplace, BlockFactory::get($this->id, $meta), true, true);
				$this->getLevel()->setBlock($next, BlockFactory::get($this->id, $meta | self::BITFLAG_HEAD), true, true);

				$nbt = new CompoundTag("", [
					new StringTag("id", Tile::BED),
					new ByteTag("color", $item->getDamage() & 0x0f),
					new IntTag("x", $blockReplace->x),
					new IntTag("y", $blockReplace->y),
					new IntTag("z", $blockReplace->z)
				]);

				$nbt2 = clone $nbt;
				$nbt2["x"] = $next->x;
				$nbt2["z"] = $next->z;

				Tile::createTile(Tile::BED, $this->getLevel(), $nbt);
				Tile::createTile(Tile::BED, $this->getLevel(), $nbt2);

				return true;
			}
		}

		return false;
	}

	public function onBreak(Item $item, Player $player = null) : bool{
		$this->getLevel()->setBlock($this, BlockFactory::get(Block::AIR), true, true);
		if(($other = $this->getOtherHalf()) !== null){
			$this->getLevel()->useBreakOn($other, $item, $player, $player !== null); //make sure tiles get removed
		}

		return true;
	}

	public function getDrops(Item $item) : array{
		if($this->isHeadPart()){
			$tile = $this->getLevel()->getTile($this);
			if($tile instanceof TileBed){
				return [
					ItemFactory::get($this->getItemId(), $tile->getColor(), 1)
				];
			}else{
				return [
					ItemFactory::get($this->getItemId(), 14, 1) //Red
				];
			}
		}

		return [];
	}

}
