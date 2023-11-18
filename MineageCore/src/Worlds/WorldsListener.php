<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Worlds;

use Mineage\MineageCore\Moderation\Moderation;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Module\ModuleListener;
use Mineage\MineageCore\Permissions\PermissionNodes;
use Mineage\MineageCore\Utils\PlayerUtils;
use Mineage\MineageCore\Utils\ServerUtils;
use Mineage\MineageCore\Utils\StringUtils;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerBucketEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldSoundEvent;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\player\Player;
use pocketmine\world\sound\EntityAttackNoDamageSound;
use pocketmine\world\sound\EntityAttackSound;
use pocketmine\world\World;
use function count;

class WorldsListener extends ModuleListener{
	private const CHEMISTRY_PACK_ID = "0fba4063-dba1-4281-9b89-ff9390653530";

	/** @var Worlds */
	protected readonly CoreModule $owner;

	public function onPlayerPreLogin(PlayerPreLoginEvent $event) : void{
		$event->clearKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_FULL);
	}

	public function onQueryRegenerate(QueryRegenerateEvent $event) : void{
		$event->getQueryInfo()->setMaxPlayerCount($event->getQueryInfo()->getPlayerCount() + 1);
	}

	/**
	 * @priority LOW
	 */
	public function onPlayerLogin(PlayerLoginEvent $event) : void{
		$player = $event->getPlayer();
		if(
			count($this->getCore()->getServer()->getOnlinePlayers()) >= $this->getCore()->getServer()->getMaxPlayers() and
			!$this->canBypassServerLimit($player, PermissionNodes::MINEAGE_SERVER_LIMIT_BYPASS)
		){
			$event->setKickMessage(KnownTranslationFactory::disconnectionScreen_serverFull());
			$event->cancel();
			return;
		}

		/** @var Moderation $moderation */
		$moderation = $this->core->module_manager->getModuleByName("moderation");

		if(
			$this->owner->teleportToLobbyOnJoin()
			and ($world = ServerUtils::getWorldByName($this->owner->getLobbyWorld())) !== null
			and !$moderation?->isFrozen($player)
		){
			$player->teleport($world->getSafeSpawn());
		}
	}

	private function canBypassServerLimit(Player $player, string $permission) : bool{
		if($player->hasPermission($permission)){
			return true;
		}

		$rankSystemSession = $this->getCore()->getRankSystem()?->getSessionManager()->get($player);
		if($rankSystemSession === null){
			return false;
		}

		if($rankSystemSession->isInitialized()){
			return $rankSystemSession->hasPermission($permission);
		}else{
			$rankSystemSession->onInitialize(static function() use ($player, $permission, $rankSystemSession) : void{
				if(!$rankSystemSession->hasPermission($permission)){
					$player->kick(KnownTranslationFactory::disconnectionScreen_serverFull());
				}
			});
			return true; // We can't know yet
		}
	}

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$event->setJoinMessage($this->core->getMessage("join-message", ["@player" => $event->getPlayer()->getName()]));
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$event->setQuitMessage($this->core->getMessage("quit-message", ["@player" => $event->getPlayer()->getName()]));
	}

	public function onPlayerExhaust(PlayerExhaustEvent $event){
		$worldName = $event->getPlayer()->getWorld()->getFolderName();
		if($worldName === $this->owner->getLobbyWorld() or $this->owner->disableHunger($worldName)){
			$event->cancel();
		}
	}

	public function onEntityDamage(EntityDamageEvent $event) : void{
		$player = $event->getEntity();
		if(!$player instanceof Player){
			return;
		}

		$worldName = $player->getWorld()->getFolderName();
		if(
			$event->getCause() === EntityDamageEvent::CAUSE_FALL
			or $worldName === $this->owner->getLobbyWorld()
			or $this->owner->disableDamage($worldName)
		){
			$event->cancel();
		}
	}

	public function onBlockBreak(BlockBreakEvent $event) : void{
		if(!$event->getPlayer()->hasPermission(PermissionNodes::MINEAGE_PROTECTION_BYPASS)){
			$event->cancel();
		}
	}

	public function onBlockPlace(BlockPlaceEvent $event) : void{
		if(!$event->getPlayer()->hasPermission(PermissionNodes::MINEAGE_PROTECTION_BYPASS)){
			$event->cancel();
		}
	}

	public function onPlayerBucket(PlayerBucketEvent $event) : void{
		if(!$event->getPlayer()->hasPermission(PermissionNodes::MINEAGE_PROTECTION_BYPASS)){
			$event->cancel();
		}
	}

	public function onPlayerDeath(PlayerDeathEvent $event) : void{
		$event->setDrops([]);
		$event->setXpDropAmount(0);
		$player = $event->getPlayer();

		$event->setDeathMessage("");

		$location = $player->getLocation();

		ServerUtils::spawnActor("minecraft:lightning_bolt", $location);
		ServerUtils::playSound("ambient.weather.thunder", $location);

		$killer = PlayerUtils::getKiller($player);
		if($killer === null){
			$cause = $player->getLastDamageCause();
			if($cause !== null && $cause->getCause() === EntityDamageEvent::CAUSE_VOID){
				foreach($location->getWorld()->getPlayers() as $p){
					$p->sendMessage(StringUtils::replace(
						"§7» §3@player §bhas fallen into the void.",
						[
							"@player" => $player->getName(),
						]
					));
				}
			}
			return;
		}

		foreach($killer->getWorld()->getPlayers() as $p){
			$p->sendMessage(StringUtils::replace(
				$this->owner->getKillMessage($player->getWorld()->getFolderName()),
				[
					"@player" => $player->getName(),
					"@killer" => $killer->getName(),
					"@pot-player" => PlayerUtils::getPotionCount($player),
					"@pot-killer" => PlayerUtils::getPotionCount($killer)
				]
			));
		}

		$world = $killer->getWorld()->getFolderName();
		if($this->owner->getWorldKit($world) !== null and $this->owner->allowRekit($world)){
			$this->owner->equipWorldKit($killer, $world);
		}
	}

	public function onPlayerDropItem(PlayerDropItemEvent $event) : void{
		$event->cancel();
	}

	public function onWorldLoad(WorldLoadEvent $event) : void{
		$event->getWorld()->setTime(World::TIME_NOON);
		$event->getWorld()->stopTime();
	}

	public function onPlayerMove(PlayerMoveEvent $event) : void{
		$to = $event->getTo();
		$world = $to->getWorld();
		if($world === $world->getServer()->getWorldManager()->getDefaultWorld() and $to->getY() <= 0){
			$event->getPlayer()->teleport($world->getSpawnLocation());
			$event->cancel();
		}
	}

	public function onEntityTeleport(EntityTeleportEvent $event) : void{
		$player = $event->getEntity();
		$targetWorld = $event->getTo()->getWorld();
		$rankSystem = $this->core->getRankSystem();
		if(!$player instanceof Player or !$player->spawned or $rankSystem === null or $event->getFrom()->getWorld() === $targetWorld){
			return;
		}

		if($this->owner->forceStandardNametags($targetWorld->getFolderName())){
			$player->setNameTag($player->getName());
		}else{
			$rankSystem->getSessionManager()->get($player)->updateNametag();
		}
	}

	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		foreach($event->getPackets() as $packet){
			if($packet instanceof ResourcePackStackPacket){
				foreach($packet->resourcePackStack as $index => $resourcePack){
					if($resourcePack->getPackId() === self::CHEMISTRY_PACK_ID){
						unset($packet->resourcePackStack[$index]);
						return;
					}
				}
			}
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onSound(WorldSoundEvent $event) : void{
		$sound = $event->getSound();
		if($sound instanceof EntityAttackNoDamageSound || $sound instanceof EntityAttackSound){
			$event->cancel();
		}
	}
}
