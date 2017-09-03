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

class LoginPacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::LOGIN_PACKET;

	const EDITION_POCKET = 0;

	public $username;
	public $protocol;
	public $gameEdition;
	public $clientUUID;
	public $clientId;
	public $identityPublicKey;
	public $serverAddress;

	public $skinId;
	public $skin = "";

	/** @var array (the "chain" index contains one or more JWTs) */
	public $chainData = [];
	/** @var string */
	public $clientDataJwt;
	/** @var array decoded payload of the clientData JWT */
	public $clientData = [];

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	public function decodePayload(){
		$this->protocol = $this->getInt();

		if(!in_array($this->protocol, ProtocolInfo::ACCEPTED_PROTOCOLS)){
			$this->buffer = null;
			return; //Do not attempt to decode for non-accepted protocols
		}

		$this->gameEdition = $this->getByte();

		$this->setBuffer($this->getString(), 0);

		$this->chainData = json_decode($this->get($this->getLInt()), true);
		foreach($this->chainData["chain"] as $chain){
			$webtoken = $this->decodeToken($chain);
			if(isset($webtoken["extraData"])){
				if(isset($webtoken["extraData"]["displayName"])){
					$this->username = $webtoken["extraData"]["displayName"];
				}
				if(isset($webtoken["extraData"]["identity"])){
					$this->clientUUID = $webtoken["extraData"]["identity"];
				}
				if(isset($webtoken["identityPublicKey"])){
					$this->identityPublicKey = $webtoken["identityPublicKey"];
				}
			}
		}

		$this->clientDataJwt = $this->get($this->getLInt());
		$this->clientData = $this->decodeToken($this->clientDataJwt);

		$this->clientId = $this->clientData["ClientRandomId"] ?? null;
		$this->serverAddress = $this->clientData["ServerAddress"] ?? null;
		$this->skinId = $this->clientData["SkinId"] ?? null;

		if(isset($this->clientData["SkinData"])){
			$this->skin = base64_decode($this->clientData["SkinData"]);
		}
	}

	public function encodePayload(){
		//TODO
	}

	public function decodeToken($token){
		list($headB64, $payloadB64, $sigB64) = explode(".", $token);

		return json_decode(base64_decode($payloadB64), true);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLogin($this);
	}
}