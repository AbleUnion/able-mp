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
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\{
	ByteTag, CompoundTag, FloatTag, IntTag, StringTag
};
use pocketmine\Player;
use pocketmine\tile\ItemFrame as TileItemFrame;
use pocketmine\tile\Tile;

class ItemFrame extends Flowable{
	protected $id = Block::ITEM_FRAME_BLOCK;

	protected $itemId = Item::ITEM_FRAME;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Item Frame";
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		$tile = $this->level->getTile($this);
		if(!($tile instanceof TileItemFrame)){
			$nbt = new CompoundTag("", [
				new StringTag("id", Tile::ITEM_FRAME),
				new IntTag("x", $this->x),
				new IntTag("y", $this->y),
				new IntTag("z", $this->z),
				new FloatTag("ItemDropChance", 1.0),
				new ByteTag("ItemRotation", 0)
			]);
			$tile = Tile::createTile(Tile::ITEM_FRAME, $this->getLevel(), $nbt);
		}

		if($tile->hasItem()){
			$tile->setItemRotation(($tile->getItemRotation() + 1) % 8);
		}else{
			if($item->getCount() > 0){
				$frameItem = clone $item;
				$frameItem->setCount(1);
				$item->setCount($item->getCount() - 1);
				$tile->setItem($frameItem);
				if($player instanceof Player and $player->isSurvival()){
					$player->getInventory()->setItemInHand($item->getCount() <= 0 ? ItemFactory::get(Item::AIR) : $item);
				}
			}
		}

		return true;
	}

	public function onBreak(Item $item, Player $player = null) : bool{
		$tile = $this->level->getTile($this);
		if($tile instanceof TileItemFrame){
			//TODO: add events
			if(lcg_value() <= $tile->getItemDropChance() and $tile->getItem()->getId() !== Item::AIR){
				$this->level->dropItem($tile->getBlock(), $tile->getItem());
			}
		}
		return parent::onBreak($item, $player);
	}

	public function onUpdate(int $type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			$sides = [
				0 => 4,
				1 => 5,
				2 => 2,
				3 => 3
			];
			if(!$this->getSide($sides[$this->meta])->isSolid()){
				$this->level->useBreakOn($this);
				return Level::BLOCK_UPDATE_NORMAL;
			}
		}
		return false;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $facePos, Player $player = null) : bool{
		if($face === Vector3::SIDE_DOWN or $face === Vector3::SIDE_UP){
			return false;
		}

		$faces = [
			Vector3::SIDE_NORTH => 3,
			Vector3::SIDE_SOUTH => 2,
			Vector3::SIDE_WEST => 1,
			Vector3::SIDE_EAST => 0
		];

		$this->meta = $faces[$face];
		$this->level->setBlock($blockReplace, $this, true, true);

		$nbt = new CompoundTag("", [
			new StringTag("id", Tile::ITEM_FRAME),
			new IntTag("x", $blockReplace->x),
			new IntTag("y", $blockReplace->y),
			new IntTag("z", $blockReplace->z),
			new FloatTag("ItemDropChance", 1.0),
			new ByteTag("ItemRotation", 0)
		]);

		if($item->hasCustomBlockData()){
			foreach($item->getCustomBlockData() as $key => $v){
				$nbt->{$key} = $v;
			}
		}

		Tile::createTile(Tile::ITEM_FRAME, $this->getLevel(), $nbt);

		return true;

	}

	public function getVariantBitmask() : int{
		return 0;
	}
}