<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Module;

use Mineage\MineageCore\MineageCore;
use pocketmine\event\Listener;

class ModuleListener implements Listener{
	public function __construct(
		protected readonly MineageCore $core,
		protected readonly CoreModule $owner
	){
	}

	public function getCore() : MineageCore{
		return $this->core;
	}

	public function getOwner() : CoreModule{
		return $this->owner;
	}
}
