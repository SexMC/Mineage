<?php
declare(strict_types=1);

namespace MineageBunkersLobby\PreGame;

use MineageBunkersLobby\Manager\GameManager;
use MineageBunkersLobby\MineageBunkersLobby;
use MineageBunkersLobby\Party\Party;
use MineageBunkersLobby\Scoreboard\Type\OnlineScoreboardType;
use MineageBunkersLobby\Scoreboard\Type\ReadyScoreboardType;
use MineageBunkersLobby\Scoreboard\Type\VotingScoreboardType;
use MineageBunkersLobby\Scoreboard\Type\WaitingScoreboardType;
use MineageBunkersLobby\session\Session;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function array_keys;
use function array_rand;
use function array_splice;
use function array_walk;
use function arsort;
use function count;
use function implode;
use function key;
use function uasort;

class PreGame{

	public const STATUS_VOTING = 1;
	public const STATUS_STARTING = 2;
	public const STATUS_STARTED = 3;
	public const STATUS_WAITING = 4;

	public const DURATION_VOTING = 10;//TODO: 15 (make sure to change back to 15)
	public const DURATION_STARTING = 10;//TODO: 30 (make sure to change back to 30)
	public const DURATION_WAITING = 30;//TODO: 600 (10 min) (make sure to change back to 600)

	public const NEEDED_PLAYERS = 2;//TODO: 20 (make sure to change back to 20)

	private int $id = -1;
	private int $countdown = self::DURATION_VOTING;
	private int $waitingCountdown = self::DURATION_WAITING;
	private int $status = self::STATUS_VOTING;
	private int $previousStatus = 0;
	private array $map = [0 => 'None', 1 => 0];
	private array $maps = ['Classic' => 0];

	public function __construct(private readonly string $server, private readonly string $address, private readonly int $port, private array $players){
		$int = mt_rand(1, 1000);
		while($this->id == -1 and !MineageBunkersLobby::getInstance()->getGameManager()->doesGameWithIdExist($int)){
			$this->id = $int;
		}
	}

	public function getId() : int{
		return $this->id;
	}

	public function getCountdown() : int{
		return $this->countdown;
	}

	public function getWaitingCooldown() : int{
		return $this->waitingCountdown;
	}

	public function getServer() : string{
		return $this->server;
	}

	public function getAddress() : string{
		return $this->address;
	}

	public function getPort() : int{
		return $this->port;
	}

	public function hasStarted() : bool{
		return $this->status == self::STATUS_STARTED;
	}

	public function isWaiting() : bool{
		return $this->status == self::STATUS_WAITING;
	}

	public function isStarting() : bool{
		return $this->status == self::STATUS_STARTING;
	}

	public function getMap() : string{
		return $this->map[0];
	}

	public function getVotesForMap(string $map) : int{
		return $this->maps[$map] ?? 0;
	}

	public function getMostVotedMap() : array{
		$arr = $this->maps;
		arsort($arr);
		return $arr;
	}

	public function addVote(string $map) : bool{
		if(!isset($this->maps[$map])){
			return false;
		}

		$this->maps[$map]++;
		return true;
	}

	public function removeVote(string $map) : bool{
		if(!isset($this->maps[$map])){
			return false;
		}

		$this->maps[$map]--;
		return true;
	}

	public function addPlayer(Player $player, Session $session, array $queueDetail) : void{
		if($this->hasStarted()){
			return;
		}

		$n = $player->getName();
		if(isset($this->players[$n])){
			return;
		}

		$v = $session->getCurrentVote();
		if($v != ''){
			$this->removeVote($v);
		}

		$hadLess = count($this->players) < self::NEEDED_PLAYERS;
		$this->players[$n] = $queueDetail;

		if($this->isWaiting()){
			$c = count($this->players);
			if($c < self::NEEDED_PLAYERS){
				$rem = self::NEEDED_PLAYERS - $c;
				$this->sendMessage(TextFormat::WHITE . 'Your game needs ' . TextFormat::RED . $rem . TextFormat::WHITE . ' more ' . ($rem > 1 ? 'players' : 'player') . ' to join.');
			}else{
				if($hadLess){
					$this->sendMessage(TextFormat::WHITE . 'Your game will now resume.');
				}
			}
		}
	}

	public function removePlayer(Player $player, Session $session) : void{
		if($this->hasStarted()){
			return;
		}

		$n = $player->getName();
		if(!isset($this->players[$n])){
			return;
		}

		$v = $session->getCurrentVote();
		if($v != ''){
			$this->removeVote($v);
		}

		unset($this->players[$n]);
	}

	public function getPlayers(bool $players = false) : array{
		if($players){
			$array = [];
			foreach($this->players as $key => $_){
				$pl = Server::getInstance()->getPlayerExact($key);
				if($pl !== null and $pl->isConnected()){
					$array[] = $pl;
				}
			}

			return $array;
		}

		return $this->players;
	}

