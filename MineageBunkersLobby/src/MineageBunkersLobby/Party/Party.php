<?php
declare(strict_types=1);

namespace MineageBunkersLobby\Party;

use MineageBunkersLobby\Manager\GameManager;
use MineageBunkersLobby\Manager\PartyManager;
use MineageBunkersLobby\MineageBunkersLobby;
use MineageBunkersLobby\Scoreboard\Type\OnlineScoreboardType;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function count;
use function mt_rand;

class Party{

	public const CAPACITY = 5;

	private int $id = -1;

	private bool $public = false;
	private string $team = 'None';

	public function __construct(private array $members){
		$int = mt_rand(1, 1000);
		while($this->id == -1 and !MineageBunkersLobby::getInstance()->getPartyManager()->doesPartyIdExist($int)){
			$this->id = $int;
		}

		foreach($this->getMembers(true) as $member){
			MineageBunkersLobby::getInstance()->getScoreboardManager()->getScoreboard(OnlineScoreboardType::ID)->update($member, $this);
		}
	}

	public function getId() : int{
		return $this->id;
	}

	public function isPublic() : bool{
		return $this->public;
	}

	public function setPublic(bool $public = true) : void{
		$this->public = $public;
	}

	public function getTeam(bool $format = false) : string{
		if($format){
			return GameManager::teamToString($this->team);
		}

		return $this->team;
	}

	public function setTeam(string $team) : void{
		$this->team = $team;
	}

	public function getMembers(bool $asObj = false) : array{
		if($asObj){
			$array = [];
			foreach($this->members as $key => $_){
				$pl = Server::getInstance()->getPlayerExact($key);
				if($pl !== null and $pl->isConnected()){
					$array[] = $pl;
				}
			}

			return $array;
		}

		return $this->members;
	}

	public function getLeader() : ?Player{
		foreach($this->members as $key => $value){
			if($value[0] == PartyManager::RANK_LEADER){
				return Server::getInstance()->getPlayerExact($key);
			}
		}

		return null;
	}

	public function setLeader(string $player) : void{
		$leader = $this->getLeader()?->getName();
		if(!isset($this->members[$player]) or $leader == $player){
			return;
		}

		$infoPl = &$this->members[$player];
		$infoPl[0] = PartyManager::RANK_LEADER;

		$infoLe = &$this->members[$leader];
		$infoLe[0] = PartyManager::RANK_MEMBER;
	}

	public function addMember(Player $player) : void{
		if(count($this->members) > PartyManager::CAPACITY){
			$player->sendMessage(TextFormat::RED . $this->getLeader()?->getNameTag() . '\'s party is full.');
			return;
		}

		$n = $player->getName();
		if(isset($this->members[$n])){
			return;
		}

		$this->members[$n] = [PartyManager::RANK_MEMBER, false];
		MineageBunkersLobby::getInstance()->getScoreboardManager()->getScoreboard(OnlineScoreboardType::ID)->update($player, $this);
	}

	public function removeMember(Player $player, bool $notify = true) : void{
		$n = $player->getName();
		if(!isset($this->members[$n])){
			return;
		}

		unset($this->members[$n]);
		if($notify){
			MineageBunkersLobby::getInstance()->getScoreboardManager()->getScoreboard(OnlineScoreboardType::ID)->update($player);
		}
	}

	public function updatePartyChat(Player $player) : bool{
		$n = $player->getName();
		if(!isset($this->members[$n])){
			return false;
		}

		$arr = $this->members[$n];
		$this->members[$n] = [$arr[0], !$arr[1]];
		return true;
	}

	public function usingPartyChat(Player $player) : bool{
		$n = $player->getName();
		if(!isset($this->members[$n])){
			return false;
		}

		return $this->members[$n][1];
	}

	public function getMemberRank(Player $player) : ?int{
		$n = $player->getName();
		if(!isset($this->members[$n])){
			return null;
		}

		return $this->members[$n][0];
	}

	public function isMember(string $player) : bool{
		return isset($this->members[$player]);
	}

	public function isLeader(string $player) : bool{
		return isset($this->members[$player]) and $this->members[$player][0] == PartyManager::RANK_LEADER;
	}

	public function sendMessage(Player $player, string $string) : void{
		$prefix = PartyManager::rankToString($this->getMemberRank($player) ?? PartyManager::RANK_MEMBER);
		foreach($this->getMembers(true) as $p){
			$p->sendMessage(TextFormat::YELLOW . '[' . TextFormat::GOLD . 'Party' . TextFormat::YELLOW . ']: ' . TextFormat::GRAY . ' [' . $prefix . TextFormat::GRAY . '] ' . TextFormat::WHITE . $player->getName() . ': ' . $string);
		}
	}
}
