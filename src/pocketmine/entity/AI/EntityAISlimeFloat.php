<?php

/*
 *   ____  _            _      _       _     _
 *  |  _ \| |          | |    (_)     | |   | |
 *  | |_) | |_   _  ___| |     _  __ _| |__ | |_
 *  |  _ <| | | | |/ _ \ |    | |/ _` | '_ \| __|
 *  | |_) | | |_| |  __/ |____| | (_| | | | | |_
 *  |____/|_|\__,_|\___|______|_|\__, |_| |_|\__|
 *                                __/ |
 *                               |___/
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author BlueLightJapan Team
 * 
*/

namespace pocketmine\entity\AI;

class EntityAISlimeFloat extends EntityAIBase{

	private $slime;

	public function __construct($slime){
		$this->slime = $slime;
		$this->setMutexBits(5);
		$slime->getNavigator()->setCanSwim(true);
	}

	public function shouldExecute() : bool{
		return $this->slime->isInsideOfWater() || $this->slime->isInsideOfLava();
	}

	public function updateTask(){
		if ((rand(0, 10) / 10) < 0.8){
			$this->slime->getJumpHelper()->setJumping();
		}

		$this->slime->getMoveHelper()->setSpeed(1.2);
	}
}