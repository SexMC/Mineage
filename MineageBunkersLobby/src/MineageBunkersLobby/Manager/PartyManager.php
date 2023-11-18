<?php
declare(strict_types=1);

namespace MineageBunkersLobby\Manager;

use MineageBunkersLobby\Party\Party;
use MineageBunkersLobby\Party\PartyInvite\PartyInvite;
use pocketmine\player\Player;

class PartyManager{

	public const CAPACITY = 5;

	public const RANK_LEADER = 0;
	public const RANK_MEMBER = 1;

	private array $parties = [];
	private array $partyInvites = [];

	public function createParty(array $players) : void{
		$party = new Party($players);
		$this->parties[$party->getId()] = $party;
	}

	public function closeParty(Party $party) : void{
		$id = $party->getId();
		if(!isset($this->parties[$id])){
			return;
		}

		foreach($this->partyInvites as $invite){
			if($invite instanceof PartyInvite and $invite->getParty() !== null){
				$otherId = $invite->getParty()->getId();
				if($otherId == $id){
					$this->closeInvite($otherId);
				}
			}
		}

		unset($this->parties[$id]);
	}

	public function createInvite(Player $sender, Player $target, ?Party $party = null) : void{
		$invite = new PartyInvite($sender, $target, $party);
		$this->partyInvites[$invite->getId()] = $invite;
	}

	public function closeInvite(int $id) : void{
		if(!isset($this->partyInvites[$id])){
			return;
		}

		unset($this->partyInvites[$id]);
	}

	public function doesPartyIdExist(int $int) : bool{
		return isset($this->parties[$int]);
	}

	public function doesInviteIdExist(int $int) : bool{
		return isset($this->partyInvites[$int]);
	}

	public function getPartyFromPlayer(string $player) : ?Party{
		foreach($this->parties as $party){
			if($party instanceof Party and $party->isMember($player)){
				return $party;
			}
		}

		return null;
	}

	public function getInviteForFromSender(string $sender, string $target) : ?PartyInvite{
		foreach($this->partyInvites as $invite){
			if($invite instanceof PartyInvite and $invite->getSender()->getName() == $sender and $invite->getTarget()->getName() == $target){
				return $invite;
			}
		}

		return null;
	}

	public static function rankToString(int $int) : string{
		return match ($int) {
			self::RANK_LEADER => 'Leader',
			default => 'Member',
		};
	}
}
