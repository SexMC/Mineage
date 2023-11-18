<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Module;

use Mineage\MineageCore\Commands\MineageCommand;
use Mineage\MineageCore\MineageCore;
use pocketmine\lang\Translatable;

abstract class ModuleCommand extends MineageCommand{
	public function __construct(
		protected MineageCore $core,
		protected readonly CoreModule $owner,
		string $name,
		Translatable|string $description = "",
		Translatable|string|null $usage_message = null,
		array $aliases = []
	){
		parent::__construct($this->core, $name, $description, $usage_message, $aliases);
	}

	public function getCore() : MineageCore{
		return $this->core;
	}

	public function getOwner() : CoreModule{
		return $this->owner;
	}
}
