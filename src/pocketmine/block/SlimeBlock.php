<?php

/*
 *
 *  _____   _____   __   _   _   _____  __    __  _____
 * /  ___| | ____| |  \ | | | | /  ___/ \ \  / / /  ___/
 * | |     | |__   |   \| | | | | |___   \ \/ /  | |___
 * | |  _  |  __|  | |\   | | | \___  \   \  /   \___  \
 * | |_| | | |___  | | \  | | |  ___| |   / /     ___| |
 * \_____/ |_____| |_|  \_| |_| /_____/  /_/     /_____/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author iTX Technologies
 * @link https://itxtech.org
 *
 */

namespace pocketmine\block;


class SlimeBlock extends Solid {

	protected $id = self::SLIME_BLOCK;

	/**
	 * SlimeBlock constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 15){
		$this->meta = $meta;
	}

	/**
	 * @return bool
	 */
	public function hasEntityCollision() : bool{
		return true;
	}

	/**
	 * @return int
	 */
	public function getHardness() : float{
		return 0;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Slime Block";
	}
	public function getMaxBounce() : float{
		return 60;
	}
}