	public function isPlayer(string $player) : bool{
		return isset($this->players[$player]);
	}

	public function getPlayerTeam(Player $player, bool $format = false) : string{
		foreach($this->players as $p => $team){
			if($player->getName() == $p){
				return $format ? GameManager::teamToString($team[0]) : $team[0];
			}
		}

		return 'None';
	}

	public function setPlayerTeam(Player $player, string $team) : void{
		if(!isset($this->players[$player->getName()])){
			return;
		}

		$f = GameManager::teamToString($team);
		if($this->players[$player->getName()][0] == $team){
			$player->sendMessage(TextFormat::RED . 'You are already on the ' . $f . TextFormat::RED . ' team.');
			return;
		}

		$c = 0;
		foreach($this->players as $_ => $otherTeam){
			if($otherTeam == $team){
				$c++;
			}
		}

		if($c >= Party::CAPACITY){
			$player->sendMessage(TextFormat::RED . 'The ' . $f . TextFormat::RED . ' team is at full capacity.');
			return;
		}

		$this->players[$player->getName()][0] = $team;
		$player->sendMessage(TextFormat::GREEN . 'You joined the ' . $f . TextFormat::GREEN . ' team.');
	}

	public function sendMessage(string $string) : void{
		foreach($this->getPlayers(true) as $p){
			$p->sendMessage($string);
		}
	}

	public function startVoting() : void{
		foreach($this->getPlayers(true) as $p){
			$p->getInventory()->clearAll();
			$p->getCursorInventory()->clearAll();

			$p->getInventory()->setItem(0, clone MineageBunkersLobby::getInstance()->getUtils()->getPreGameItem('join-blue-team'));
			$p->getInventory()->setItem(1, clone MineageBunkersLobby::getInstance()->getUtils()->getPreGameItem('join-red-team'));
			$p->getInventory()->setItem(2, clone MineageBunkersLobby::getInstance()->getUtils()->getPreGameItem('join-yellow-team'));
			$p->getInventory()->setItem(3, clone MineageBunkersLobby::getInstance()->getUtils()->getPreGameItem('join-green-team'));

			MineageBunkersLobby::getInstance()->getScoreboardManager()->getScoreboard(VotingScoreboardType::ID)->send($p, null, false);
			MineageBunkersLobby::getInstance()->getScoreboardManager()->getScoreboard(VotingScoreboardType::ID)->update($p, $this);
		}
	}

	public function startGame() : void{
		//TODO: make sure the Teams are divided equally
		$map = $this->getMostVotedMap();

		$this->map[0] = ($name = key($map));
		$this->map[1] = ($votes = $map[$name]);

		$english = ($votes == 0 or $votes > 1) ? 'votes.' : 'vote.';
		foreach($this->getPlayers(true) as $p){
			$p->sendMessage(TextFormat::DARK_AQUA . $name . TextFormat::WHITE . ' has been chosen with ' . TextFormat::GREEN . $votes . TextFormat::WHITE . ' ' . $english);

			MineageBunkersLobby::getInstance()->getScoreboardManager()->getScoreboard(ReadyScoreboardType::ID)->send($p, null, false);
			MineageBunkersLobby::getInstance()->getScoreboardManager()->getScoreboard(ReadyScoreboardType::ID)->update($p, $this);

			MineageBunkersLobby::getInstance()->getSessionManager()->getSession($p)?->setCurrentVote('');
		}

		$this->status = self::STATUS_STARTING;
	}

