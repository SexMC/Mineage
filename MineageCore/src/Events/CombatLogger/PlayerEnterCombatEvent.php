<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Events\CombatLogger;

use Mineage\MineageCore\Events\MineageEvent;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

class PlayerEnterCombatEvent extends MineageEvent implements Cancellable{
	use CancellableTrait;

	public function __construct(
		protected Player $player,
		protected Player $tagged_by,
		protected int $duration_ticks,
		protected bool $was_in_combat_before
	){
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function getTaggedBy() : Player{
		return $this->tagged_by;
	}

	public function getDurationTicks() : int{
		return $this->duration_ticks;
	}

	public function setDurationTicks(int $duration_ticks) : void{
		$this->duration_ticks = $duration_ticks;
	}

	public function wasInCombatBefore() : bool{
		return $this->was_in_combat_before;
	}
}
