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

namespace pocketmine\event\player;

use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\Player;

class PlayerItemHeldEvent extends PlayerEvent implements Cancellable{
	public static $handlerList = null;

	/** @var Item */
	private $item;
	/** @var int */
	private $hotbarSlot;

	public function __construct(Player $player, Item $item, int $hotbarSlot){
		$this->player = $player;
		$this->item = $item;
		$this->hotbarSlot = $hotbarSlot;
	}

	/**
	 * Returns the hotbar slot the player is attempting to hold.
	 *
	 * NOTE: This event is called BEFORE the slot is equipped server-side. Setting the player's held item during this
	 * event will result in the **old** slot being changed, not this one.
	 *
	 * To change the item in the slot that the player is attempting to hold, set the slot that this function reports.
	 *
	 * @return int
	 */
	public function getSlot() : int{
		return $this->hotbarSlot;
	}

	/**
	 * @deprecated This is currently an alias of {@link getSlot}
	 *
	 * Some background for confused future readers: Before MCPE 1.2, hotbar slots and inventory slots were not the same
	 * thing - a hotbar slot was a link to a certain slot in the inventory.
	 * As of 1.2, hotbar slots are now always linked to their respective slots in the inventory, meaning that the two
	 * are now synonymous, rendering the separate methods obsolete.
	 *
	 * @return int
	 */
	public function getInventorySlot() : int{
		return $this->getSlot();
	}

	/**
	 * Returns the item in the slot that the player is trying to equip.
	 *
	 * @return Item
	 */
	public function getItem() : Item{
		return $this->item;
	}
}