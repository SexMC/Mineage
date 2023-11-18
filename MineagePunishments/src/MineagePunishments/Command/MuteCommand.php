<?php
declare(strict_types=1);

namespace MineagePunishments\Command;

use MineagePunishments\Base;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class MuteCommand extends Command{

	public function __construct(private readonly Base $plugin){
		parent::__construct('mute', 'Mute a player');
		$this->setPermission('mineage.command.mute');
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
				$this->plugin->getFormManager()->openMuteForm($sender);
			}*/

			$sender->sendMessage(TextFormat::RED . 'Provide a username.');
			return;
		}

		switch($args[0]){
			case 'reasons':
				$sender->sendMessage('- Available Mute Reasons -' . TextFormat::EOL . TextFormat::GRAY . TextFormat::ITALIC . '(You\'ll have to type it out, caps sensitive)' . TextFormat::RESET . TextFormat::EOL . implode(TextFormat::EOL, array_keys($this->plugin->getAdminManager()->getMuteReasons())));
				break;
			case 'info':
				if(!isset($args[1])){
					$sender->sendMessage(TextFormat::RED . 'Provide a username.');
					return;
				}

				$player = $this->plugin->getServer()->getPlayerByPrefix($args[1]) ?? $args[1];
				$name = is_string($player) ? $player : $player->getName();
				if(!$this->plugin->getAdminManager()->isMuted($name)){
					$sender->sendMessage(TextFormat::RED . 'The player \'' . $name . '\' is not muted.');
					return;
				}

				$info = $this->plugin->getAdminManager()->getMute($name);
				$sender->sendMessage(
					'- ' . $player . '\'s Punishment Info -' . TextFormat::EOL . TextFormat::GRAY . TextFormat::ITALIC .
					'Punishment issued on ' . date('F j, Y @ g:i a', $info['happened']) . TextFormat::EOL . TextFormat::RESET . TextFormat::WHITE .
					'Reason: ' . $info['reason'] . TextFormat::EOL .
					'Staff: ' . $info['staff'] . TextFormat::EOL .
					($info['expires'] == -1 ? '' : 'Expires on: ' . date('F j, Y @ g:i a', $info['expires'])));
				break;
			case 'list':
				$activeMutes = $this->plugin->getAdminManager()->getActiveMutes();
				if(sizeof($activeMutes) < 1){
					$sender->sendMessage(TextFormat::RED . 'There are no ongoing mutes.');
					return;
				}

				$sender->sendMessage('- Ongoing Mutes -' . TextFormat::EOL . TextFormat::GRAY . implode(', ', array_keys($activeMutes)));
				break;
			default:
				if(!isset($args[1])){
					$sender->sendMessage(TextFormat::RED . 'Provide a reason, refer to /' . $this->getName() . ' reasons.');
					return;
				}

				$muteReason = $this->plugin->getAdminManager()->matchStringToMuteReason($args[1]);
				if($muteReason === null){
					$sender->sendMessage(TextFormat::RED . 'Provide an existing reason, refer to /' . $this->getName() . ' reasons.');
					return;
				}

				$player = $this->plugin->getServer()->getPlayerByPrefix($args[0]) ?? $args[0];
				$reasonArray = $this->plugin->getAdminManager()->getMuteReasons()[$muteReason];
				$expires = time() + ($reasonArray['Days'] * 86400) + ($reasonArray['Hours'] * 3600);
				$this->plugin->getAdminManager()->addActiveMute($sender, is_string($player) ? $player : $player->getName(), $muteReason, $sender->getName(), time(), $expires);
				break;
		}
	}
}
