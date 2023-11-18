<?php

declare(strict_types=1);

namespace MineagePunishments\Listener;

use lumine\client\event\PlayerFlaggedEvent;
use lumine\client\event\PlayerPunishEvent;
use MineagePunishments\Base;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;

class AnticheatListener implements Listener{

	private string $alertMessage;
	private string $kickMessage;
	private string $banMessage;

	public function __construct(private readonly Base $plugin){
		$this->alertMessage = TextFormat::colorize($this->plugin->getConfig()->getNested("autoclick-alert-message", ""));
		$this->kickMessage = TextFormat::colorize($this->plugin->getConfig()->getNested("autoclick-kick-message", ""));
		$this->banMessage = TextFormat::colorize($this->plugin->getConfig()->getNested("anticheat-broadcast-message", ""));
	}

	public function onFlagged(PlayerFlaggedEvent $event){
		if(strtolower($event->getCategory()) === "autoclicker" && $this->alertMessage !== ""){
			$event->getPlayer()->sendMessage($this->alertMessage);
		}
	}

	public function onPunish(PlayerPunishEvent $event){
		$event->cancel();

		$player = $event->getPlayer();
		if(strtolower($event->getCategory()) === "autoclicker"){
			$player->kick($this->kickMessage);
			return;
		}

		$reason = $this->plugin->getAdminManager()->matchStringToBanReason("cheat");
		$reasonArray = $this->plugin->getAdminManager()->getBanReasons()[$reason];
		$expires = time() + ($reasonArray["Days"] * 86400) + ($reasonArray["Hours"] * 3600);
		$this->plugin->getAdminManager()->addActiveBan(null, $player->getName(), $reason, "Anti-cheat", time(), $expires, true);

		$this->plugin->getServer()->broadcastMessage(TextFormat::colorize(
			str_replace(["@player"], [$player->getName()], $this->banMessage))
		);
	}
}
