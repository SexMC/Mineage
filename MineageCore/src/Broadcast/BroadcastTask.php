<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Broadcast;

use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Module\ModuleTask;

class BroadcastTask extends ModuleTask{
	/** @var Broadcast */
	protected readonly CoreModule $owner;

	public function __construct(
		MineageCore $core,
		CoreModule $owner,
	){
		parent::__construct($core, $owner);
	}

	public function onRun() : void{
		$this->core->getServer()->broadcastMessage($this->owner->getNextMessage(), $this->core->getServer()->getOnlinePlayers());
	}
}
