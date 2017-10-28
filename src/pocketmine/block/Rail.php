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
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Rail extends Flowable{

	const STRAIGHT_NORTH_SOUTH = 0;
	const STRAIGHT_EAST_WEST = 1;
	const ASCENDING_EAST = 2;
	const ASCENDING_WEST = 3;
	const ASCENDING_NORTH = 4;
	const ASCENDING_SOUTH = 5;
	const CURVE_SOUTHEAST = 6;
	const CURVE_SOUTHWEST = 7;
	const CURVE_NORTHWEST = 8;
	const CURVE_NORTHEAST = 9;

	protected $id = self::RAIL;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Rail";
	}

	public function getHardness() : float{
		return 0.7;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		if(!$blockReplace->getSide(Vector3::SIDE_DOWN)->isTransparent()){
			return $this->getLevel()->setBlock($blockReplace, $this, true, true);
		}

		return false;
	}

	public function onUpdate(int $type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if($this->getSide(Vector3::SIDE_DOWN)->isTransparent()){
				$this->getLevel()->useBreakOn($this);
				return $type;
			}else{
				//TODO: Update rail connectivity
			}
		}

		return false;
	}

	public function getVariantBitmask() : int{
		return 0;
	}
}
