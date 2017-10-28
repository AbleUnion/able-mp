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

class Leaves2 extends Leaves{

	protected $id = self::LEAVES2;
	protected $woodType = self::WOOD2;

	public function getName() : string{
		static $names = [
			self::ACACIA => "Acacia Leaves",
			self::DARK_OAK => "Dark Oak Leaves"
		];
		return $names[$this->meta & 0x03] ?? "Unknown";
	}

	public function getDrops(Item $item) : array{
		$variantMeta = $this->getDamage() & 0x03;

		if($item->isShears()){
			return [
				ItemFactory::get($this->getItemId(), $variantMeta, 1)
			];
		}elseif(mt_rand(1, 20) === 1){ //Saplings
			return [
				ItemFactory::get(Item::SAPLING, $variantMeta + 4, 1)
			];
		}

		return [];
	}
}