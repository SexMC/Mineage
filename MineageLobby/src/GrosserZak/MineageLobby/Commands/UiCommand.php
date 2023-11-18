<?php
declare(strict_types=1);

namespace GrosserZak\MineageLobby\Commands;

use GrosserZak\MineageLobby\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;

class UiCommand extends Command implements PluginOwned {

    public function __construct(
    ) {
        parent::__construct("ui", "Open the Server Selector UI", "/ui");
        $this->setPermission("ui.open");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
        if(!$sender instanceof Player) {
            $sender->sendMessage("You must be in-game to use this command!");
            return false;
        }
        if(!$this->testPermission($sender)) {
            return false;
        }
        Main::getInstance()->openServerSelectorUI($sender);
        return true;
    }

    public function getOwningPlugin() : Main {
        return Main::getInstance();
    }
}
