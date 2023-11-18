<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Worlds;

use Mineage\MineageCore\Kits\Kits;
use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Permissions\PermissionNodes;
use Mineage\MineageCore\Utils\DefaultConfigData;
use Mineage\MineageCore\Utils\ServerUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

class Worlds extends CoreModule{
	public const WORLD_PLAYER_LIMIT_UNLIMITED = -1;

	public const JOIN_WORLD_FAILED_WORLD_INVALID = 0;
	public const JOIN_WORLD_FAILED_WORLD_FULL = 1;
	public const JOIN_WORLD_SUCCESS = 2;

	public function __construct(MineageCore $core){
		parent::__construct(
			core: $core,
			name: "worlds",
			default_config_data: DefaultConfigData::worlds()
		);

		if($this->enabled or $this->bypass_enable_check){
			$this->core->getServer()->getPluginManager()->registerEvents(new WorldsListener($core, $this), $core);
			foreach($this->core->getServer()->getWorldManager()->getWorlds() as $world){
				$world->setTime(World::TIME_NOON);
				$world->stopTime();
			}
		}
	}

	public function getLobbyWorld() : ?string{
		return $this->getConfig()->getNested("lobby-world", null);
	}

	public function teleportToLobbyOnJoin() : bool{
		return $this->getConfig()->getNested("teleport-to-lobby-on-join", false);
	}

	public function isWorldConfigured(string $world) : bool{
		return $this->getConfig()->getNested("worlds.$world", false) !== false;
	}

	public function getWorldMaxPlayers(string $world) : int{
		return $this->getConfig()->getNested("worlds.$world.max-players", self::WORLD_PLAYER_LIMIT_UNLIMITED);
	}

	public function getJoinWorldMessage(string $world) : ?string{
		return $this->getConfig()->getNested("worlds.$world.join-success");
	}

	public function getJoinLimitReachedMessage(string $world) : ?string{
		return $this->getConfig()->getNested("worlds.$world.join-limit-reached");
	}

	public function getWorldKit(string $world) : ?string{
		return $this->getConfig()->getNested("worlds.$world.world-kit");
	}

	public function equipKitOnJoin(string $world) : bool{
		return $this->getConfig()->getNested("worlds.$world.equip-kit-on-join", false);
	}

	public function allowRekit(string $world) : bool{
		return $this->getConfig()->getNested("worlds.$world.allow-rekit", false);
	}

	public function disableHunger(string $world) : bool{
		return $this->getConfig()->getNested("worlds.$world.disable-hunger", false);
	}

	public function disableDamage(string $world) : bool{
		return $this->getConfig()->getNested("worlds.$world.disable-damage", false);
	}

	public function getKillMessage(string $world) : string{
		return $this->getConfig()->getNested("worlds.$world.kill-message", "");
	}

	public function forceStandardNametags(string $world) : bool{
		return $this->getConfig()->getNested("worlds.$world.force-standard-nametags", false);
	}

	public function joinWorld(Player $player, string $world, bool $allow_bypass = false) : int{
		$world_class = ServerUtils::getWorldByName($world);
		$world_max_players = $this->getWorldMaxPlayers($world);

		if($world_class === null){
			return self::JOIN_WORLD_FAILED_WORLD_INVALID;
		}

		if(
			$world_max_players === self::WORLD_PLAYER_LIMIT_UNLIMITED
			or $world_max_players > count($world_class->getPlayers())
			or $allow_bypass or $player->hasPermission(PermissionNodes::MINEAGE_WORLD_LIMIT_BYPASS)
		){
			if(!$this->isWorldConfigured($world)){
				return self::JOIN_WORLD_SUCCESS;
			}

			if($this->equipKitOnJoin($world)){
				$this->equipWorldKit($player, $world);
			}

			$player->teleport($world_class->getSpawnLocation());
			$player->sendMessage(TextFormat::colorize($this->getJoinWorldMessage($world)));
			return self::JOIN_WORLD_SUCCESS;
		}

		$player->sendMessage(TextFormat::colorize($this->getJoinLimitReachedMessage($world)));
		return self::JOIN_WORLD_FAILED_WORLD_FULL;
	}

	public function equipWorldKit(Player $player, string $world) : bool{
		/** @var Kits $kits */
		$kits = $this->core->module_manager->getModuleByName("kits");
		if($kits === null){
			$this->core->getLogger()->info("Could not equip kit in world '$world', are kits disabled?");
			return false;
		}

		$kits->equipKit($player, $this->getConfig()->getNested("worlds.$world.world-kit"));
		return true;
	}
}
