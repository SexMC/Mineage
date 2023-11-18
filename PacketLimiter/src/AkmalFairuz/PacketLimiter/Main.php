<?php

declare(strict_types=1);

namespace AkmalFairuz\PacketLimiter;

use AkmalFairuz\PacketLimiter\session\SessionManager;
use AkmalFairuz\PacketLimiter\task\Task;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketDecodeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\CraftingEventPacket;
use pocketmine\network\mcpe\protocol\EditorNetworkPacket;
use pocketmine\network\mcpe\protocol\EmoteListPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\PurchaseReceiptPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SubChunkRequestPacket;
use pocketmine\network\mcpe\protocol\SubClientLoginPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{

	public function onEnable() : void{
		$cfg = $this->getConfig();
		$maxWarn = $cfg->get("maximum_warning", 5);
		$packetLimit = $cfg->get("packet_per_second", 250);
		$kickMessage = $cfg->get("kick_message", "You sending too many packets!");
		SessionManager::create($maxWarn, $packetLimit, $kickMessage);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getScheduler()->scheduleRepeatingTask(new Task(), 1);
	}

	public function onPacketDecode(DataPacketDecodeEvent $event) : void{
		if($event->getPacketId() === MobEquipmentPacket::NETWORK_ID){
			if(strlen($event->getPacketBuffer()) >= 200){
				$event->cancel();
			}
		}
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 * @priority MONITOR
	 */
	public function onPacketReceive(DataPacketReceiveEvent $event){
		$player = $event->getOrigin()->getPlayer();
		if($player instanceof Player){
			$packet = $event->getPacket();
			if($packet instanceof InventoryTransactionPacket && $packet->trData instanceof UseItemTransactionData){
				return;
			}elseif($packet instanceof NetworkStackLatencyPacket){
				return;
			}
			SessionManager::getInstance()->get($player)->addPacket();
		}
	}

	/**
	 * @param PlayerQuitEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerQuit(PlayerQuitEvent $event){
		SessionManager::getInstance()->remove($event->getPlayer());
	}

	public function onDataPacketDecode(DataPacketDecodeEvent $event) : void{
		$cancel = function() use($event) : void{
			$event->cancel();
			$message = "Discarded packet " . PacketPool::getInstance()->getPacketById($event->getPacketId())->getName() . " from ";
			if($event->getOrigin()->getPlayer() !== null){
				$message .= $event->getOrigin()->getPlayer()->getName();
			}else{
				$message .= $event->getOrigin()->getIp();
			}
			$this->getLogger()->info($message);
		};

		switch($event->getPacketId()){
			case EmoteListPacket::NETWORK_ID:
			case CraftingEventPacket::NETWORK_ID:
			case EditorNetworkPacket::NETWORK_ID:
			case MapInfoRequestPacket::NETWORK_ID:
			case PurchaseReceiptPacket::NETWORK_ID:
			case SetActorDataPacket::NETWORK_ID:
			case SubChunkRequestPacket::NETWORK_ID:
			case SubClientLoginPacket::NETWORK_ID:
			case BlockActorDataPacket::NETWORK_ID:
			case PlayerSkinPacket::NETWORK_ID:
				$cancel();
				return;
		}

		$in = PacketSerializer::decoder($event->getPacketBuffer(), 0, $event->getOrigin()->getPacketSerializerContext());
		$valid = match($event->getPacketId()){
			//ItemStackRequestPacket::NETWORK_ID => $this->validateItemStackRequestPacket($in),
			default => true
		};
		if(!$valid){
			$cancel();
		}
	}

	private function validateItemStackRequestPacket(PacketSerializer $in) : bool{
		$count = $in->getUnsignedVarInt();
		if($count > 60){
			return false;
		}
		for($i = 0; $i < $count; $i++){
			if(!$this->validateItemStackRequest($in)){
				return false;
			}
		}
		return true;
	}

	private function validateItemStackRequest(PacketSerializer $in) : bool{
		$in->readGenericTypeNetworkId();
		return $in->getUnsignedVarInt() > 10;
	}
}
