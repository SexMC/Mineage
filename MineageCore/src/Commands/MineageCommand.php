<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Commands;

use Mineage\MineageCore\MineageCore;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

abstract class MineageCommand extends Command implements PluginOwned{
	public function __construct(protected MineageCore $core, string $name, Translatable|string $description = "", Translatable|string|null $usage_message = null, array $aliases = []){
		parent::__construct($name, $description, $usage_message, $aliases);
	}

	public function testPlayer(CommandSender $sender) : bool{
		if(!$sender instanceof Player){
			$sender->sendMessage("Please run this command in-game.");
			return false;
		}
		return true;
	}

	public function testPlayerSilent(CommandSender $sender) : bool{
		return $sender instanceof Player;
	}

	public function testAll(CommandSender $sender) : bool{
		if(!$this->testPlayer($sender)){
			return false;
		}
		return $this->testPermission($sender);
	}

	public function testCustomPermission(CommandSender $sender, string $permission) : bool{
		if(!$sender->hasPermission($permission)){
			$sender->sendMessage($sender->getServer()->getLanguage()->translateString(TextFormat::RED . "%commands.generic.permission"));
			return false;
		}
		return true;
	}

	public function testPermissionAndPlayer(CommandSender $sender, string $permission) : bool{
		if(!$sender->hasPermission($permission)){
			$sender->sendMessage($sender->getServer()->getLanguage()->translateString(TextFormat::RED . "%commands.generic.permission"));
			return false;
		}
		return $this->testPlayer($sender);
	}

	public function getOwningPlugin() : Plugin{
		return $this->core;
	}
}
