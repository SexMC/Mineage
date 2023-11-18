<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Commands\Default;

use Mineage\MineageCore\Commands\MineageCommand;
use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Moderation\Moderation;
use Mineage\MineageCore\Permissions\PermissionNodes;
use Mineage\MineageCore\Utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class InfoCommand extends MineageCommand{
	public function __construct(MineageCore $core){
		parent::__construct($core, "info", "See player's info", "/info [player]");
		$this->setPermission(PermissionNodes::MINEAGE_COMMAND_INFO);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		if(!$this->testAll($sender)){
			return;
		}

		/** @var Player $sender */
		if(!isset($args[0])){
			$target = $sender;
		}else{
			$target = $sender->getServer()->getPlayerByPrefix($args[0]);
			if($target === null){
				$message = $this->core->getMessage("info-command.player-not-found", ["@player" => $args[0]]);
				$sender->sendMessage($message);
				return;
			}
		}

		/** @var Moderation $moderation */
		$moderation = $this->core->module_manager->getModuleByName("moderation");
		$playerIpInfo = $moderation->getPlayerIPInfo($target);
		if($playerIpInfo === null){
			$country = $timezone = $proxy = "N/A";
		}else{
			$country = $playerIpInfo->country;
			$timezone = $playerIpInfo->timezone;
			$proxy = $playerIpInfo->proxy ? "yes" : "no";
			if($playerIpInfo->proxy and $target->hasPermission(PermissionNodes::MINEAGE_ANTIVPN_BYPASS)){
				$proxy .= " (allowed to use)";
			}
		}

		$message = $this->core->getMessage("info-command.output", [
			"@player" => $target->getName(),
			"@device" => PlayerUtils::getPlayerDeviceOS($target),
			"@control" => PlayerUtils::getPlayerControl($target),
			"@ip" => $target->getNetworkSession()->getIp(),
			"@country" => $country,
			"@timezone" => $timezone,
			"@proxy" => $proxy
		]);
		$sender->sendMessage($message);
	}
}
