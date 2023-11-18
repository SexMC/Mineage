<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Utils;

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\TextFormat;

class ItemParser{
	/**
	 * @param string[]|string $items
	 *
	 * @return Item[]
	 */
	public static function parse(array|string $items) : array{
		$items = is_string($items) ? [$items] : $items;
		$output_items = [];

		foreach($items as $item){
			if($item instanceof Item or !is_string($item)){
				continue;
			}

			$parts = explode(":", $item);

			$itemName = array_shift($parts);
			$count = (int) array_shift($parts);
			$name = array_shift($parts);
			$lore = array_shift($parts);

			$item = StringToItemParser::getInstance()->parse($itemName);
			if($item === null or $item->isNull()){
				continue;
			}

			$item->setCount($count);

			if(isset($name) and strtolower($name) !== "default"){
				$item->setCustomName(TextFormat::colorize($name));
			}

			if(isset($lore) and $lore !== ""){
				$item->setLore(explode(";", TextFormat::colorize($lore)));
			}

			foreach(self::parseEnchantments(implode(":", $parts)) as $enchantment){
				$item->addEnchantment($enchantment);
				if($item instanceof Durable and $enchantment->getType() === VanillaEnchantments::UNBREAKING() and $enchantment->getLevel() > $enchantment->getType()->getMaxLevel()){
					$item->setUnbreakable();
				}
			}

			$output_items[] = $item;
		}

		return $output_items;
	}

	/**
	 * @param string $enchantments
	 *
	 * @return EnchantmentInstance[]
	 */
	public static function parseEnchantments(string $enchantments) : array{
		$last_enchantment = null;
		$output = [];

		$parts = explode(":", $enchantments);

		foreach($parts as $index => $part){
			if((++$index % 2) !== 0){
				$last_enchantment = StringToEnchantmentParser::getInstance()->parse($part) ?? EnchantmentIdMap::getInstance()->fromId((int) $part);
				continue;
			}

			if($last_enchantment !== null){
				$output[] = new EnchantmentInstance($last_enchantment, (int) $part);
			}
		}

		return $output;
	}
}
