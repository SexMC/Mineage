<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Chat;

use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Module\ModuleListener;
use Mineage\MineageCore\Permissions\PermissionNodes;
use Mineage\MineageCore\Utils\StringUtils;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\TextFormat;

class ChatListener extends ModuleListener{
	/** @var Chat */
	protected readonly CoreModule $owner;

	public function onPlayerChat(PlayerChatEvent $event){
		$player = $event->getPlayer();
		if(
			($this->owner->isGlobalChatMuted()
				and !$player->hasPermission(PermissionNodes::MINEAGE_GLOBAL_CHAT_MUTE_BYPASS))
		){
			$message = $this->owner->getConfig()->getNested("globalchat.muted");
			$player->sendMessage(TextFormat::colorize($message));

			$event->cancel();
		}

		if(
			!$this->owner->testChatCooldown($player)
			and !$player->hasPermission(PermissionNodes::MINEAGE_CHAT_COOLDOWN_BYPASS)
		){
			$message = $this->owner->getConfig()->getNested("cooldown.message");
			$player->sendMessage(TextFormat::colorize(StringUtils::replace($message, ["@cooldown" => $this->owner->getChatCooldown($player)])));

			$event->cancel();
		}

		$event->setMessage(TextFormat::clean($event->getMessage()));
	}
}
