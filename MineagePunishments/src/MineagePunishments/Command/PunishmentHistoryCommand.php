<?php
declare(strict_types=1);

namespace MineagePunishments\Command;

use MineagePunishments\Base;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class PunishmentHistoryCommand extends Command{

	public function __construct(private readonly Base $plugin){
		parent::__construct('check', 'View the punishment history for any player', '', ['c']);
		$this->setPermission('mineage.command.phistory');
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
		$name = $args[0];

		$this->plugin->getNetwork()->executeSelect('mineage.get.history.entry', ['player' => $name], function($rows) use ($sender, $name){
			if(sizeof($rows) < 1){
				$sender->sendMessage(TextFormat::RED . $name . ' has no punishment history.');
				return;
			}

			$message = TextFormat::DARK_GRAY . '-' . TextFormat::GRAY . ' Punishment History for ' . $name . ' ' . TextFormat::DARK_GRAY . '-';
			foreach($rows as $row){
				$message .= TextFormat::EOL . TextFormat::GRAY . '[' . date('F j, Y @ g:i a', $row['PDate']) . '] '
					. TextFormat::AQUA . $row['PType']
					. TextFormat::WHITE . ' (' . $row['Reason'] . ')'
					. TextFormat::GRAY . ' - '
					. TextFormat::DARK_AQUA . $row['Staff'];
			}
			$sender->sendMessage($message);
		});
	}
}
