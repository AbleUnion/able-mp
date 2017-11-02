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

class EntityBounceOnBlockEvent extends EntityEvent {
	private $falldistance;
	private $bouncedistance;
	
	/**
	 * @param Entity $entity
	 * @param float $falldistance
	 * @param float $bouncedistance
	 */
	public function __construct(Entity $entity, float $falldistance, float $bouncedistance){
		$this->entity = $entity;
		$this->falldistance = $falldistance;
		$this->bouncedistance = $bouncedistance;
	}
	/**
	 * @return Entity
	 */
	public function getEntity(){
		return $this->entity;
	}
	/**
	 * @return float
	 */
	public function getFallDistance() : float{
		return $this->falldistance;
	}
	/**
	 * @return float
	 */
	public function getBounceDistance() : float{
		return $this->bouncedistance;
	}
}
