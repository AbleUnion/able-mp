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

namespace pocketmine\item;

use pocketmine\block\BlockFactory;
use pocketmine\nbt\tag\CompoundTag;

/**
 * Manages Item instance creation and registration
 */
class ItemFactory{

	/** @var \SplFixedArray */
	private static $list = null;

	public static function init(){
		if(self::$list === null){
			self::$list = new \SplFixedArray(65536);

			self::registerItem(new IronShovel());
			self::registerItem(new IronPickaxe());
			self::registerItem(new IronAxe());
			self::registerItem(new FlintSteel());
			self::registerItem(new Apple());
			self::registerItem(new Bow());
			self::registerItem(new Arrow());
			self::registerItem(new Coal());
			self::registerItem(new Item(Item::DIAMOND, 0, "Diamond"));
			self::registerItem(new Item(Item::IRON_INGOT, 0, "Iron Ingot"));
			self::registerItem(new Item(Item::GOLD_INGOT, 0, "Gold Ingot"));
			self::registerItem(new IronSword());
			self::registerItem(new WoodenSword());
			self::registerItem(new WoodenShovel());
			self::registerItem(new WoodenPickaxe());
			self::registerItem(new WoodenAxe());
			self::registerItem(new StoneSword());
			self::registerItem(new StoneShovel());
			self::registerItem(new StonePickaxe());
			self::registerItem(new StoneAxe());
			self::registerItem(new DiamondSword());
			self::registerItem(new DiamondShovel());
			self::registerItem(new DiamondPickaxe());
			self::registerItem(new DiamondAxe());
			self::registerItem(new Stick());
			self::registerItem(new Bowl());
			self::registerItem(new MushroomStew());
			self::registerItem(new GoldSword());
			self::registerItem(new GoldShovel());
			self::registerItem(new GoldPickaxe());
			self::registerItem(new GoldAxe());
			self::registerItem(new StringItem());
			self::registerItem(new Item(Item::FEATHER, 0, "Feather"));
			self::registerItem(new Item(Item::GUNPOWDER, 0, "Gunpowder"));
			self::registerItem(new WoodenHoe());
			self::registerItem(new StoneHoe());
			self::registerItem(new IronHoe());
			self::registerItem(new DiamondHoe());
			self::registerItem(new GoldHoe());
			self::registerItem(new WheatSeeds());
			self::registerItem(new Item(Item::WHEAT, 0, "Wheat"));
			self::registerItem(new Bread());
			self::registerItem(new LeatherCap());
			self::registerItem(new LeatherTunic());
			self::registerItem(new LeatherPants());
			self::registerItem(new LeatherBoots());
			self::registerItem(new ChainHelmet());
			self::registerItem(new ChainChestplate());
			self::registerItem(new ChainLeggings());
			self::registerItem(new ChainBoots());
			self::registerItem(new IronHelmet());
			self::registerItem(new IronChestplate());
			self::registerItem(new IronLeggings());
			self::registerItem(new IronBoots());
			self::registerItem(new DiamondHelmet());
			self::registerItem(new DiamondChestplate());
			self::registerItem(new DiamondLeggings());
			self::registerItem(new DiamondBoots());
			self::registerItem(new GoldHelmet());
			self::registerItem(new GoldChestplate());
			self::registerItem(new GoldLeggings());
			self::registerItem(new GoldBoots());
			self::registerItem(new Item(Item::FLINT, 0, "Flint"));
			self::registerItem(new RawPorkchop());
			self::registerItem(new CookedPorkchop());
			self::registerItem(new Painting());
			self::registerItem(new GoldenApple());
			self::registerItem(new Sign());
			self::registerItem(new WoodenDoor());
			self::registerItem(new Bucket());

			self::registerItem(new Minecart());
			//TODO: SADDLE
			self::registerItem(new IronDoor());
			self::registerItem(new Redstone());
			self::registerItem(new Snowball());
			self::registerItem(new Boat());
			self::registerItem(new Item(Item::LEATHER, 0, "Leather"));

			self::registerItem(new Item(Item::BRICK, 0, "Brick"));
			self::registerItem(new Item(Item::CLAY_BALL, 0, "Clay"));
			self::registerItem(new Sugarcane());
			self::registerItem(new Item(Item::PAPER, 0, "Paper"));
			self::registerItem(new Book());
			self::registerItem(new Item(Item::SLIME_BALL, 0, "Slimeball"));
			//TODO: CHEST_MINECART

			self::registerItem(new Egg());
			self::registerItem(new Compass());
			self::registerItem(new FishingRod());
			self::registerItem(new Clock());
			self::registerItem(new Item(Item::GLOWSTONE_DUST, 0, "Glowstone Dust"));
			self::registerItem(new Fish());
			self::registerItem(new CookedFish());
			self::registerItem(new Dye());
			self::registerItem(new Item(Item::BONE, 0, "Bone"));
			self::registerItem(new Item(Item::SUGAR, 0, "Sugar"));
			self::registerItem(new Cake());
			self::registerItem(new Bed());
			//TODO: REPEATER
			self::registerItem(new Cookie());
			//TODO: FILLED_MAP
			self::registerItem(new Shears());
			self::registerItem(new Melon());
			self::registerItem(new PumpkinSeeds());
			self::registerItem(new MelonSeeds());
			self::registerItem(new RawBeef());
			self::registerItem(new Steak());
			self::registerItem(new RawChicken());
			self::registerItem(new CookedChicken());
			//TODO: ROTTEN_FLESH
			//TODO: ENDER_PEARL
			self::registerItem(new BlazeRod());
			self::registerItem(new Item(Item::GHAST_TEAR, 0, "Ghast Tear"));
			self::registerItem(new Item(Item::GOLD_NUGGET, 0, "Gold Nugget"));
			self::registerItem(new NetherWart());
			self::registerItem(new Potion());
			self::registerItem(new GlassBottle());
			self::registerItem(new SpiderEye());
			self::registerItem(new Item(Item::FERMENTED_SPIDER_EYE, 0, "Fermented Spider Eye"));
			self::registerItem(new Item(Item::BLAZE_POWDER, 0, "Blaze Powder"));
			self::registerItem(new Item(Item::MAGMA_CREAM, 0, "Magma Cream"));
			self::registerItem(new BrewingStand());
			//TODO: CAULDRON
			//TODO: ENDER_EYE
			self::registerItem(new Item(Item::GLISTERING_MELON, 0, "Glistering Melon"));
			self::registerItem(new SpawnEgg());
			//TODO: BOTTLE_O_ENCHANTING
			//TODO: FIREBALL

			self::registerItem(new Item(Item::EMERALD, 0, "Emerald"));
			self::registerItem(new ItemFrame());
			self::registerItem(new FlowerPot());
			self::registerItem(new Carrot());
			self::registerItem(new Potato());
			self::registerItem(new BakedPotato());
			//TODO: POISONOUS_POTATO
			//TODO: EMPTYMAP
			self::registerItem(new GoldenCarrot());
			self::registerItem(new Skull());
			//TODO: CARROTONASTICK
			self::registerItem(new Item(Item::NETHER_STAR, 0, "Nether Star"));
			self::registerItem(new PumpkinPie());

			//TODO: ENCHANTED_BOOK
			//TODO: COMPARATOR
			self::registerItem(new Item(Item::NETHER_BRICK, 0, "Nether Brick"));
			self::registerItem(new Item(Item::NETHER_QUARTZ, 0, "Nether Quartz"));
			//TODO: MINECART_WITH_TNT
			//TODO: HOPPER_MINECART
			self::registerItem(new Item(Item::PRISMARINE_SHARD, 0, "Prismarine Shard"));
			//TODO: HOPPER
			//TODO: RABBIT
			self::registerItem(new CookedRabbit());
			//TODO: RABBIT_STEW
			self::registerItem(new Item(Item::RABBIT_FOOT, 0, "Rabbit's Foot"));
			//TODO: RABBIT_HIDE
			//TODO: HORSEARMORLEATHER
			//TODO: HORSEARMORIRON
			//TODO: GOLD_HORSE_ARMOR
			//TODO: DIAMOND_HORSE_ARMOR
			//TODO: LEAD
			//TODO: NAMETAG
			self::registerItem(new Item(Item::PRISMARINE_CRYSTALS, 0, "Prismarine Crystals"));
			//TODO: MUTTONRAW
			//TODO: COOKED_MUTTON

			//TODO: END_CRYSTAL
			//TODO: SPRUCE_DOOR
			//TODO: BIRCH_DOOR
			//TODO: JUNGLE_DOOR
			//TODO: ACACIA_DOOR
			//TODO: DARK_OAK_DOOR
			//TODO: CHORUS_FRUIT
			self::registerItem(new Item(Item::CHORUS_FRUIT_POPPED, 0, "Popped Chorus Fruit"));

			//TODO: DRAGON_BREATH
			//TODO: SPLASH_POTION

			//TODO: LINGERING_POTION

			//TODO: COMMAND_BLOCK_MINECART
			//TODO: ELYTRA
			self::registerItem(new Item(Item::SHULKER_SHELL, 0, "Shulker Shell"));

			//TODO: TOTEM

			self::registerItem(new Item(Item::IRON_NUGGET, 0, "Iron Nugget"));

			self::registerItem(new Beetroot());
			self::registerItem(new BeetrootSeeds());
			self::registerItem(new BeetrootSoup());
			//TODO: RAW_SALMON
			//TODO: CLOWNFISH
			//TODO: PUFFERFISH
			//TODO: COOKED_SALMON

			self::registerItem(new GoldenAppleEnchanted());
		}

		Item::initCreativeItems();
	}

