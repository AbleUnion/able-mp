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
use pocketmine\item\Tool;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\Sign as TileSign;
use pocketmine\tile\Tile;

class SignPost extends Transparent{

	protected $id = self::SIGN_POST;

	protected $itemId = Item::SIGN;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getHardness() : float{
		return 1;
	}

	public function isSolid() : bool{
		return false;
	}

	public function getName() : string{
		return "Sign Post";
	}

	protected function recalculateBoundingBox() : ?AxisAlignedBB{
		return null;
	}


	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		if($face !== Vector3::SIDE_DOWN){

			if($face === Vector3::SIDE_UP){
				$this->meta = floor((($player->yaw + 180) * 16 / 360) + 0.5) & 0x0f;
				$this->getLevel()->setBlock($blockReplace, $this, true);
			}else{
				$this->meta = $face;
				$this->getLevel()->setBlock($blockReplace, BlockFactory::get(Block::WALL_SIGN, $this->meta), true);
			}

			Tile::createTile(Tile::SIGN, $this->getLevel(), TileSign::createNBT($this, $face, $item, $player));

			return true;
		}

		return false;
	}

	public function onUpdate(int $type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if($this->getSide(Vector3::SIDE_DOWN)->getId() === self::AIR){
				$this->getLevel()->useBreakOn($this);

				return Level::BLOCK_UPDATE_NORMAL;
			}
		}

		return false;
	}

	public function getToolType() : int{
		return Tool::TYPE_AXE;
	}

	public function getVariantBitmask() : int{
		return 0;
	}
}