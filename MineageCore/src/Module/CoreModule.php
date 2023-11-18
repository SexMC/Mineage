<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Module;

use Mineage\MineageCore\MineageCore;
use pocketmine\utils\Config;
use function file_exists;

abstract class CoreModule{
	private ?Config $config = null;
	/** @var bool */
	public bool $enabled = true;

	public function __construct(
		public readonly MineageCore $core,
		public readonly string $name = "core",
		protected readonly bool $create_config = true,
		protected readonly array $default_config_data = [],
		protected readonly bool $bypass_enable_check = false
	){
		$this->core->getLogger()->debug("{$this->name} module enabled");
		$this->load();
	}

	public function load() : void{
		if($this->create_config){
			if(!file_exists($this->core->getDataFolder() . "{$this->name}.yml")){
				$this->config = new Config($this->core->getDataFolder() . "{$this->name}.yml", Config::YAML, $this->default_config_data);
			}else{
				$this->config = new Config($this->core->getDataFolder() . "{$this->name}.yml", Config::YAML);
			}

			if(!$this->config->exists("enable")){
				$this->config->set("enable");
			}

			$this->config->save();
			if($this->bypass_enable_check or $this->config->get("enable")){
				$this->enabled = true;
			}
		}
	}

	public function onEnable() : void{
	}

	public function onDisable() : void{
	}

	public function reload() : void{
		$this->getConfig()?->reload();
	}

	public function getName() : string{
		return $this->name;
	}

	public function getConfig() : ?Config{
		return $this->config;
	}

	public function isEnabled() : bool{
		return $this->enabled;
	}

	public function setEnabled(bool $value) : void{
		$this->enabled = $value;
	}
}