	/**
	 * Registers an item type into the index. Plugins may use this method to register new item types or override existing
	 * ones.
	 *
	 * NOTE: If you are registering a new item type, you will need to add it to the creative inventory yourself - it
	 * will not automatically appear there.
	 *
	 * @param Item $item
	 * @param bool $override
	 *
	 * @throws \RuntimeException if something attempted to override an already-registered item without specifying the
	 * $override parameter.
	 */
	public static function registerItem(Item $item, bool $override = false){
		$id = $item->getId();
		if(!$override and self::isRegistered($id)){
			throw new \RuntimeException("Trying to overwrite an already registered item");
		}

		self::$list[$id] = clone $item;
	}

	/**
	 * Returns an instance of the Item with the specified id, meta, count and NBT.
	 *
	 * @param int                $id
	 * @param int                $meta
	 * @param int                $count
	 * @param CompoundTag|string $tags
	 *
	 * @return Item
	 * @throws \TypeError
	 */
	public static function get(int $id, int $meta = 0, int $count = 1, $tags = "") : Item{
		if(!is_string($tags) and !($tags instanceof CompoundTag)){
			throw new \TypeError("`tags` argument must be a string or CompoundTag instance, " . (is_object($tags) ? "instance of " . get_class($tags) : gettype($tags)) . " given");
		}

		$item = null;
		try{
			if($id < 256){
				/* Blocks must have a damage value 0-15, but items can have damage value -1 to indicate that they are
				 * crafting ingredients with any-damage. */
				$item = new ItemBlock(BlockFactory::get($id, $meta !== -1 ? $meta & 0xf : 0), $meta);
			}else{
				/** @var Item|null $listed */
				$listed = self::$list[$id];
				if($listed !== null){
					$item = clone $listed;
				}
			}
		}catch(\RuntimeException $e){
			throw new \InvalidArgumentException("Item ID $id is invalid or out of bounds");
		}

		$item = ($item ?? new Item($id, $meta));

		$item->setDamage($meta);
		$item->setCount($count);
		$item->setCompoundTag($tags);
		return $item;
	}

