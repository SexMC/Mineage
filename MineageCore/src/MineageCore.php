<?php
declare(strict_types=1);

namespace Mineage\MineageCore;

use IvanCraft623\RankSystem\RankSystem;
use Mineage\MineageCore\Commands\Default\HubCommand;
use Mineage\MineageCore\Commands\Default\InfoCommand;
use Mineage\MineageCore\Commands\Default\PingCommand;
use Mineage\MineageCore\Commands\Default\RekitCommand;
use Mineage\MineageCore\Module\ModuleManager;
use Mineage\MineageCore\Permissions\PermissionManager;
use Mineage\MineageCore\Utils\DefaultConfigData;
use Mineage\MineageCore\Utils\StringUtils;
use Mineage\MineageCore\Worlds\VoidGenerator;
use pocketmine\block\ChemistryTable;
use pocketmine\block\Element;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\CreativeInventory;
use pocketmine\lang\Translatable;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\PermissionManager as PMPermissionManager;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\generator\GeneratorManager;

class MineageCore extends PluginBase{
	private static ?MineageCore $instance = null;

	public static function getInstance() : ?MineageCore{
		return self::$instance;
	}

	public readonly ModuleManager $module_manager;
	public readonly PermissionManager $permission_manager;

	public readonly Config $messages;

	public function onLoad() : void{
		while(!self::$instance instanceof $this){
			self::$instance = $this;
		}
		GeneratorManager::getInstance()->addGenerator(VoidGenerator::class, "void", fn() => null, true);
	}

	protected function onEnable() : void{
		$this->loadConfigs();
		$this->loadManagers();
		$this->loadListeners();
		$this->loadCommands();

		$this->unregisterCreativeChemistryItems();
		$this->restrictSensitivePermissions();
	}

	public function onDisable() : void{
		$this->module_manager->onDisable();
	}

	public function loadConfigs(){
		$this->messages = new Config(
			file: $this->getDataFolder() . "messages.yml",
			default: DefaultConfigData::messages()
		);
	}

	public function loadManagers(){
		$this->permission_manager = new PermissionManager();
		$this->permission_manager->registerDefaultPermissions();

		$this->module_manager = new ModuleManager($this);
		$this->module_manager->onEnable();
	}

	public function loadListeners(){
	}

	public function loadCommands(){
		$default_commands = [
			new HubCommand($this),
			new InfoCommand($this),
			new PingCommand($this),
			new RekitCommand($this),
		];

		foreach($default_commands as $default_command){
			$this->getServer()->getCommandMap()->register("mineage", $default_command);
		}
	}

	public function getMessage(string $nested, array $replace = []) : ?string{
		return TextFormat::colorize(StringUtils::replace($this->messages->getNested($nested, ""), $replace));
	}

	public function getRankSystem() : ?RankSystem{
		return $this->getServer()->getPluginManager()->getPlugin("RankSystem");
	}

	private function unregisterCreativeChemistryItems() : void{
		$creativeInventory = CreativeInventory::getInstance();

		$creativeInventory->remove(VanillaBlocks::ELEMENT_ZERO()->asItem());
		$creativeInventory->remove(VanillaBlocks::CHEMICAL_HEAT()->asItem());

		foreach(VanillaBlocks::getAll() as $block){
			if($block instanceof ChemistryTable or $block instanceof Element){
				$creativeInventory->remove($block->asItem());
			}
		}
	}

	private function restrictSensitivePermissions() : void{
		$everyoneRoot = PMPermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_USER);
		$operatorRoot = PMPermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR);
		if($everyoneRoot === null or $operatorRoot === null){
			return;
		}

		foreach([
					DefaultPermissionNames::COMMAND_CLEAR_SELF,
					DefaultPermissionNames::COMMAND_KILL_SELF,
					DefaultPermissionNames::COMMAND_ME,
					DefaultPermissionNames::COMMAND_VERSION,
				] as $permissionName){
			$everyoneRoot->removeChild($permissionName);
			$operatorRoot->addChild($permissionName, true);
		}
	}

	public function hasPermissionOrKick(Player $player, string $permission, Translatable|string $kickReason) : bool{
		if($player->hasPermission($permission)){
			return true;
		}

		$rankSystemSession = $this->getRankSystem()?->getSessionManager()->get($player);
		if($rankSystemSession === null){
			$player->kick($kickReason);
			return false;
		}

		if($rankSystemSession->isInitialized()){
			if(!$rankSystemSession->hasPermission($permission)){
				$player->kick($kickReason);
				return false;
			}else{
				return true;
			}
		}else{
			$rankSystemSession->onInitialize(static function() use($player, $permission, $rankSystemSession, $kickReason) : void{
				if(!$rankSystemSession->hasPermission($permission)){
					$player->kick($kickReason);
				}
			});
			return true; // We can't know yet
		}
	}
}