	public function update() : void{
		$players = $this->getPlayers(true);
		$c = count($this->players);
		if($c < self::NEEDED_PLAYERS and !$this->hasStarted()){
			if($this->status != self::STATUS_WAITING){
				//Just an initial warning to keep players informed, and save the status
				//to be able to resume the game where it was when there's enough players
				$rem = self::NEEDED_PLAYERS - $c;
				$s = TextFormat::WHITE . 'Your game needs ' . TextFormat::RED . $rem . TextFormat::WHITE . ' more ' . ($rem > 1 ? 'players' : 'player') . ' to join.';
				foreach($players as $p){
					$p->sendMessage($s);
					MineageBunkersLobby::getInstance()->getScoreboardManager()->getScoreboard(WaitingScoreboardType::ID)->send($p);
				}

				$this->previousStatus = $this->status;
			}

			$this->status = self::STATUS_WAITING;

			if($this->waitingCountdown < 1){
				$this->players = [];
				$this->map = [];
				$this->maps = [];

				foreach($players as $p){
					$p->sendMessage(TextFormat::RED . 'Your game has ended for not having enough players to start.');
					MineageBunkersLobby::getInstance()->getScoreboardManager()->getScoreboard(OnlineScoreboardType::ID)->send($p);
					MineageBunkersLobby::getInstance()->getUtils()->sendSpawnKit($p);
				}

				MineageBunkersLobby::getInstance()->getGameManager()->closeGame($this);
				return;
			}

			foreach($players as $p){
				MineageBunkersLobby::getInstance()->getScoreboardManager()->getScoreboard(WaitingScoreboardType::ID)->update($p, $this);
			}

			$this->waitingCountdown--;
			return;
		}else{
			if($this->status == self::STATUS_WAITING){
				$this->status = $this->previousStatus;
				$this->previousStatus = 0;
				$this->waitingCountdown = self::DURATION_WAITING;
			}
		}

		if($this->status == self::STATUS_VOTING){
			if($this->countdown == 0){
				$this->status = self::STATUS_STARTING;
				$this->countdown = self::DURATION_STARTING;
				$this->startGame();
				return;
			}elseif($this->countdown == 10 or $this->countdown < 6){
				$this->sendMessage(TextFormat::WHITE . 'Map voting will end in ' . TextFormat::DARK_AQUA . $this->countdown . ' seconds' . TextFormat::WHITE . '.');
			}

			foreach($players as $p){
				MineageBunkersLobby::getInstance()->getScoreboardManager()->getScoreboard(VotingScoreboardType::ID)->update($p, $this);
			}
		}elseif($this->status == self::STATUS_STARTING){
			if($this->countdown == 0){
				[$blue, $red, $yellow, $green] = $this->arrangeTeams();
				MineageBunkersLobby::getInstance()->getNetwork()?->executeGeneric('mineage.update.game.server.entry', [
					'server' => $this->getServer(),
					'map_name' => $this->getMap(),
					'blue_team' => implode(',', array_keys($blue)),
					'red_team' => implode(',', array_keys($red)),
					'yellow_team' => implode(',', array_keys($yellow)),
					'green_team' => implode(',', array_keys($green)),
					'spectating' => '',
					'winning_team' => '',
				]);

				$this->status = self::STATUS_STARTED;
				$this->countdown = -1;

				foreach($players as $p){
					$p->transfer($this->address, $this->port, 'Automated transfer to play on game-server: ' . $this->server);
				}
				return;
			}elseif($this->countdown < 11){
				if($this->countdown == 4){
					foreach($players as $p){
						$p->sendTitle(' ', TextFormat::GRAY . 'You\'ll now be transferred to a server for your game', 10, 40, 10);
						$p->getInventory()?->clearAll();
						$p->getCursorInventory()?->clearAll();
					}
				}

				$this->sendMessage(TextFormat::WHITE . 'The game will start in ' . TextFormat::DARK_AQUA . $this->countdown . ' seconds' . TextFormat::WHITE . '.');
			}

			foreach($players as $p){
				MineageBunkersLobby::getInstance()->getScoreboardManager()->getScoreboard(ReadyScoreboardType::ID)->update($p, $this);
			}
		}else{
			return;
		}

		$this->countdown--;
	}

	private function arrangeTeams() : array{
		$arr = $this->players;
		$blue = [];
		$red = [];
		$yellow = [];
		$green = [];
		$teams = ['Blue' => &$blue, 'Red' => &$red, 'Yellow' => &$yellow, 'Green' => &$green];

		array_walk($arr, function($value, $key) use (&$blue, &$red, &$yellow, &$green, $teams){
			if($value[0] == 'Blue'){
				$blue[$key] = $value[0];
			}

			if($value[0] == 'Red'){
				$red[$key] = $value[0];
			}

			if($value[0] == 'Yellow'){
				$yellow[$key] = $value[0];
			}

			if($value[0] == 'Green'){
				$green[$key] = $value[0];
			}

			if($value[0] == 'None'){
				$randTeam = array_rand($teams);
				$randA = &$teams[$randTeam];
				$randA[$key] = $randTeam;
			}

			if($value[0] == 'Party'){//TODO
				/*$p = MineageBunkersLobby::getInstance()->getPartyManager()->getPartyFromPlayer($key);
				$randTeam = array_rand($teams);
				$randA = &$teams[$randTeam];
				$randA[$key] = $randTeam;*/
			}
		});

		uasort($blue, fn($a, $b) => $a[1] <=> $b[1]);//sort based who initially queued soonest
		array_splice($blue, 5);

		uasort($red, fn($a, $b) => $a[1] <=> $b[1]);
		array_splice($red, 5);

		uasort($yellow, fn($a, $b) => $a[1] <=> $b[1]);
		array_splice($yellow, 5);

		uasort($green, fn($a, $b) => $a[1] <=> $b[1]);
		array_splice($green, 5);

		return [$blue, $red, $yellow, $green];
	}
}
