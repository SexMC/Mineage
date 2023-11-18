<?php
declare(strict_types=1);

namespace MineageBunkersLobby\Party\PartyInvite;

use MineageBunkersLobby\Manager\PartyManager;
use MineageBunkersLobby\MineageBunkersLobby;
use MineageBunkersLobby\Party\Party;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function mt_rand;

class PartyInvite{

	private int $id = -1;
	private int $expire;

	public function __construct(private readonly Player $sender, private readonly Player $target, private readonly ?Party $party = null){
		$int = mt_rand(1, 1000);
		while($this->id == -1 and !MineageBunkersLobby::getInstance()->getPartyManager()->doesInviteIdExist($int)){
			$this->id = $int;
		}

		$this->expire = time() + 30;

		$this->sender->sendMessage(TextFormat::GREEN . 'You invited ' . $this->target->getName() . ' to join your party.');
		$this->target->sendMessage(TextFormat::BOLD . TextFormat::AQUA . 'Invite > ' . $this->sender->getName() . TextFormat::RESET . TextFormat::YELLOW . ' invited you to join their party.');
	}

	public function getId() : int{
		return $this->id;
	}

	public function getSender() : Player{
		return $this->sender;
	}

	public function getTarget() : Player{
		return $this->target;
	}

	public function getParty() : ?Party{
		return $this->party;
	}

	public function getExpire() : int{
		return $this->expire;
	}

	public function accept() : void{
		MineageBunkersLobby::getInstance()->getPartyManager()->closeInvite($this->id);
		if(!$this->sender->isConnected() or !$this->target->isConnected()){
			return;
		}

		$this->sender->sendMessage(TextFormat::GREEN . $this->target->getName() . ' accepted your party invitation.');
		$this->target->sendMessage(TextFormat::GREEN . 'You accepted ' . $this->sender->getName() . '\'s party invitation.');

		if($this->party === null){
			$a = [
				$this->sender->getName() => [PartyManager::RANK_LEADER, false],
				$this->target->getName() => [PartyManager::RANK_MEMBER, false],
			];

			MineageBunkersLobby::getInstance()->getPartyManager()->createParty($a);
		}else{
			$this->party->addMember($this->target);
		}
	}

	public function decline() : void{
		MineageBunkersLobby::getInstance()->getPartyManager()->closeInvite($this->id);
		if(!$this->sender->isConnected() or !$this->target->isConnected()){
			return;
		}

		$this->sender->sendMessage(TextFormat::RED . $this->target->getName() . ' declined your party invitation.');
		$this->target->sendMessage(TextFormat::GREEN . 'You declined ' . $this->sender->getName() . '\'s party invitation.');
	}
}
