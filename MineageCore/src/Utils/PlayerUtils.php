<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Utils;

use Mineage\MineageCore\CombatLogger\CombatLogger;
use Mineage\MineageCore\MineageCore;
use pocketmine\entity\Attribute;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use function array_filter;

class PlayerUtils{
	public const OS_LIST = [
		"Unknown",
		"Android",
		"iOS",
		"macOS",
		"FireOS",
		"GearVR",
		"HoloLens",
		"Windows 10",
		"Windows",
		"Dedicated",
		"Orbis",
		"Playstation 4",
		"Nintento Switch",
		"Xbox One"
	];

	public const CONTROLS = [
		"Unknown",
		"Mouse & Keyboard",
		"Touch",
		"Controller"
	];

	public static function getPlayerDeviceOS(Player $player) : string{
		return self::OS_LIST[$player->getPlayerInfo()->getExtraData()["DeviceOS"]] ?? "Unknown";
	}

	public static function getPlayerControl(Player $player) : string{
		return self::CONTROLS[$player->getPlayerInfo()->getExtraData()["CurrentInputMode"]] ?? "Unknown";
	}

	public static function resetPlayer(Player $player) : void{
		$player->getArmorInventory()->clearAll();
		$player->getInventory()->clearAll();

		$player->setSprinting(false);
		$player->setSneaking(false);
		$player->setFlying(false);

		$player->extinguish();
		$player->setAirSupplyTicks($player->getMaxAirSupplyTicks());
		$player->deadTicks = 0;

		$player->getEffects()->clear();
		$player->setHealth($player->getMaxHealth());

		foreach($player->getAttributeMap()->getAll() as $attribute){
			if($attribute->getId() === Attribute::EXPERIENCE or $attribute->getId() === Attribute::EXPERIENCE_LEVEL){
				continue;
			}
			$attribute->resetToDefault();
		}
	}

	public static function getKiller(Player $player) : ?Player{
		$lastDamageCause = $player->getLastDamageCause();
		if(!$lastDamageCause instanceof EntityDamageByEntityEvent){
			/** @var CombatLogger|null $combatLogger */
			$combatLogger = MineageCore::getInstance()->module_manager->getModuleByName('combatlogger');
			if($combatLogger !== null && $combatLogger->isInCombat($player)){
				return $combatLogger->getTagged($player);
			}
			return null;
		}

		$damager = $lastDamageCause->getDamager();
		if(!$damager instanceof Player){
			return null;
		}

		return $damager;
	}

	public static function getPotionCount(Player $player) : int{
		return count(array_filter($player->getInventory()->getContents(), static fn(Item $item) => $item->equals(VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING()))));
	}
}
