<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Commands\Default;

use Mineage\MineageCore\Commands\MineageCommand;
use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Permissions\PermissionNodes;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class HubCommand extends MineageCommand{
	public function __construct(MineageCore $core){
		parent::__construct($core, "hub", "Teleport to the hub", "/hub", ["spawn"]);
		$this->setPermission(PermissionNodes::MINEAGE_COMMAND_HUB);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testAll($sender)){
			return false;
		}

		/** @var Player $sender */

		$sender->teleport($sender->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
		$sender->sendMessage($this->core->getMessage("hub-command.success"));
		return true;
	}
}
