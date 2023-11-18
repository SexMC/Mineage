<?php
declare(strict_types=1);

namespace MineageBunkersLobby\Task;

use MineageBunkersLobby\MineageBunkersLobby;
use MineageBunkersLobby\PreGame\PreGame;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class QueueTask extends Task{

	public function __construct(private readonly MineageBunkersLobby $plugin){}

	public function onRun() : void{
		if(count($this->plugin->getGameManager()->getQueued()) >= PreGame::NEEDED_PLAYERS){
			$this->plugin->getGameManager()->createGame();
		}

		foreach($this->plugin->getGameManager()->getGames() as $game){
			if($game instanceof PreGame){
				if(!$game->hasStarted()){
					if($game->isWaiting()){
						foreach($this->plugin->getGameManager()->getQueued() as $key => $value){
							if(!$game->isPlayer($key)){
								$p = Server::getInstance()->getPlayerExact($key);
								if($p !== null and $p->isConnected()){
									$game->addPlayer($p, MineageBunkersLobby::getInstance()->getSessionManager()->getSession($p), $value);
								}
							}
						}
					}

					$game->update();
				}
			}
		}
	}
}
