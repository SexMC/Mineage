<?php
declare(strict_types=1);

namespace MineagePunishments\Command;

use MineagePunishments\Base;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ACBanCommand extends Command{

	public function __construct(private readonly Base $plugin){
		parent::__construct('acban', 'Anti-cheat ban a player');
		$this->setPermission('mineage.command.acban');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		foreach($this->getPermissions() as $permission){
			if(!$sender->hasPermission($permission)){
				return;
			}
		}

		if(!isset($args[0])){
			$sender->sendMessage(TextFormat::RED . 'Provide a username.');
			return;
		}

		switch($args[0]){
			default:
				$player = $this->plugin->getServer()->getPlayerByPrefix($args[0]) ?? $args[0];
				$reason = $this->plugin->getAdminManager()->matchStringToBanReason("cheat");
				$reasonArray = $this->plugin->getAdminManager()->getBanReasons()[$reason];
				$expires = time() + ($reasonArray['Days'] * 86400) + ($reasonArray['Hours'] * 3600);
				$this->plugin->getAdminManager()->addActiveBan($sender, is_string($player) ? $player : $player->getName(), $reason, "Anti-cheat", time(), $expires, true);

				$this->plugin->getServer()->broadcastMessage(TextFormat::colorize(
					str_replace(["@player"], [is_string($player) ? $player : $player->getName()], TextFormat::colorize($this->plugin->getConfig()->getNested("anticheat-broadcast-message", ""))))
				);
				break;
		}
	}
}
