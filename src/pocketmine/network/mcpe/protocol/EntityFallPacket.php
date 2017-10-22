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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>


use pocketmine\network\mcpe\NetworkSession;

class EntityFallPacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::ENTITY_FALL_PACKET;

	/** @var int */
	public $entityRuntimeId;
	/** @var float */
	public $fallDistance;
	/** @var bool */
	public $bool1;

	protected function decodePayload(){
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->fallDistance = $this->getLFloat();
		$this->bool1 = $this->getBool();
	}

	protected function encodePayload(){
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putLFloat($this->fallDistance);
		$this->putBool($this->bool1);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleEntityFall($this);
	}
}
