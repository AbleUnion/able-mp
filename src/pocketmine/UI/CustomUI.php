<?php

namespace pocketmine\UI;

use pocketmine\Player;
use pocketmine\UI\elements\Button;
use pocketmine\UI\elements\UIElement;

interface CustomUI{

	public function handle($response, Player $player);

	public function jsonSerialize();

	/**
	 * To handle manual closing
	 * @param Player $player
	 */
	public function close(Player $player);

	public function getTitle();

	public function getContent(): array;

	/**
	 * @param int $index
	 * @return UIElement|Button|null
	 */
	public function getElement(int $index);

	public function setElement(UIElement $element, int $index);

	public function setID(int $id);

	public function getID(): int;
}
