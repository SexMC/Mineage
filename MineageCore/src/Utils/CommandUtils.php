<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Utils;

use pocketmine\player\Player;

class CommandUtils{
	public static function parseCommandsAndDispatch(Player $player, array $commands, array $replace = []) : void{
		foreach($commands as $command){
			[$type, $command] = explode(":", $command);
			$command = StringUtils::replace($command, $replace);

			if($type === "console"){
				$player->getServer()->getCommandMap()->dispatch(ConsoleUtils::newConsoleCommandSender(), $command);
			}elseif($type === "player"){
				$player->getServer()->getCommandMap()->dispatch($player, $command);
			}
		}
	}
}
