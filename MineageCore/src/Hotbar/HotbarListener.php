<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Hotbar;

use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Module\ModuleListener;
use Mineage\MineageCore\Utils\PlayerUtils;
use Mineage\MineageCore\Worlds\Worlds;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerOffHandInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\player\Player;

class HotbarListener extends ModuleListener{
	/** @var Hotbar */
	protected readonly CoreModule $owner;

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$world = $player->getWorld()->getFolderName();

		/** @var Worlds $worlds */
		$worlds = $this->core->module_manager->getModuleByName("worlds");
		if($worlds !== null and $worlds->getWorldKit($world) === null){
			PlayerUtils::resetPlayer($player);
		}

		if($this->owner->isHotbarWorld($world)){
			$this->owner->equip($player);
		}
	}

	public function onEntityTeleport(EntityTeleportEvent $event){
		$to = $event->getTo();
		$from = $event->getFrom();
		$entity = $event->getEntity();

		if($entity instanceof Player and $to->getWorld() !== $from->getWorld()){
			$world = $to->getWorld()->getFolderName();

			/** @var Worlds $worlds */
			$worlds = $this->core->module_manager->getModuleByName("worlds");

			if($worlds !== null and $worlds->getWorldKit($world) === null){
				PlayerUtils::resetPlayer($entity);
			}

			if($this->owner->isHotbarWorld($world)){
				$this->owner->equip($entity);
			}
		}
	}

	public function onPlayerDropItem(PlayerDropItemEvent $event){
		$world = $event->getPlayer()->getWorld()->getFolderName();

		if($this->owner->isHotbarWorld($world) and $this->owner->cancelDrop()){
			$event->cancel();
		}
	}

	public function onInventoryTransaction(InventoryTransactionEvent $event){
		$world = $event->getTransaction()->getSource()->getWorld()->getFolderName();

		if($this->owner->isHotbarWorld($world) and $this->owner->cancelSlotChange()){
			foreach($event->getTransaction()->getActions() as $action){
				if($action instanceof SlotChangeAction){
					foreach($event->getTransaction()->getInventories() as $inventory){
						if($inventory instanceof PlayerInventory || $inventory instanceof PlayerOffHandInventory || $inventory instanceof ArmorInventory){
							$event->cancel();
						}
					}
				}
			}
		}
	}
}
