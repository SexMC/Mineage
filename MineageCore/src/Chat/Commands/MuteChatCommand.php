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

class MuteChatCommand extends ModuleCommand{
	/** @var Chat */
	protected readonly CoreModule $owner;

	public function __construct(MineageCore $core, CoreModule $owner){
		parent::__construct($core, $owner, "mutechat", "Mutes global chat", "Usage: /mutechat", []);
		$this->setPermission(PermissionNodes::MINEAGE_GLOBAL_CHAT_MUTE_BYPASS);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return false;
		}

		if($this->owner->isGlobalChatMuted()){
			$message = $this->owner->getConfig()->getNested("globalchat.unmute");
			$this->owner->unmuteGlobalChat();
		}else{
			$message = $this->owner->getConfig()->getNested("globalchat.mute");
			$this->owner->muteGlobalChat();
		}


		$this->core->getServer()->broadcastMessage(
			TextFormat::colorize(
				StringUtils::replace(
					$message, [
						"@sender" => $sender->getName(),
					]
				)
			),
			$this->core->getServer()->getOnlinePlayers()
		);
		return true;
	}
}