	/**
	 * Tries to parse the specified string into Item ID/meta identifiers, and returns Item instances it created.
	 *
	 * Example accepted formats:
	 * - `diamond_pickaxe:5`
	 * - `minecraft:string`
	 * - `351:4 (lapis lazuli ID:meta)`
	 *
	 * If multiple item instances are to be created, their identifiers must be comma-separated, for example:
	 * `diamond_pickaxe,wooden_shovel:18,iron_ingot`
	 *
	 * @param string $str
	 * @param bool   $multiple
	 *
	 * @return Item[]|Item
	 */
	public static function fromString(string $str, bool $multiple = false){
		if($multiple === true){
			$blocks = [];
			foreach(explode(",", $str) as $b){
				$blocks[] = self::fromString($b, false);
			}

			return $blocks;
		}else{
			$b = explode(":", str_replace([" ", "minecraft:"], ["_", ""], trim($str)));
			if(!isset($b[1])){
				$meta = 0;
			}else{
				$meta = $b[1] & 0xFFFF;
			}

			if(defined(Item::class . "::" . strtoupper($b[0]))){
				$item = self::get(constant(Item::class . "::" . strtoupper($b[0])), $meta);
				if($item->getId() === Item::AIR and strtoupper($b[0]) !== "AIR" and is_numeric($b[0])){
					$item = self::get(((int) $b[0]) & 0xFFFF, $meta);
				}
			}elseif(is_numeric($b[0])){
				$item = self::get(((int) $b[0]) & 0xFFFF, $meta);
			}else{
				$item = self::get(Item::AIR, 0, 0);
			}

			return $item;
		}
	}

	/**
	 * Returns whether the specified item ID is already registered in the item factory.
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function isRegistered(int $id) : bool{
		if($id < 256){
			return BlockFactory::isRegistered($id);
		}
		return self::$list[$id] !== null;
	}
}