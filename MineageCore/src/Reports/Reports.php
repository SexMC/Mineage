<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Reports;

use Mineage\MineageCore\Discord\Discord;
use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Permissions\PermissionNodes;
use Mineage\MineageCore\Utils\DefaultConfigData;
use Mineage\MineageCore\Utils\ServerUtils;
use Mineage\MineageCore\Utils\StringUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class Reports extends CoreModule{
	protected array $cooldown;

	public function __construct(MineageCore $core){
		parent::__construct(
			core: $core,
			name: "reports",
			default_config_data: DefaultConfigData::reports()
		);

		$this->cooldown = [];
		$this->core->getServer()->getCommandMap()->register("mineage", new ReportCommand($core, $this));
	}

	public function setCooldown(Player $player, ?int $cooldown = null) : void{
		$cooldown = $cooldown ?? $this->getConfig()->get("report-cooldown", 60);
		$this->cooldown[$player->getName()] = time() + $cooldown;
	}

	public function getCooldown(Player $player) : ?int{
		$name = $player->getName();
		if(!isset($this->cooldown[$name])){
			return 0;
		}
		return $this->cooldown[$name] - time();
	}

	public function testCooldown(Player $player) : bool{
		$cooldown = $this->getConfig()->get("report-cooldown", 60);
		$name = $player->getName();

		if(!$this->cooldown[$name]){
			$this->cooldown[$name] = time() + $cooldown;
			return true;
		}

		if($this->cooldown[$name] - time() <= 0){
			unset($this->cooldown[$name]);
			return true;
		}
		return false;
	}


	public function processReport(CommandSender $origin, string $player, string $reason) : void{
		$config = $this->getConfig();
		$replaces = [
			"@origin" => $origin->getName(),
			"@player" => $player,
			"@reason" => $reason
		];

		if($config->get("log-to-discord", true)){
			/** @var Discord $discord */
			$discord = $this->core->module_manager->getModuleByName("discord");
			$discord?->sendRaw($config->get("report-discord-message", []), $replaces);
		}

		$report_confirmation = $config->get("report-confirmation");
		$report_to_staff = $config->get("report-to-staff");

		$report_confirmation = TextFormat::colorize(StringUtils::replace($report_confirmation, $replaces));
		$report_to_staff = TextFormat::colorize(StringUtils::replace($report_to_staff, $replaces));

		$origin->sendMessage($report_confirmation);

		ServerUtils::sendMessageToOnlinePlayersWithPermissions($report_to_staff, [
			PermissionNodes::MINEAGE_CHANNEL_REPORTS,
			PermissionNodes::MINEAGE_CHANNEL_STAFF
		]);
	}
}
