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
use pocketmine\math\Vector3;
use pocketmine\Player;

class HayBale extends Solid{

	protected $id = self::HAY_BALE;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Hay Bale";
	}

	public function getHardness() : float{
		return 0.5;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $facePos, Player $player = null) : bool{
		$faces = [
			0 => 0,
			1 => 0,
			2 => 0b1000,
			3 => 0b1000,
			4 => 0b0100,
			5 => 0b0100
		];

		$this->meta = ($this->meta & 0x03) | $faces[$face];
		$this->getLevel()->setBlock($blockReplace, $this, true, true);

		return true;
	}

	public function getVariantBitmask() : int{
		return 0x03;
	}
}