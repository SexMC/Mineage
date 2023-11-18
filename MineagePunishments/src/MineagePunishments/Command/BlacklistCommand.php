<?php
declare(strict_types=1);

namespace MineagePunishments\Command;

use MineagePunishments\Base;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class BlacklistCommand extends Command{

	public function __construct(private readonly Base $plugin){
		parent::__construct('blacklist', 'Blacklist a player');
		$this->setPermission('mineage.command.blacklist');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		foreach($this->getPermissions() as $permission){
			if(!$sender->hasPermission($permission)){
				return;
			}
		}

		if(!isset($args[0])){
			/*
			TODO: multi-version plugin seems to break custom forms
			if ($sender instanceof Player) {
				$this->plugin->getFormManager()->openBlacklistForm($sender);
			}*/

			$sender->sendMessage(TextFormat::RED . 'Provide a username.');
			return;
		}

		switch($args[0]){
			case 'list':
				$activeBlacklists = $this->plugin->getAdminManager()->getActiveBans(true);
				if(sizeof($activeBlacklists) < 1){
					$sender->sendMessage(TextFormat::RED . 'There are no ongoing blacklists.');
					return;
				}

				$sender->sendMessage('- Ongoing Blacklists -' . TextFormat::EOL . TextFormat::GRAY . implode(', ', array_keys($activeBlacklists)));
				break;
			case 'info':
				if(!isset($args[1])){
					$sender->sendMessage(TextFormat::RED . 'Provide a username.');
					return;
				}

				$player = $this->plugin->getServer()->getPlayerByPrefix($args[1]) ?? $args[1];
				$name = is_string($player) ? $player : $player->getName();
				if(!$this->plugin->getAdminManager()->isBlacklisted($name)){
					$sender->sendMessage(TextFormat::RED . 'The player \'' . $name . '\' is not blacklisted.');
					return;
				}

				$info = $this->plugin->getAdminManager()->getBlacklist($name);
				$sender->sendMessage(
					'- ' . $player . '\'s Punishment Info -' . TextFormat::EOL . TextFormat::GRAY . TextFormat::ITALIC .
					'Punishment issued on ' . date('F j, Y @ g:i a', $info['happened']) . TextFormat::EOL . TextFormat::RESET . TextFormat::WHITE .
					'Reason: ' . $info['reason'] . TextFormat::EOL .
					'Staff: ' . $info['staff']);
				break;
			default:
				if(!isset($args[1])){
					$sender->sendMessage(TextFormat::RED . 'Provide a reason.');
					return;
				}

				$player = $this->plugin->getServer()->getPlayerByPrefix($args[0]) ?? $args[0];
				$silent = isset($args[2]) && strtolower($args[2]) == '-s';
				$this->plugin->getAdminManager()->addActiveBan($sender, is_string($player) ? $player : $player->getName(), $args[1], $sender->getName(), time(), -1, $silent);
				break;
		}
	}
}
