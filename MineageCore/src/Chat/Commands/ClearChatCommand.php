<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Chat\Commands;

use Mineage\MineageCore\Chat\Chat;
use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Module\ModuleCommand;
use Mineage\MineageCore\Permissions\PermissionNodes;
use Mineage\MineageCore\Utils\StringUtils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ClearChatCommand extends ModuleCommand{
	/** @var Chat */
	protected readonly CoreModule $owner;

	public function __construct(MineageCore $core, CoreModule $owner){
		parent::__construct($core, $owner, "clearchat", "Clears chat", "Usage: /clearchat", []);
		$this->setPermission(PermissionNodes::MINEAGE_COMMAND_CLEARCHAT);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return false;
		}

		$this->owner->clearChat();
		$this->core->getServer()->broadcastMessage(
			TextFormat::colorize(
				StringUtils::replace(
					$this->owner->getConfig()->getNested("clearchat.clear"), [
						"@sender" => $sender->getName()
					]
				)
			),
			$this->core->getServer()->getOnlinePlayers()
		);
		return true;
	}
}
