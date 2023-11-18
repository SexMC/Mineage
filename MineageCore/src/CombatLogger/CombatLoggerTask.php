<?php
declare(strict_types=1);

namespace Mineage\MineageCore\CombatLogger;

use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Module\ModuleTask;
use pocketmine\player\Player;

class CombatLoggerTask extends ModuleTask{
	/** @var CombatLogger */
	protected readonly CoreModule $owner;

	public function __construct(
		MineageCore $core,
		CoreModule $owner,
		private readonly Player $player,
		private readonly Player $tagged
	){
		parent::__construct($core, $owner);
	}

	public function getTagged() : Player{
		return $this->tagged;
	}

	public function onRun() : void{
		$this->owner->removeCombatTag($this->player);
	}
}
