<?php
declare(strict_types=1);

namespace GrosserZak\MineageLobby\Tasks;

use GrosserZak\MineageLobby\Main;
use GrosserZak\MineageLobby\Utils\ScoreboardAPI;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class ScorehudTask extends Task{

    public function onRun(): void
    {
        $count = count(Main::getInstance()->getServer()->getOnlinePlayers());
        foreach (Main::getInstance()->serversData as $datas){
            foreach ($datas as $data){
                $count += $data["Players"] ?? 0;
            }
        }
       foreach (Server::getInstance()->getOnlinePlayers() as $player){
           ScoreboardAPI::sendScore($player, "§l§3HUB");
           ScoreboardAPI::setScoreLine($player, 1," §7----------------");
           ScoreboardAPI::setScoreLine($player, 2," §fOnline: §3$count");
           ScoreboardAPI::setScoreLine($player, 3," ");
           ScoreboardAPI::setScoreLine($player, 4," §3mineage.us");
           ScoreboardAPI::setScoreLine($player, 5," §7----------------§7");
       }
    }
}
