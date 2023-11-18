<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Utils;

use Mineage\MineageCore\Permissions\PermissionNodes;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;

class ServerUtils{
	public static function getWorldByName(string $world) : ?World{
		$world_manager = Server::getInstance()->getWorldManager();

		if(!$world_manager->isWorldLoaded($world)){
			$world_manager->loadWorld($world);
		}

		return $world_manager->getWorldByName($world);
	}

	/**
	 * @param string[] $permissions
	 *
	 * @return Player[]
	 */
	public static function getOnlinePlayersWithPermissions(array $permissions) : array{
		return array_filter(Server::getInstance()->getOnlinePlayers(), static function(Player $player) use ($permissions){
			foreach($permissions as $permission){
				if($player->hasPermission($permission)){
					return true;
				}
			}
			return false;
		});
	}

	/**
	 * @return Player[]
	 */
	public static function getOnlineStaff() : array{
		return self::getOnlinePlayersWithPermissions([PermissionNodes::MINEAGE_CHANNEL_STAFF]);
	}

	public static function sendMessageToOnlinePlayersWithPermissions(string $message, array $permissions) : void{
		foreach(self::getOnlinePlayersWithPermissions($permissions) as $player){
			$player->sendMessage($message);
		}
	}

	public static function sendMessageToOnlineStaff(string $message) : void{
		self::sendMessageToOnlinePlayersWithPermissions($message, [PermissionNodes::MINEAGE_CHANNEL_STAFF]);
	}

	public static function playSound(string $sound, Position $position, float $volume = 1000, float $pitch = 1, ?array $targets = null) : void{
		NetworkBroadcastUtils::broadcastPackets($targets ?? $position->getWorld()->getPlayers(), [PlaySoundPacket::create($sound, $position->x, $position->y, $position->z, $volume, $pitch)]);
	}

	public static function spawnActor(string $actor, Location $location, ?array $targets = null) : void{
		$packet = new AddActorPacket();
		$packet->type = $actor;
		$packet->actorRuntimeId = Entity::nextRuntimeId();
		$packet->actorUniqueId = 1;
		$packet->position = $location;
		$packet->yaw = $location->yaw;
		$packet->syncedProperties = new PropertySyncData([], []);
		NetworkBroadcastUtils::broadcastPackets($targets ?? $location->getWorld()->getPlayers(), [$packet]);
	}
}
