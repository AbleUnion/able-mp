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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\entity\Attribute;
use pocketmine\network\mcpe\NetworkSession;

class UpdateAttributesPacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::UPDATE_ATTRIBUTES_PACKET;

	public $entityRuntimeId;

	/** @var Attribute[] */
	public $entries = [];

	public function decodePayload(){
	}

	public function encodePayload(){
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putUnsignedVarInt(count($this->entries));
		foreach($this->entries as $entry){
			$this->putLFloat($entry->getMinValue());
			$this->putLFloat($entry->getMaxValue());
			$this->putLFloat($entry->getValue());
			$this->putLFloat($entry->getDefaultValue());
			$this->putString($entry->getName());
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateAttributes($this);
	}

	/**
	 * @return PacketName|string
	 */
	public function getName() : string{
		return "UpdateAttributesPacket";
	}
}