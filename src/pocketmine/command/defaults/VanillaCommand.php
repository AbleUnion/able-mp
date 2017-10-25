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

namespace pocketmine\command\defaults;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

abstract class VanillaCommand extends Command{
	const MAX_COORD = 30000000;
	const MIN_COORD = -30000000;

	/**
	 * @param CommandSender $sender
	 * @param mixed         $value
	 * @param int           $min
	 * @param int           $max
	 *
	 * @return int
	 */
	protected function getInteger(CommandSender $sender, $value, int $min = self::MIN_COORD, int $max = self::MAX_COORD) : int{
		$i = (int) $value;

		if($i < $min){
			$i = $min;
		}elseif($i > $max){
			$i = $max;
		}

		return $i;
	}

	/**
	 * @param float         $original
	 * @param CommandSender $sender
	 * @param string        $input
	 * @param float         $min
	 * @param float         $max
	 *
	 * @return float
	 */
	protected function getRelativeDouble(float $original, CommandSender $sender, string $input, float $min = self::MIN_COORD, float $max = self::MAX_COORD) : float{
		if($input{0} === "~"){
			$value = $this->getDouble($sender, substr($input, 1));

			return $original + $value;
		}

		return $this->getDouble($sender, $input, $min, $max);
	}

	/**
	 * @param CommandSender $sender
	 * @param mixed         $value
	 * @param float         $min
	 * @param float         $max
	 *
	 * @return float
	 */
	protected function getDouble(CommandSender $sender, $value, float $min = self::MIN_COORD, float $max = self::MAX_COORD) : float{
		$i = (double) $value;

		if($i < $min){
			$i = $min;
		}elseif($i > $max){
			$i = $max;
		}

		return $i;
	}
}