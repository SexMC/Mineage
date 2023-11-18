<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Commands\Default;

use Mineage\MineageCore\Commands\MineageCommand;
use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Permissions\PermissionNodes;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class PingCommand extends MineageCommand{
	public function __construct(MineageCore $core){
		parent::__construct($core, "ping", "Check player pings", "Usage: /ping [player]", []);
		$this->setPermission(PermissionNodes::MINEAGE_COMMAND_PING);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testAll($sender)){
			return false;
		}

		/** @var Player $sender */

		$player = $args[0] ?? $sender->getName();
		$player = $sender->getServer()->getPlayerByPrefix($player);

		if($player === null){
			$message = $this->core->getMessage("ping-command.player-not-found", ["@player" => $args[0]]);
			$sender->sendMessage($message);
			return false;
		}

		if($player !== $sender){
			$message = $this->core->getMessage("ping-command.ping-other", [
				"@ping" => $player->getNetworkSession()->getPing(),
				"@player" => $player->getName()
			]);
			$sender->sendMessage($message);
			return true;
		}

		$message = $this->core->getMessage("ping-command.ping-self", [
			"@ping" => $player->getNetworkSession()->getPing(),
		]);
		$sender->sendMessage($message);
		return true;
	}
}
