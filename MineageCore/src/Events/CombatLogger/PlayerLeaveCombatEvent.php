<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Events\CombatLogger;

use Mineage\MineageCore\Events\MineageEvent;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

class PlayerLeaveCombatEvent extends MineageEvent implements Cancellable{
	use CancellableTrait;

	public function __construct(
		protected Player $player,
	){
	}

	public function getPlayer() : Player{
		return $this->player;
	}
}
