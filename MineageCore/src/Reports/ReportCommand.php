<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Reports;

use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Module\ModuleCommand;
use Mineage\MineageCore\Permissions\PermissionNodes;
use Mineage\MineageCore\Utils\StringUtils;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ReportCommand extends ModuleCommand{
	/** @var Reports */
	protected readonly CoreModule $owner;

	public function __construct(MineageCore $core, Reports $module){
		parent::__construct($core, $module, "report", "Report other players", "/report <player> <reason...>");
		$this->setPermission(PermissionNodes::MINEAGE_COMMAND_DEFAULT);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		if(!$this->testPermission($sender)){
			return;
		}

		if(count($args) < 2){
			throw new InvalidCommandSyntaxException();
		}

		if($sender instanceof Player and ($cooldown = $this->owner->getCooldown($sender)) > 0){
			$message = $this->owner->getConfig()->get("report-cooldown-message");
			$message = TextFormat::colorize(StringUtils::replace($message, ["@cooldown" => $cooldown]));

			$sender->sendMessage($message);
			return;
		}

		$targetName = array_shift($args);
		$target = $sender->getServer()->getPlayerExact($targetName);

		if($sender instanceof Player){
			if($target === null){
				$message = $this->owner->getConfig()->get("report-player-offline");
				$message = TextFormat::colorize(StringUtils::replace($message, ["@player" => $targetName]));

				$sender->sendMessage($message);
				return;
			}

			if($target === $sender){
				$message = $this->owner->getConfig()->get("report-no-self");
				$sender->sendMessage(TextFormat::colorize($message));
				return;
			}
		}

		$reason = implode(" ", $args);
		$this->owner->processReport($sender, $targetName, $reason);
		if($sender instanceof Player){
			$this->owner->setCooldown($sender);
		}
	}
}
