<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Utils;

use pocketmine\console\ConsoleCommandSender;
use pocketmine\lang\Language;
use pocketmine\Server;

class ConsoleUtils{
	public static function newConsoleCommandSender() : ConsoleCommandSender{
		return new ConsoleCommandSender(Server::getInstance(), new Language(Language::FALLBACK_LANGUAGE));
	}

	public static function dispatchCommandAsConsole(string $command) : void{
		Server::getInstance()->dispatchCommand(self::newConsoleCommandSender(), $command);
	}
}
