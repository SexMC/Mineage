<?php
declare(strict_types=1);

namespace Mineage\MineageCore\CombatLogger;

use Mineage\MineageCore\Events\CombatLogger\PlayerEnterCombatEvent;
use Mineage\MineageCore\Events\CombatLogger\PlayerLeaveCombatEvent;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Module\ModuleListener;
use Mineage\MineageCore\Permissions\PermissionNodes;
use Mineage\MineageCore\Utils\PlayerUtils;
use Mineage\MineageCore\Utils\StringUtils;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class CombatLoggerListener extends ModuleListener{
	/** @var CombatLogger */
	protected readonly CoreModule $owner;

	/**
	 * @priority MONITOR
	 */
	public function onEntityDamage(EntityDamageEvent $event){
		if($event instanceof EntityDamageByEntityEvent){
			$damager = $event->getDamager();
			$target = $event->getEntity();

			if($damager instanceof Player and $target instanceof Player){
				$this->owner->combatTag($damager, $target);
				$this->owner->combatTag($target, $damager);
			}
		}
	}

	public function onPlayerQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();

		if($this->owner->isInCombat($player)){
			$this->removeCombatTag($player);
			$player->kill();
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onPlayerDeath(PlayerDeathEvent $event){
		$player = $event->getPlayer();
		$this->removeCombatTag($player);

		$killer = PlayerUtils::getKiller($player);
		if($killer !== null){
			$this->removeCombatTag($killer);
		}
	}

	public function onPlayerEnterCombatEvent(PlayerEnterCombatEvent $event){
		if(!$event->wasInCombatBefore()){
			$enter_combat_message = $this->owner->getEnterCombatMessage();
			$enter_combat_message = TextFormat::colorize($enter_combat_message);

			$event->getPlayer()->sendMessage(StringUtils::replace($enter_combat_message, ["@player" => $event->getTaggedBy()->getName()]));
		}
	}

	public function onPlayerLeaveCombat(PlayerLeaveCombatEvent $event){
		if($event->getPlayer()->isConnected()){
			$event->getPlayer()->sendMessage(TextFormat::colorize($this->owner->getLeaveCombatMessage()));
		}
	}

	public function onCommandEvent(CommandEvent $event){
		$sender = $event->getSender();

		$command = explode(" ", $event->getCommand())[0];
		if(
			$sender instanceof Player
			and !$sender->hasPermission(PermissionNodes::MINEAGE_COMBATLOGGER_BYPASS)
			and $this->owner->isInCombat($sender)
			and !$this->owner->isCommandWhitelisted($command)
		){
			$sender->sendMessage(TextFormat::colorize($this->owner->getCommandBlockedMessage()));
			$event->cancel();
		}
	}

	protected function removeCombatTag(Player $player){
		$this->owner->removeCombatTag($player);
	}
}
