<?php
declare(strict_types=1);

namespace MineageBunkersLobby\ChatFormatter;

use pocketmine\player\chat\ChatFormatter;
use pocketmine\utils\TextFormat;

final class MineageChatFormatter implements ChatFormatter{

	//not using this since I'll have to find the player object from this name, then the party, then game, etc.. while EventListener::onChat() does it as well
	public function format(string $username, string $message) : string{
		return TextFormat::GREEN . $username . TextFormat::GRAY . ': ' . TextFormat::WHITE . $message;
	}
}
