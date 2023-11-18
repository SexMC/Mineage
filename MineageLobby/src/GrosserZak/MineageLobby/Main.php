<?php
declare(strict_types=1);

namespace GrosserZak\MineageLobby;

use GrosserZak\MineageLobby\Commands\UiCommand;
use GrosserZak\MineageLobby\Listeners\EventListener;
use GrosserZak\MineageLobby\Tasks\FetchServersStatsAsyncTask;
use GrosserZak\MineageLobby\Tasks\ScorehudTask;
use jojoe77777\FormAPI\SimpleForm;
use libpmquery\PMQuery;
use pocketmine\player\Player;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\inventory\TransactionData;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat as G;

class Main extends PluginBase {
    use SingletonTrait;

    private const BACK_BUTTON = "#back#";

    /** @var Int[] */
    public array $storage;

    private int $serversFetchStatsTaskId;

    public array $uiData = [], $serversData = [];

    protected function onEnable() : void {

        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }

        if(!class_exists(\jojoe77777\FormAPI\FormAPI::class)) {
            $this->getLogger()->error("FormAPI must be installed for this plugin to work!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        if(!class_exists(PMQuery::class)) {
            $this->getLogger()->error("PMQuery must be installed for this plugin to work!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        $this->saveDefaultConfig();
        $this->loadConfigData();
        $this->getServer()->getCommandMap()->register("ui", new UiCommand($this));
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this->getConfig()->get("item")), $this);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() {
            $task = new FetchServersStatsAsyncTask($this->getConfig()->get("servers"));
            $this->serversFetchStatsTaskId = $task->getId();
            $this->getServer()->getAsyncPool()->submitTask($task);
        }), 20);
        self::setInstance($this);
        $this->getServer()->getWorldManager()->getDefaultWorld()->setTime(6000);
        $this->getServer()->getWorldManager()->getDefaultWorld()->stopTime();
        $this->getScheduler()->scheduleRepeatingTask(new ScorehudTask(), 20);
    }

    private function loadConfigData() : void {
        $cfg = $this->getConfig();
        $this->uiData = $cfg->get("ui");
    }

    /** @internal */
    public function loadServersData(int $id, array $data) : void {
        if($this->serversFetchStatsTaskId === $id) {
            $this->serversData = $data;
        }
    }

    public function openServerSelectorUI(Player $player) : void {
        $menu = InvMenu::create(InvMenu ::TYPE_CHEST);
        $inventory = $menu->getInventory();

        for ($i = 0 ; $i < $inventory->getSize(); $i++) {
            if ($inventory->isSlotEmpty($i)) {
                $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem()->setCustomName(" ");
                $inventory->setItem($i, $item);
            }
        }
        $sword = VanillaItems::DIAMOND_SWORD();
        $lore = $this->uiData["groups-content"];
        $name = $this->uiData["servers-content"];
      
        foreach($this->serversData as $groupName => $groupServers) {
            $groupPlayersCounter = 0;
            $groupMaxPlayersCounter = 0;
            foreach($groupServers as $serverData) {
                if(!is_null($serverData) and !is_null($serverData["Players"]) and !is_null($serverData["MaxPlayers"])) {
                    $groupPlayersCounter += $serverData["Players"];
                    $groupMaxPlayersCounter += $serverData["MaxPlayers"];
                }
            }
        }

        $sword->setCustomName($name);
        $sword->setLore([$lore]);
        $newlore = str_replace("[players]", (string)$groupPlayersCounter, $lore);
        $newlore = str_replace("[maxplayers]", (string)$groupMaxPlayersCounter, $lore);
        $inventory->setItem(13, $sword);
        $menu->setName($this->uiData["title"]);
        $menu->send($player);

        $menu->setListener(function(InvMenuTransaction $transaction) : InvMenuTransactionResult{
            $player = $transaction->getPlayer();
            if ($transaction->getItemClicked()->getVanillaName() === "Diamond Sword") {
                $serverData = $this->getConfig()->getNested("servers.Asia.AS1");
                $player->transfer($serverData["ip"], $serverData["port"]);
            return $transaction->continue();
            } else {
                return $transaction->discard();
            }

            return $transaction->continue();
        });

        $menu->setInventoryCloseListener(function(Player $player, Inventory $inv): void {
            $player->removeCurrentWindow();
        });
    }   

    public function openServerGroupSelectorUI(Player $player, string $groupName) : void {
        $form = new SimpleForm(function (Player $player, ?string $data) use ($groupName) {
            if($data != null) {
                if($data === self::BACK_BUTTON) {
                    $this->openServerSelectorUI($player);
                    return;
                }
                $serverData = $this->getConfig()->getNested("servers.".$groupName)[$data];
                foreach($this->serversData[$groupName] as $serverName => $dataServer) {
                    if ($serverName == $data) {
                        if (is_null($dataServer) and !isset($dataServer["Players"]) and !isset($dataServer["MaxPlayers"])) {
                            $player->sendMessage(Main::getInstance()->getConfig()->get("message")["offlineservermessage"]);
                            return;
                        }
                    }
                }
                $player->transfer($serverData["ip"], $serverData["port"]);
            }
        });
        $form->setTitle($this->uiData["title"]);
        $form->setContent($this->uiData["servers-content"]);
        foreach($this->serversData[$groupName] as $serverName => $serverData) {
            if(!is_null($serverData) and !is_null($serverData["Players"]) and !is_null($serverData["MaxPlayers"])) {
                $serverStatus = "§8Playing: §3{$serverData["Players"]}";
            } else {
                $serverStatus = G::RED . "OFFLINE";
            }
            $form->addButton($serverName . G::EOL . $serverStatus, -1, "", $serverName);
        }
        $form->addButton("Back", -1, "", self::BACK_BUTTON);
        $player->sendForm($form);
    }
}
