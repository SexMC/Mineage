<?php
declare(strict_types=1);

namespace GrosserZak\MineageLobby\Listeners;

use GrosserZak\MineageLobby\Main;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\Server;
use pocketmine\utils\TextFormat as G;

class EventListener implements Listener {

    private const SERVER_SELECTOR_NBT_NAME = "server-selector";

    public function __construct(
        private readonly array $itemData
    ) {}

    public function onLogin(PlayerLoginEvent $ev) : void {
        $player = $ev->getPlayer();
        $player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
        $ip = $player->getNetworkSession()->getIp();
        if (isset(Main::getInstance()->storage[$ip]) && Main::getInstance()->storage[$ip] >= 2){
            $ev->cancel();
        } else {
            Main::getInstance()->storage[$ip] = (Main::getInstance()->storage[$ip] ?? 0)+ 1;
        }
    }

    public function onJoin(PlayerJoinEvent $ev) : void {
        $player = $ev->getPlayer();
        $ev->setJoinMessage("§8[§a+§8]§a {$player->getName()}");
            $player->sendMessage("§3Welcome to the §lMineage Network§r§3!\n§3Discord: §fdiscord.gg/mineagenetwork");
            $item = StringToItemParser::getInstance()->parse($this->itemData["id"]);
            if ($item === null) {
                Main::getInstance()->getLogger()->warning("Miss configured item ID in config.yml! Not found item with id: " . $this->itemData["id"] . "!");
                $item = VanillaItems::NETHER_STAR();
            }
            $item->setCustomName($this->itemData["name"]);
            $item->setLore(["", $this->itemData["lore"]]);
            $item->getNamedTag()->setTag(Main::getInstance()->getName(), CompoundTag::create()
                ->setByte(self::SERVER_SELECTOR_NBT_NAME, 1)
            );
            $player->getInventory()->setItem(4, $item);
            $player->getInventory()->setHeldItemIndex(4);
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        $event->setQuitMessage("§8[§c-§8]§c {$player->getName()}");
        $ip = $player->getNetworkSession()->getIp();
        if(isset(Main::getInstance()->storage[$ip])){
            Main::getInstance()->storage[$ip]--;
            if (Main::getInstance()->storage[$ip] <= 0){
                unset(Main::getInstance()->storage[$ip]);
            }
        }
    }

    public function onDamage(EntityDamageEvent $ev) : void {
            $ev->cancel();
    }

    public function onUse(PlayerItemUseEvent $ev) : void {
        if ($ev->getItem()->getNamedTag()->getCompoundTag(Main::getInstance()->getName())?->getTag(self::SERVER_SELECTOR_NBT_NAME) !== null) {
            Main::getInstance()->openServerSelectorUI($ev->getPlayer());
            $ev->cancel();
        }
    }

    public function onDrop(PlayerDropItemEvent $ev) : void {
        if($ev->getItem()->getNamedTag()->getCompoundTag(Main::getInstance()->getName())?->getTag(self::SERVER_SELECTOR_NBT_NAME) !== null) {
            $ev->cancel();
        }
    }

    public function onItemMove(InventoryTransactionEvent $ev) : void {
        $transaction = $ev->getTransaction();
        $actions = array_values($transaction->getActions());
        if(count($actions) === 2) {
            foreach($actions as $i => $action) {
                if($action instanceof SlotChangeAction and $actions[($i + 1) % 2] instanceof SlotChangeAction and
                    ( $action->getTargetItem()->getNamedTag()->getCompoundTag(Main::getInstance()->getName())?->getTag(self::SERVER_SELECTOR_NBT_NAME) !== null
                        or $action->getSourceItem()->getNamedTag()->getCompoundTag(Main::getInstance()->getName())?->getTag(self::SERVER_SELECTOR_NBT_NAME) !== null )) {
                    $ev->cancel();
                }
            }
        }
    }
    public function onExhaust(PlayerExhaustEvent $ev) : void {
        $ev->cancel();
    }

    public function onQuery(QueryRegenerateEvent $event): void
    {
        $count = count(Main::getInstance()->getServer()->getOnlinePlayers());
        foreach (Main::getInstance()->serversData as $datas){
            foreach ($datas as $data){
                $count += $data["Players"] ?? 0;
            }
        }
        $event->getQueryInfo()->setPlayerCount($count);
    }

    public function onPlace(BlockPlaceEvent $event): void
    {
        $event->cancel();
    }

    public function onBreak(BlockBreakEvent $event): void
    {
        $event->cancel();
    }

    public function onMove(PlayerMoveEvent $event): void{
        $player = $event->getPlayer();
        if ($player->getPosition()->getFloorY() <= 1){
            $player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
        }
    }
}
