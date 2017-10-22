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

namespace pocketmine\inventory;

use pocketmine\event\Timings;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\MainLogger;
use pocketmine\utils\UUID;

class CraftingManager{

	/** @var CraftingRecipe[] */
	public $recipes = [];

	/** @var CraftingRecipe[][] */
	protected $recipeLookup = [];

	/** @var FurnaceRecipe[] */
	public $furnaceRecipes = [];

	private static $RECIPE_COUNT = 0;

	/** @var CraftingDataPacket */
	private $craftingDataCache;

	public function __construct(){
		// load recipes from src/pocketmine/resources/recipes.json
		$recipes = new Config(Server::getInstance()->getFilePath() . "src/pocketmine/resources/recipes.json", Config::JSON, []);

		MainLogger::getLogger()->info("Loading recipes...");
		foreach($recipes->getAll() as $recipe){
			switch($recipe["type"]){
				case 0:
					// TODO: handle multiple result items
					$first = $recipe["output"][0];
					$result = new ShapelessRecipe(Item::jsonDeserialize($first));

					foreach($recipe["input"] as $ingredient){
						$result->addIngredient(Item::jsonDeserialize($ingredient));
					}
					$this->registerRecipe($result);
					break;
				case 1:
					$first = array_shift($recipe["output"]);

					$this->registerRecipe(new ShapedRecipe(
						Item::jsonDeserialize($first),
						$recipe["shape"],
						array_map(function(array $data) : Item{ return Item::jsonDeserialize($data); }, $recipe["input"]),
						array_map(function(array $data) : Item{ return Item::jsonDeserialize($data); }, $recipe["output"])
					));
					break;
				case 2:
				case 3:
					$result = $recipe["output"];
					$resultItem = Item::jsonDeserialize($result);
					$this->registerRecipe(new FurnaceRecipe($resultItem, ItemFactory::get($recipe["inputId"], $recipe["inputDamage"] ?? -1, 1)));
					break;
				default:
					break;
			}
		}

		$this->buildCraftingDataCache();
	}

	/**
	 * Rebuilds the cached CraftingDataPacket.
	 */
	public function buildCraftingDataCache(){
		Timings::$craftingDataCacheRebuildTimer->startTiming();
		$pk = new CraftingDataPacket();
		$pk->cleanRecipes = true;

		foreach($this->recipes as $recipe){
			if($recipe instanceof ShapedRecipe){
				$pk->addShapedRecipe($recipe);
			}elseif($recipe instanceof ShapelessRecipe){
				$pk->addShapelessRecipe($recipe);
			}
		}

		foreach($this->furnaceRecipes as $recipe){
			$pk->addFurnaceRecipe($recipe);
		}

		$pk->encode();

		$this->craftingDataCache = $pk;
		Timings::$craftingDataCacheRebuildTimer->stopTiming();
	}

	/**
	 * Returns a CraftingDataPacket for sending to players. Rebuilds the cache if it is outdated.
	 *
	 * @return CraftingDataPacket
	 */
	public function getCraftingDataPacket(){
		if($this->craftingDataCache === null){
			$this->buildCraftingDataCache();
		}

		return $this->craftingDataCache;
	}

	public function sort(Item $i1, Item $i2){
		if($i1->getId() > $i2->getId()){
			return 1;
		}elseif($i1->getId() < $i2->getId()){
			return -1;
		}elseif($i1->getDamage() > $i2->getDamage()){
			return 1;
		}elseif($i1->getDamage() < $i2->getDamage()){
			return -1;
		}elseif($i1->getCount() > $i2->getCount()){
			return 1;
		}elseif($i1->getCount() < $i2->getCount()){
			return -1;
		}else{
			return 0;
		}
	}

	/**
	 * @param UUID $id
	 * @return CraftingRecipe|null
	 */
	public function getRecipe(UUID $id){
		$index = $id->toBinary();
		return $this->recipes[$index] ?? null;
	}

	/**
	 * @return Recipe[]
	 */
	public function getRecipes() : array{
		return $this->recipes;
	}

	/**
	 * @return FurnaceRecipe[]
	 */
	public function getFurnaceRecipes() : array{
		return $this->furnaceRecipes;
	}

