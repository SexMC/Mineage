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

class FreezeCommand extends ModuleCommand{
	/** @var Moderation */
	protected readonly CoreModule $owner;

	public function __construct(MineageCore $core, CoreModule $owner){
		parent::__construct($core, $owner, "freeze", "Freeze player", "Usage: /freeze <player>", []);
		$this->setPermission(PermissionNodes::MINEAGE_COMMAND_FREEZE);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testAll($sender)){
			return false;
		}
		/** @var Player $sender */

		if($this->owner->getFrozenPlayer($sender) !== null){
			$message = TextFormat::colorize($this->owner->getConfig()->getNested("freeze.cannot-freeze-again"));
			$sender->sendMessage($message);
			return false;
		}

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

		$frozen_by = $this->owner->getFrozenBy($target);
		if($frozen_by !== null){
			$message = TextFormat::colorize($this->owner->getConfig()->getNested("freeze.cannot-freeze-already-frozen"));
			$message = str_replace("@staff", $frozen_by->getName(), $message);
			$sender->sendMessage($message);
			return false;
		}

		if($target === $sender){
			$message = TextFormat::colorize($this->owner->getConfig()->getNested("freeze.cannot-freeze-self"));
			$sender->sendMessage($message);
			return false;
		}

		if($target->hasPermission(PermissionNodes::MINEAGE_FREEZE_BYPASS)){
			$message = TextFormat::colorize($this->owner->getConfig()->getNested("freeze.cannot-freeze-staff"));
			$sender->sendMessage($message);
			return false;
		}

		$this->owner->processFreeze($sender, $target);
		return true;
	}
}
