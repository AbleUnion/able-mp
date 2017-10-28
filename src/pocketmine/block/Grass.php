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

use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\Tool;
use pocketmine\level\generator\object\TallGrass as TallGrassObject;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\Random;

class Grass extends Solid{

	protected $id = self::GRASS;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Grass";
	}

	public function getHardness() : float{
		return 0.6;
	}

	public function getToolType() : int{
		return Tool::TYPE_SHOVEL;
	}

	public function getDrops(Item $item) : array{
		return [
			ItemFactory::get(Item::DIRT, 0, 1)
		];
	}

	public function ticksRandomly() : bool{
		return true;
	}

	public function onUpdate(int $type){
		if($type === Level::BLOCK_UPDATE_RANDOM){
			$lightAbove = $this->level->getFullLightAt($this->x, $this->y + 1, $this->z);
			if($lightAbove < 4 and BlockFactory::$lightFilter[$this->level->getBlockIdAt($this->x, $this->y + 1, $this->z)] >= 3){ //2 plus 1 standard filter amount
				//grass dies
				$this->level->getServer()->getPluginManager()->callEvent($ev = new BlockSpreadEvent($this, $this, BlockFactory::get(Block::DIRT)));
				if(!$ev->isCancelled()){
					$this->level->setBlock($this, $ev->getNewState(), false, false);
				}

				return Level::BLOCK_UPDATE_RANDOM;
			}elseif($lightAbove >= 9){
				//try grass spread
				$vector = $this->asVector3();
				for($i = 0; $i < 4; ++$i){
					$vector->x = mt_rand($this->x - 1, $this->x + 1);
					$vector->y = mt_rand($this->y - 3, $this->y + 1);
					$vector->z = mt_rand($this->z - 1, $this->z + 1);
					if(
						$this->level->getBlockIdAt($vector->x, $vector->y, $vector->z) !== Block::DIRT or
						$this->level->getFullLightAt($vector->x, $vector->y + 1, $vector->z) < 4 or
						BlockFactory::$lightFilter[$this->level->getBlockIdAt($vector->x, $vector->y + 1, $vector->z)] >= 3
					){
						continue;
					}

					$this->level->getServer()->getPluginManager()->callEvent($ev = new BlockSpreadEvent($this->level->getBlock($vector), $this, BlockFactory::get(Block::GRASS)));
					if(!$ev->isCancelled()){
						$this->level->setBlock($vector, $ev->getNewState(), false, false);
					}
				}

				return Level::BLOCK_UPDATE_RANDOM;
			}
		}

		return false;
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		if($item->getId() === Item::DYE and $item->getDamage() === 0x0F){
			$item->count--;
			TallGrassObject::growGrass($this->getLevel(), $this, new Random(mt_rand()), 8, 2);

			return true;
		}elseif($item->isHoe()){
			$item->useOn($this);
			$this->getLevel()->setBlock($this, BlockFactory::get(Block::FARMLAND));

			return true;
		}elseif($item->isShovel() and $this->getSide(Vector3::SIDE_UP)->getId() === Block::AIR){
			$item->useOn($this);
			$this->getLevel()->setBlock($this, BlockFactory::get(Block::GRASS_PATH));

			return true;
		}

		return false;
	}
}
