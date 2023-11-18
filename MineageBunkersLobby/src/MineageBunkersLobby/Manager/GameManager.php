<?php

declare(strict_types=1);

namespace MineageBunkersLobby\Manager;

use MineageBunkersLobby\MineageBunkersLobby;
use MineageBunkersLobby\PreGame\PreGame;
use MineageBunkersLobby\Scoreboard\Type\OnlineScoreboardType;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class GameManager{

	private array $games = [];
	private array $gamePlayers = [];
	private array $queuedPlayers = [];

	public function __construct(private readonly MineageBunkersLobby $plugin){}

	public function queuePlayer(Player $player) : bool{
		$session = $this->plugin->getSessionManager()->getSession($player);
		if($session === null){
			return false;
		}

		$n = $player->getName();
		if(isset($this->queuedPlayers[$n])){
			return false;
		}

		if($session->getQueueWait() > time()){
			$s = abs($session->getQueueWait() - time());
			$player->sendMessage(TextFormat::RED . 'You must wait ' . $s . ' ' . ($s > 1 ? 'seconds' : 'second') . ' before joining the queue again.');
			return false;
		}

		$session->doQueueAttempt();

		$this->queuedPlayers[$n] = ['None', time()];
		$player->sendMessage(TextFormat::GREEN . 'You joined the queue.');

		$this->plugin->getScoreboardManager()->getScoreboard(OnlineScoreboardType::ID)->update($player);
		//TODO: update scoreboard for everyone efficiently
		return true;
	}

	public function unqueuePlayer(Player $player, bool $notify = true) : bool{
		$n = $player->getName();
		if(!isset($this->queuedPlayers[$n])){
			return false;
		}

		unset($this->queuedPlayers[$n]);

		if($notify){
			$player->sendMessage(TextFormat::RED . 'You left the queue.');
			$this->plugin->getScoreboardManager()->getScoreboard(OnlineScoreboardType::ID)->update($player);
		}

		//TODO: update scoreboard for everyone efficiently
		return true;
	}

	public function getQueued() : array{
		return $this->queuedPlayers;
	}

	public function isQueued(Player $player) : bool{
		return isset($this->queuedPlayers[$player->getName()]);
	}

	public function createGame() : void{
		//TODO: Find an open server in the MYSQL db, if on is available

		$arr = $this->queuedPlayers;
		uasort($arr, fn($a, $b) => $a[1] <=> $b[1]);
		array_splice($arr, 5);
		foreach($arr as $key => $value){
			unset($this->queuedPlayers[$key]);
			//$this->gamePlayers[$key] = $value[0]; //TODO: set-up gamePlayers array
		}

		$server = 'mb-s1';
		$info = $this->plugin->getConfig()->get('servers')[$server];
		$game = new PreGame($server, $info['address'], $info['port'], $arr);
		$game->startVoting();
		$this->games[$game->getId()] = $game;
	}

	public function closeGame(PreGame $game) : void{
		if(!isset($this->games[$game->getId()])){
			return;
		}

		/*
		//TODO: set-up gamePlayers array
		foreach($game->getPlayers() as $key => $value){
			if(isset($this->gamePlayers[$key])){
				unset($this->gamePlayers[$key]);
			}
		}*/

		unset($this->games[$game->getId()]);
	}

	public function getGames() : array{
		return $this->games;
	}

	public function getGameFromPlayer(string $player) : ?PreGame{
		foreach($this->games as $game){
			if($game instanceof PreGame and $game->isPlayer($player)){
				return $game;
			}
		}

		return null;
	}

	public function doesGameWithIdExist(int $int) : bool{
		return isset($this->games[$int]);
	}

	public function getInGame() : array{
		return $this->gamePlayers;
	}

	public static function teamToString(string $string) : string{
		return match ($string) {
			'Blue' => TextFormat::BLUE . 'Blue',
			'Red' => TextFormat::RED . 'Red',
			'Yellow' => TextFormat::YELLOW . 'Yellow',
			'Green' => TextFormat::GREEN . 'Green',
			default => 'None',
		};
	}
}
