<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Kits;

use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Utils\CommandUtils;
use Mineage\MineageCore\Utils\DefaultConfigData;
use Mineage\MineageCore\Utils\EffectParser;
use Mineage\MineageCore\Utils\ItemParser;
use Mineage\MineageCore\Utils\PlayerUtils;
use pocketmine\player\Player;

class Kits extends CoreModule{
	public function __construct(MineageCore $core){
		parent::__construct(
			core: $core,
			name: "kits",
			default_config_data: DefaultConfigData::kits()
		);
	}

	public function kitExists(string $kit) : bool{
		return $this->getConfig()->getNested("kits.$kit", false) !== false;
	}

	public function getKitArmor(string $kit) : array{
		return ItemParser::parse($this->getConfig()->getNested("kits.$kit.armor"));
	}

	public function getKitItems(string $kit) : array{
		return ItemParser::parse($this->getConfig()->getNested("kits.$kit.items"));
	}

	public function getKitEffects(string $kit) : array{
		return EffectParser::parse($this->getConfig()->getNested("kits.$kit.effects"));
	}

	public function getKitCommands(string $kit) : array{
		return $this->getConfig()->getNested("kits.$kit.commands");
	}

	public function equipKitArmor(Player $player, string $kit) : bool{
		if(!$this->kitExists($kit)){
			return false;
		}
		$player->getArmorInventory()->setContents($this->getKitArmor($kit));
		return true;
	}

	public function equipKitItems(Player $player, string $kit) : bool{
		if(!$this->kitExists($kit)){
			return false;
		}
		$player->getInventory()->setContents($this->getKitItems($kit));
		return true;
	}

	public function equipKitEffects(Player $player, string $kit) : bool{
		if(!$this->kitExists($kit)){
			return false;
		}
		foreach($this->getKitEffects($kit) as $effect){
			$player->getEffects()->add($effect);
		}
		return true;
	}

	public function runKitCommands(Player $player, string $kit) : bool{
		if(!$this->kitExists($kit)){
			return false;
		}
		CommandUtils::parseCommandsAndDispatch($player, $this->getKitCommands($kit), ["@player" => $player->getName()]);
		return true;
	}

	public function equipKit(Player $player, string $kit, bool $reset_player = true) : bool{
		if(!$this->kitExists($kit)){
			return false;
		}

		if($reset_player){
			PlayerUtils::resetPlayer($player);
		}

		$this->equipKitArmor($player, $kit);
		$this->equipKitItems($player, $kit);
		$this->equipKitEffects($player, $kit);
		$this->runKitCommands($player, $kit);

		return true;
	}
}
