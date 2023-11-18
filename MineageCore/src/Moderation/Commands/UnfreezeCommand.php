<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Moderation\Commands;

use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Moderation\Moderation;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Module\ModuleCommand;
use Mineage\MineageCore\Permissions\PermissionNodes;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class UnfreezeCommand extends ModuleCommand{
	/** @var Moderation */
	protected readonly CoreModule $owner;

	public function __construct(MineageCore $core, CoreModule $owner){
		parent::__construct($core, $owner, "unfreeze", "Unfreeze player", "Usage: /unfreeze <player>", []);
		$this->setPermission(PermissionNodes::MINEAGE_COMMAND_UNFREEZE);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testAll($sender)){
			return false;
		}
		/** @var Player $sender */
		if(count($args) === 0){
			$sender->sendMessage($this->getUsage());
			return false;
		}

		$target = $sender->getServer()->getPlayerByPrefix($args[0]);
		if($target === null){
			$message = TextFormat::colorize($this->owner->getConfig()->getNested("freeze.player-not-found"));
			$message = str_replace("@player", $args[0], $message);
			$sender->sendMessage($message);
			return false;
		}

		if($target === $sender){
			$message = TextFormat::colorize($this->owner->getConfig()->getNested("freeze.cannot-unfreeze-self"));
			$sender->sendMessage($message);
			return false;
		}

		if(!$this->owner->isFrozen($target)){
			$message = TextFormat::colorize($this->owner->getConfig()->getNested("freeze.cannot-unfreeze-not-frozen"));
			$sender->sendMessage($message);
			return false;
		}

		$frozen_by = $this->owner->getFrozenBy($target);
		if($this->owner->getFrozenBy($target) !== $sender){
			$message = TextFormat::colorize($this->owner->getConfig()->getNested("freeze.cannot-unfreeze-not-frozen-by"));
			$message = str_replace("@staff", $frozen_by->getName(), $message);
			$sender->sendMessage($message);
			return false;
		}

		$this->owner->processUnfreeze($sender, $target);
		return true;
	}
}
