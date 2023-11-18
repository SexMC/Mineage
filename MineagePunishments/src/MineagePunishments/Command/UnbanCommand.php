<?php
declare(strict_types=1);

namespace MineagePunishments\Command;

use MineagePunishments\Base;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class UnbanCommand extends Command{

	public function __construct(private readonly Base $plugin){
		parent::__construct('unban', 'Unban a player');
		$this->setPermission('mineage.command.unban');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		foreach($this->getPermissions() as $permission){
			if(!$sender->hasPermission($permission)){
				return;
			}
		}

		if(!isset($args[0])){
			if($sender instanceof Player){
				$this->plugin->getFormManager()->openActivePunishmentsForm($sender);
			}
			return;
		}

		$player = $this->plugin->getServer()->getPlayerExact($args[0]) ?? $args[0];
		$this->plugin->getAdminManager()->removeActiveBan($sender, is_string($player) ? $player : $player->getName());
	}
}
