<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Module;

use Mineage\MineageCore\Broadcast\Broadcast;
use Mineage\MineageCore\Chat\Chat;
use Mineage\MineageCore\CombatLogger\CombatLogger;
use Mineage\MineageCore\Discord\Discord;
use Mineage\MineageCore\Forms\Forms;
use Mineage\MineageCore\Hotbar\Hotbar;
use Mineage\MineageCore\Kits\Kits;
use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Moderation\Moderation;
use Mineage\MineageCore\Reports\Reports;
use Mineage\MineageCore\Worlds\Worlds;

class ModuleManager{
	/** @var CoreModule[] */
	protected array $modules = [];

	public function __construct(MineageCore $core){
		$default_modules = [
			new Broadcast($core),
			new Chat($core),
			new CombatLogger($core),
			new Discord($core),
			new Forms($core),
			new Hotbar($core),
			new Kits($core),
			new Moderation($core),
			new Reports($core),
			new Worlds($core)
		];

		foreach($default_modules as $module){
			$this->loadModule($module);
		}
	}

	public function onEnable() : void{
		foreach($this->modules as $module){
			if($module->isEnabled()){
				$module->onEnable();
			}
		}
	}

	public function onDisable() : void{
		foreach($this->modules as $module){
			if($module->isEnabled()){
				$module->onDisable();
			}
		}
	}

	public function getModuleByName(string $module) : ?CoreModule{
		return $this->modules[$module] ?? null;
	}

	public function loadModule(CoreModule $module) : void{
		$this->modules[$module->getName()] = $module;
	}

	public function unloadModule(CoreModule $module) : void{
		$module->setEnabled(false);
		unset($this->modules[$module->getName()]);
	}
}
