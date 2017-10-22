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

namespace pocketmine\event\entity;

use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;

/**
 * Called when a entity decides to explode
 */
class ExplosionPrimeEvent extends EntityEvent implements Cancellable{
	public static $handlerList = null;

	/** @var float */
	protected $force;
	/** @var bool */
	private $blockBreaking;

	/**
	 * @param Entity $entity
	 * @param float  $force
	 */
	public function __construct(Entity $entity, float $force){
		$this->entity = $entity;
		$this->force = $force;
		$this->blockBreaking = true;
	}

	/**
	 * @return float
	 */
	public function getForce() : float{
		return $this->force;
	}

	public function setForce(float $force){
		$this->force = $force;
	}

	/**
	 * @return bool
	 */
	public function isBlockBreaking() : bool{
		return $this->blockBreaking;
	}

	/**
	 * @param bool $affectsBlocks
	 */
	public function setBlockBreaking(bool $affectsBlocks){
		$this->blockBreaking = $affectsBlocks;
	}

}