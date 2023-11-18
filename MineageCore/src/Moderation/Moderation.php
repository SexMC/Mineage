<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Moderation;

use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Moderation\Commands\FreezeCommand;
use Mineage\MineageCore\Moderation\Commands\UnfreezeCommand;
use Mineage\MineageCore\Moderation\Task\UpdateFreezeScoreboardTask;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Permissions\PermissionNodes;
use Mineage\MineageCore\Utils\DefaultConfigData;
use Mineage\MineageCore\Utils\ServerUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

class Moderation extends CoreModule{
	/** @var \WeakMap<Player, PlayerIPInfo> */
	protected \WeakMap $playerInfos;

	/** @var Player[] */
	protected array $frozen_players = [];

	/** @var UpdateFreezeScoreboardTask[] */
	protected array $frozen_scoreboard_tasks = [];

	public function __construct(MineageCore $core){
		parent::__construct(
			core: $core,
			name: "moderation",
			default_config_data: DefaultConfigData::moderation()
		);

		if($this->enabled or $this->bypass_enable_check){
			$this->core->getServer()->getPluginManager()->registerEvents(new ModerationListener($core, $this), $core);
			$this->playerInfos = new \WeakMap();

			$commands = [
				new FreezeCommand($core, $this),
				new UnfreezeCommand($core, $this)
			];
			foreach($commands as $command){
				$this->core->getServer()->getCommandMap()->register("mineage", $command);
			}
		}
	}

	public function getFreezeWhitelistedCommands() : array{
		return $this->getConfig()->getNested("freeze.whitelisted-commands", []);
	}

	public function isFreezeCommandWhitelisted(string $command) : bool{
		return in_array($command, $this->getFreezeWhitelistedCommands());
	}

	public function isFrozen(Player $player) : bool{
		return isset($this->frozen_players[$player->getName()]);
	}

	public function getFrozenBy(Player $player) : ?Player{
		return $this->frozen_players[$player->getName()][1] ?? null;
	}

	public function getFrozenPlayer(Player $sender) : ?Player{
		foreach($this->frozen_players as $frozen_players){
			if($sender === $frozen_players[1]){
				return $frozen_players[0];
			}
		}
		return null;
	}

	public function freezePlayer(Player $player, Player $sender) : void{
		$this->frozen_players[$player->getName()] = [$player, $sender];
	}

	public function unfreezePlayer(Player $player) : void{
		unset($this->frozen_players[$player->getName()]);
	}

	public function isFreezingPlayer(Player $player) : bool{
		return isset($this->frozen_scoreboard_tasks[$player->getName()]);
	}

	public function getFreezeWorld() : ?World{
		return ServerUtils::getWorldByName($this->getConfig()->getNested("freeze.world"));
	}

	public function processFreeze(Player $sender, Player $target){
		$this->freezePlayer($target, $sender);

		$freeze_world = $this->getFreezeWorld();
		if($freeze_world !== null){
			$sender->teleport($freeze_world->getSpawnLocation());
			$target->teleport($freeze_world->getSpawnLocation());
		}

		$player_message = TextFormat::colorize($this->getConfig()->getNested("freeze.freeze-player-message"));
		$player_message = str_replace("@staff", $sender->getName(), $player_message);

		$target->sendMessage($player_message);

		$staff_message = TextFormat::colorize($this->getConfig()->getNested("freeze.freeze-staff-message"));
		$staff_message = str_replace("@player", $target->getName(), $staff_message);

		$sender->sendMessage($staff_message);

		$handler = $this->core->getScheduler()->scheduleRepeatingTask($task = new UpdateFreezeScoreboardTask($this->core, $this, $sender, $target), 20);
		$task->setHandler($handler);

		$this->frozen_scoreboard_tasks[$sender->getName()] = $task;
	}

	public function processUnfreeze(Player $sender, Player $target){
		$this->unfreezePlayer($target);

		$spawn = $sender->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation();

		$sender->teleport($spawn);
		$target->teleport($spawn);

		$player_message = TextFormat::colorize($this->getConfig()->getNested("freeze.unfreeze-player-message"));
		$player_message = str_replace("@staff", $sender->getName(), $player_message);

		$target->sendMessage($player_message);

		$staff_message = TextFormat::colorize($this->getConfig()->getNested("freeze.unfreeze-staff-message"));
		$staff_message = str_replace("@player", $target->getName(), $staff_message);

		$sender->sendMessage($staff_message);

		$this->frozen_scoreboard_tasks[$sender->getName()]->getHandler()->cancel();
		unset($this->frozen_scoreboard_tasks[$sender->getName()]);
	}

	public function processUnfreezeByTargetLogout(Player $sender, Player $target){
		$this->unfreezePlayer($target);

		$spawn = $sender->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation();
		$sender->teleport($spawn);

		$staff_message = TextFormat::colorize($this->getConfig()->getNested("freeze.unfreeze-staff-message-logout"));
		$staff_message = str_replace("@player", $target->getName(), $staff_message);

		$sender->sendMessage($staff_message);

		$this->frozen_scoreboard_tasks[$sender->getName()]->getHandler()->cancel();
		unset($this->frozen_scoreboard_tasks[$sender->getName()]);
	}

	public function processUnfreezeByStaffLogout(Player $sender, Player $target){
		$this->unfreezePlayer($target);

		$spawn = $sender->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation();
		$target->teleport($spawn);

		$player_message = TextFormat::colorize($this->getConfig()->getNested("freeze.unfreeze-player-message-logout"));
		$player_message = str_replace("@staff", $sender->getName(), $player_message);

		$target->sendMessage($player_message);

		$this->frozen_scoreboard_tasks[$sender->getName()]->getHandler()->cancel();
		unset($this->frozen_scoreboard_tasks[$sender->getName()]);
	}

	public function ipInfoFetchCallback(Player $player, PlayerIPInfo $info) : void{
		$kickReason = $this->getConfig()->getNested("antivpn.kick-message");
		if($info->proxy and !$this->core->hasPermissionOrKick($player, PermissionNodes::MINEAGE_ANTIVPN_BYPASS, $kickReason)){
			return;
		}

		$this->playerInfos[$player] = $info;
	}

	public function getPlayerIPInfo(Player $player) : ?PlayerIPInfo{
		return $this->playerInfos[$player] ?? null;
	}
}
