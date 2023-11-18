<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Moderation;

use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Module\ModuleListener;
use Mineage\MineageCore\Permissions\PermissionNodes;
use Mineage\MineageCore\Moderation\Task\IPInfoFetchTask;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ModerationListener extends ModuleListener{
	/** @var Moderation */
	protected readonly CoreModule $owner;

	private array $storage = [];

	public function onCommandEvent(CommandEvent $event){
		$sender = $event->getSender();

		$command = explode(" ", $event->getCommand())[0];
		if(
			$sender instanceof Player
			and $this->owner->isFrozen($sender)
			and !$sender->hasPermission(PermissionNodes::MINEAGE_FREEZE_BYPASS)
			and !$this->owner->isFreezeCommandWhitelisted($command)
		){
			$sender->sendMessage(TextFormat::colorize($this->owner->getConfig()->getNested("freeze.command-blocked")));
			$event->cancel();
		}
	}

	public function onPlayerLogin(PlayerLoginEvent $event) : void{
		$ip = $event->getPlayer()->getNetworkSession()->getIp();
		if(isset($this->storage[$ip]) && $this->storage[$ip] >= 2){
			$event->setKickMessage("Too many accounts on this IP address.");
			$event->cancel();
			return;
		}else{
			$this->storage[$ip] = ($this->storage[$ip] ?? 0) + 1;
		}
		$this->core->getServer()->getAsyncPool()->submitTask(new IPInfoFetchTask($event->getPlayer(), $this->owner->ipInfoFetchCallback(...), $this->getCore()->getLogger()));
	}

	public function onPlayerQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();

		$ip = $player->getNetworkSession()->getIp();
		if(isset($this->storage[$ip])){
			$this->storage[$ip]--;
			if($this->storage[$ip] <= 0){
				unset($this->storage[$ip]);
			}
		}

		if($this->owner->isFrozen($player)){
			$frozen_by = $this->owner->getFrozenBy($player);
			$this->owner->processUnfreezeByTargetLogout($frozen_by, $player);
		}

		$player_frozen = $this->owner->getFrozenPlayer($player);
		if($player_frozen !== null){
			$this->owner->processUnfreezeByStaffLogout($player, $player_frozen);
		}
	}

	public function onPlayerChat(PlayerChatEvent $event){
		$player = $event->getPlayer();

		$recipients = $event->getRecipients();
		foreach($recipients as $index => $recipient){
			if(!$recipient instanceof Player){
				continue;
			}
			if($this->owner->isFrozen($recipient) or $this->owner->getFrozenPlayer($recipient) !== null){
				unset($recipients[$index]);
			}
		}
		$event->setRecipients($recipients);

		$frozen_by = $this->owner->getFrozenBy($player);
		if($frozen_by !== null){
			$event->setRecipients([$player, $frozen_by]);
		}

		$frozen_player = $this->owner->getFrozenPlayer($player);
		if($frozen_player !== null){
			$event->setRecipients([$player, $frozen_player]);
		}
	}
}
