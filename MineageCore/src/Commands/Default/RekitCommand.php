<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Commands\Default;

use Mineage\MineageCore\Commands\MineageCommand;
use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Permissions\PermissionNodes;
use Mineage\MineageCore\Worlds\Worlds;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class RekitCommand extends MineageCommand{
	public function __construct(MineageCore $core){
		parent::__construct($core, "rekit", "Equip world kit again", "Usage: /rekit", []);
		$this->setPermission(PermissionNodes::MINEAGE_COMMAND_REKIT);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testAll($sender)){
			return false;
		}

		/** @var Player $sender */

		$world = $sender->getWorld()->getFolderName();

		/** @var Worlds $worlds */
		$worlds = $this->core->module_manager->getModuleByName("worlds");
		if($worlds->getWorldKit($world) === null){
			return false;
		}

		if(!$worlds->allowRekit($world)){
			return false;
		}

		$worlds->equipWorldKit($sender, $world);
		return true;
	}
}