	/**
	 * @param Item $input
	 *
	 * @return FurnaceRecipe|null
	 */
	public function matchFurnaceRecipe(Item $input){
		return $this->furnaceRecipes[$input->getId() . ":" . $input->getDamage()] ?? $this->furnaceRecipes[$input->getId() . ":?"] ?? null;
	}

	/**
	 * @param ShapedRecipe $recipe
	 */
	public function registerShapedRecipe(ShapedRecipe $recipe){
		$result = $recipe->getResult();

		/** @var Item[][] $ingredients */
		$ingredients = $recipe->getIngredientMap();
		$hash = "";
		foreach($ingredients as $v){
			foreach($v as $item){
				if($item !== null){
					/** @var Item $item */
					$hash .= $item->getId() . ":" . ($item->hasAnyDamageValue() ? "?" : $item->getDamage()) . "x" . $item->getCount() . ",";
				}
			}

			$hash .= ";";
		}

		$this->recipeLookup[$result->getId() . ":" . $result->getDamage()][$hash] = $recipe;
		$this->craftingDataCache = null;
	}

	/**
	 * @param ShapelessRecipe $recipe
	 */
	public function registerShapelessRecipe(ShapelessRecipe $recipe){
		$result = $recipe->getResult();
		$hash = "";
		$ingredients = $recipe->getIngredientList();
		usort($ingredients, [$this, "sort"]);
		foreach($ingredients as $item){
			$hash .= $item->getId() . ":" . ($item->hasAnyDamageValue() ? "?" : $item->getDamage()) . "x" . $item->getCount() . ",";
		}
		$this->recipeLookup[$result->getId() . ":" . $result->getDamage()][$hash] = $recipe;
		$this->craftingDataCache = null;
	}

	/**
	 * @param FurnaceRecipe $recipe
	 */
	public function registerFurnaceRecipe(FurnaceRecipe $recipe){
		$input = $recipe->getInput();
		$this->furnaceRecipes[$input->getId() . ":" . ($input->hasAnyDamageValue() ? "?" : $input->getDamage())] = $recipe;
		$this->craftingDataCache = null;
	}

	/**
	 * @param ShapelessRecipe $recipe
	 * @return bool
	 */
	public function matchRecipe(ShapelessRecipe $recipe) : bool{
		if(!isset($this->recipeLookup[$idx = $recipe->getResult()->getId() . ":" . $recipe->getResult()->getDamage()])){
			return false;
		}

		$hash = "";
		$ingredients = $recipe->getIngredientList();
		usort($ingredients, [$this, "sort"]);
		foreach($ingredients as $item){
			$hash .= $item->getId() . ":" . ($item->hasAnyDamageValue() ? "?" : $item->getDamage()) . "x" . $item->getCount() . ",";
		}

		if(isset($this->recipeLookup[$idx][$hash])){
			return true;
		}

		$hasRecipe = null;
		foreach($this->recipeLookup[$idx] as $possibleRecipe){
			if($possibleRecipe instanceof ShapelessRecipe){
				if($possibleRecipe->getIngredientCount() !== count($ingredients)){
					continue;
				}
				$checkInput = $possibleRecipe->getIngredientList();
				foreach($ingredients as $item){
					$amount = $item->getCount();
					foreach($checkInput as $k => $checkItem){
						if($checkItem->equals($item, !$checkItem->hasAnyDamageValue(), $checkItem->hasCompoundTag())){
							$remove = min($checkItem->getCount(), $amount);
							$checkItem->setCount($checkItem->getCount() - $remove);
							if($checkItem->getCount() === 0){
								unset($checkInput[$k]);
							}
							$amount -= $remove;
							if($amount === 0){
								break;
							}
						}
					}
				}

				if(count($checkInput) === 0){
					$hasRecipe = $possibleRecipe;
					break;
				}
			}
			if($hasRecipe instanceof Recipe){
				break;
			}
		}

		return $hasRecipe !== null;

	}

	/**
	 * @param Recipe $recipe
	 */
	public function registerRecipe(Recipe $recipe){
		if($recipe instanceof CraftingRecipe){
			$result = $recipe->getResult();
			$recipe->setId($uuid = UUID::fromData((string) ++self::$RECIPE_COUNT, (string) $result->getId(), (string) $result->getDamage(), (string) $result->getCount(), $result->getCompoundTag()));
			$this->recipes[$uuid->toBinary()] = $recipe;
		}

		$recipe->registerToCraftingManager($this);
	}

}
