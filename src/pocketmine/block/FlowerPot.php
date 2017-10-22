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

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\FlowerPot as TileFlowerPot;
use pocketmine\tile\Tile;

class FlowerPot extends Flowable{

	const STATE_EMPTY = 0;
	const STATE_FULL = 1;

	protected $id = self::FLOWER_POT_BLOCK;
	protected $itemId = Item::FLOWER_POT;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Flower Pot Block";
	}

	protected function recalculateBoundingBox(){
		return new AxisAlignedBB(
			$this->x + 0.3125,
			$this->y,
			$this->z + 0.3125,
			$this->x + 0.6875,
			$this->y + 0.375,
			$this->z + 0.6875
		);
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $facePos, Player $player = null) : bool{
		if($this->getSide(Vector3::SIDE_DOWN)->isTransparent()){
			return false;
		}

		$this->getLevel()->setBlock($blockReplace, $this, true, true);

		$nbt = new CompoundTag("", [
			new StringTag("id", Tile::FLOWER_POT),
			new IntTag("x", $blockReplace->x),
			new IntTag("y", $blockReplace->y),
			new IntTag("z", $blockReplace->z),
			new ShortTag("item", 0),
			new IntTag("mData", 0)
		]);

		if($item->hasCustomBlockData()){
			foreach($item->getCustomBlockData() as $key => $v){
				$nbt->{$key} = $v;
			}
		}

		Tile::createTile(Tile::FLOWER_POT, $this->getLevel(), $nbt);
		return true;
	}

	public function onUpdate(int $type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if($this->getSide(Vector3::SIDE_DOWN)->isTransparent() === true){
				$this->getLevel()->useBreakOn($this);

				return Level::BLOCK_UPDATE_NORMAL;
			}
		}

		return false;
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		$pot = $this->getLevel()->getTile($this);
		if(!($pot instanceof TileFlowerPot)){
			return false;
		}
		if(!$pot->canAddItem($item)){
			return true;
		}

		$this->setDamage(self::STATE_FULL); //specific damage value is unnecessary, it just needs to be non-zero to show an item.
		$this->getLevel()->setBlock($this, $this, true, false);
		$pot->setItem($item);

		if($player instanceof Player){
			if($player->isSurvival()){
				$item->setCount($item->getCount() - 1);
				$player->getInventory()->setItemInHand($item->getCount() > 0 ? $item : ItemFactory::get(Item::AIR));
			}
		}
		return true;
	}

	public function getDrops(Item $item) : array{
		$items = parent::getDrops($item);

		$tile = $this->getLevel()->getTile($this);
		if($tile instanceof TileFlowerPot){
			$item = $tile->getItem();
			if($item->getId() !== Item::AIR){
				$items[] = $item;
			}
		}

		return $items;
	}

}