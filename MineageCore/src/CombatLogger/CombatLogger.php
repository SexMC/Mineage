<?php
declare(strict_types=1);

namespace Mineage\MineageCore\CombatLogger;

use Mineage\MineageCore\Events\CombatLogger\PlayerEnterCombatEvent;
use Mineage\MineageCore\Events\CombatLogger\PlayerLeaveCombatEvent;
use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Utils\DefaultConfigData;
use pocketmine\player\Player;
use WeakMap;

class CombatLogger extends CoreModule{
	/** @var WeakMap<Player, CombatLoggerTask> */
	protected WeakMap $combat_tasks;

	public function __construct(MineageCore $core){
		parent::__construct(
			core: $core,
			name: "combatlogger",
			default_config_data: DefaultConfigData::combatLogger()
		);

		if($this->enabled or $this->bypass_enable_check){
			$this->core->getServer()->getPluginManager()->registerEvents(new CombatLoggerListener($core, $this), $core);
		}

		$this->combat_tasks = new WeakMap();
	}

	public function getCombatDurationTicks() : int{
		return $this->getConfig()->get("combat-duration-ticks", 300);
	}

	public function getWhitelistedCommands() : array{
		return $this->getConfig()->get("whitelisted-commands", []);
	}

	public function isCommandWhitelisted(string $command) : bool{
		return in_array($command, $this->getWhitelistedCommands());
	}

	public function getEnterCombatMessage() : string{
		return $this->getConfig()->get("enter-combat-message", "");
	}

	public function getLeaveCombatMessage() : string{
		return $this->getConfig()->get("leave-combat-message", "");
	}

	public function getCommandBlockedMessage() : string{
		return $this->getConfig()->get("command-blocked-message", "");
	}

	public function isInCombat(Player $player) : bool{
		return isset($this->combat_tasks[$player]);
	}

	public function getTagged(Player $player) : ?Player{
		return $this->combat_tasks[$player]?->getTagged();
	}

	public function combatTag(Player $damager, Player $tagged_by, int $duration = -1) : void{
		$old_duration = null;
		if($was_in_combat_before = isset($this->combat_tasks[$damager])){
			$old_duration = $this->combat_tasks[$damager]->getHandler()->getDelay();
		}
		$duration = $duration == -1 ? ($old_duration ?? $this->getCombatDurationTicks()) : $duration;
		$event = new PlayerEnterCombatEvent(
			$damager,
			$tagged_by,
			$duration,
			$was_in_combat_before
		);
		$event->call();

		$old_duration ??= -1;

		if(!$event->isCancelled()){
			if($duration >= $old_duration){
				if($was_in_combat_before){
					$this->combat_tasks[$damager]->getHandler()->cancel();
				}
				$handler = $this->core->getScheduler()->scheduleDelayedTask(
					$task = new CombatLoggerTask($this->core, $this, $damager, $tagged_by),
					$duration
				);
				$task->setHandler($handler);
				$this->combat_tasks[$damager] = $task;
			}
		}
	}

	public function removeCombatTag(Player $player){
		$event = new PlayerLeaveCombatEvent($player);
		$event->call();
		if(!$event->isCancelled() and isset($this->combat_tasks[$player])){
			$this->combat_tasks[$player]->getHandler()?->cancel();
			unset($this->combat_tasks[$player]);
		}
	}
}
