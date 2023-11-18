<?php
declare(strict_types=1);

namespace MineagePunishments\Command;

use MineagePunishments\Base;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class AliasCommand extends Command{

	public function __construct(private readonly Base $plugin){
		parent::__construct('alias', 'Alias a player');
		$this->setPermission('mineage.command.alias');
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

		//TODO: alias offline players
		$firstArg = str_replace('@', '', $args[0]);
		$player = $this->plugin->getServer()->getPlayerByPrefix($firstArg);
		if($player === null){
			$sender->sendMessage(TextFormat::RED . 'The player ' . TextFormat::DARK_AQUA . $firstArg . TextFormat::RED . ' could not be found.');
			return;
		}

		$this->plugin->getNetwork()?->executeSelect('mineage.get.aliases', [], function($rows) use ($sender, $player){
			$name = $player->getName();
			if(sizeof($rows) < 1){
				$sender->sendMessage(TextFormat::RED . 'There are no tracked aliases.');
				return;
			}

			$playerInfo = $player->getPlayerInfo();

			foreach($rows as $row){
				foreach(explode(',', $row['IP']) as $value){
					if($row['Player'] != $name and $value == $player->getNetworkSession()->getIp()){
						if($this->plugin->getAdminManager()->isBlacklisted($row['Player'])){
							$ipAliases[] = TextFormat::DARK_RED . $row['Player'] . TextFormat::WHITE;
						}elseif($this->plugin->getAdminManager()->isBanned($row['Player'])){
							$ipAliases[] = TextFormat::RED . $row['Player'] . TextFormat::WHITE;
						}elseif($this->plugin->getAdminManager()->isMuted($row['Player'])){
							$ipAliases[] = TextFormat::GOLD . $row['Player'] . TextFormat::WHITE;
						}else{
							$ipAliases[] = $row['Player'];
						}
					}
				}

				foreach(explode(',', $row['DID']) as $value){
					if($row['Player'] != $name and $value == $playerInfo->getExtraData()['DeviceId']){
						if($this->plugin->getAdminManager()->isBlacklisted($row['Player'])){
							$didAliases[] = TextFormat::DARK_RED . $row['Player'] . TextFormat::WHITE;
						}elseif($this->plugin->getAdminManager()->isBanned($row['Player'])){
							$didAliases[] = TextFormat::RED . $row['Player'] . TextFormat::WHITE;
						}elseif($this->plugin->getAdminManager()->isMuted($row['Player'])){
							$didAliases[] = TextFormat::GOLD . $row['Player'] . TextFormat::WHITE;
						}else{
							$didAliases[] = $row['Player'];
						}
					}
				}

				foreach(explode(',', $row['CID']) as $value){
					if($row['Player'] != $name and $value == $playerInfo->getExtraData()['ClientRandomId']){
						if($this->plugin->getAdminManager()->isBlacklisted($row['Player'])){
							$cidAliases[] = TextFormat::DARK_RED . $row['Player'] . TextFormat::WHITE;
						}elseif($this->plugin->getAdminManager()->isBanned($row['Player'])){
							$cidAliases[] = TextFormat::RED . $row['Player'] . TextFormat::WHITE;
						}elseif($this->plugin->getAdminManager()->isMuted($row['Player'])){
							$cidAliases[] = TextFormat::GOLD . $row['Player'] . TextFormat::WHITE;
						}else{
							$cidAliases[] = $row['Player'];
						}
					}
				}
			}

			$sender->sendMessage('- ' . $name . '\'s Aliases -' . TextFormat::EOL .
				'IP: ' . (empty($ipAliases) ? '(Empty)' : implode(', ', $ipAliases)) . TextFormat::EOL .
				'DID: ' . (empty($didAliases) ? '(Empty)' : implode(', ', $didAliases)) . TextFormat::EOL .
				'CID: ' . (empty($cidAliases) ? '(Empty)' : implode(', ', $cidAliases)));
		});
	}
}
