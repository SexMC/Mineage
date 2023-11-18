<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Chat\Commands;

use Mineage\MineageCore\Chat\Chat;
use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Module\ModuleCommand;
use Mineage\MineageCore\Permissions\PermissionNodes;
use Mineage\MineageCore\Utils\ServerUtils;
use pocketmine\command\CommandSender;

class StaffChatCommand extends ModuleCommand{
	/** @var Chat */
	protected readonly CoreModule $owner;

	public function __construct(MineageCore $core, CoreModule $owner){
		parent::__construct($core, $owner, "staffchat", "ability to use staff chat", "Usage: /staffchat <message...>", ["sc"]);
		$this->setPermission(PermissionNodes::MINEAGE_COMMAND_STAFFCHAT);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return false;
		}

		if(count($args) === 0){
			$sender->sendMessage($this->getUsage());
			return false;
		}

		$message = implode(" ", $args);

		$format = $this->owner->getStaffChatFormat();
		$format = str_replace(["@player", "@message"], [$sender->getName(), $message], $format);

		ServerUtils::sendMessageToOnlineStaff($format);
		return true;
	}
}
