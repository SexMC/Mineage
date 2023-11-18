<?php
declare(strict_types=1);

namespace GrosserZak\MineageLobby\Tasks;

use GrosserZak\MineageLobby\Main;
use libpmquery\PMQuery;
use libpmquery\PmQueryException;
use pocketmine\scheduler\AsyncTask;

class FetchServersStatsAsyncTask extends AsyncTask {

    private const TLS_KEY_PLUGIN = "plugin";

    private static int $idCounter = 1;

    private int $id;

    private string $servers;

    public function __construct(array $serversArr) {
        $this->servers = serialize($serversArr);
        $this->id = self::$idCounter++;
    }

    public function getId() : int {
        return $this->id;
    }

    public function onRun() : void {
        $serversArr = unserialize($this->servers);
        $serversData = [];
        foreach($serversArr as $groupName => $groupServers) {
            foreach($groupServers as $serverName => $serverData) {
               $ip = "";
               $port = "";
                $group = true;
                if ($serverName == "ip" || $serverName == "port"){
                    $ip = $groupServers["ip"];
                    $port = $groupServers["port"];
                    $group = false;
                } else {
                    $ip = $serverData["ip"];
                    $port = $serverData["port"];
                }

                try {
                    $result = PMQuery::query($ip, $port);
                } catch(PmQueryException) {
                    $result = [];
                }

                if ($group) {
                    if (!empty($result) and !is_null($result["Players"]) and !is_null($result["MaxPlayers"])) {
                        $serversData[$groupName][$serverName] = $result;
                    } else {
                        $serversData[$groupName][$serverName] = null;
                    }
                } else {
                    if (!empty($result) and !is_null($result["Players"]) and !is_null($result["MaxPlayers"])) {
                        $serversData[$groupName][$groupName] = $result;
                    } else {
                        $serversData[$groupName][$groupName] = null;
                    }
                }
            }


        }
        $this->setResult($serversData);
    }

    public function onCompletion() : void {
        Main::getInstance()->loadServersData($this->id, $this->getResult());
    }

}
