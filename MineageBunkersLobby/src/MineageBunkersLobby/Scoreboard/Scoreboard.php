<?php
declare(strict_types=1);

namespace MineageBunkersLobby\Scoreboard;

use MineageBunkersLobby\MineageBunkersLobby;
use MineageBunkersLobby\Party\Party;
use MineageBunkersLobby\PreGame\PreGame;
use MineageBunkersLobby\session\Session;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;

abstract class Scoreboard{

	public const REMOVE_LINE_IDENTIFIER = '{R:L}';

	protected array $lines = [];
	protected string $title = '';
	protected int $id = 0;

	public function __construct(){
		$this->initialize();
	}

	protected abstract function initialize() : void;

	public abstract function update(Player $player, Party|PreGame $object = null) : void;

	public function send(Player $player, Session $session = null, bool $update = true) : void{
		if($session === null){
			$session = MineageBunkersLobby::getInstance()->getSessionManager()->getSession($player);
			if($session === null){
				return;
			}
		}

		if($session->getCurrentScoreboard() == $this->id){
			return;
		}else{
			self::removeScoreboard($player, $session);
		}

		$session->setCurrentScoreboard($this->id);

		self::createTitle($player, $this->title);
		if($update){
			$this->update($player);
		}
	}

	protected static function removeScoreboard(Player $player, Session $session = null) : void{
		$packet = new RemoveObjectivePacket();
		$packet->objectiveName = 'objective';

		if($session === null){
			$session = MineageBunkersLobby::getInstance()->getSessionManager()->getSession($player);
		}

		$session?->setCurrentScoreboard();
		$player->getNetworkSession()->sendDataPacket($packet);
	}

	protected static function createTitle(Player $player, string $title) : void{
		$packet = new SetDisplayObjectivePacket();
		$packet->displaySlot = 'sidebar';
		$packet->objectiveName = 'objective';
		$packet->displayName = $title;
		$packet->criteriaName = 'dummy';
		$packet->sortOrder = 0;

		$player->getNetworkSession()->sendDataPacket($packet);
	}

	public static function createLine(Player $player, int $line, string $string) : void{
		$packetLine = new ScorePacketEntry();
		$packetLine->objectiveName = 'objective';
		$packetLine->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
		$packetLine->customName = ' ' . $string;
		$packetLine->score = $line;
		$packetLine->scoreboardId = $line;
		$packet = new SetScorePacket();
		$packet->type = SetScorePacket::TYPE_CHANGE;
		$packet->entries[] = $packetLine;

		$player->getNetworkSession()->sendDataPacket($packet);
	}

	public static function removeLine(Player $player, int $line) : void{
		$entry = new ScorePacketEntry();
		$entry->objectiveName = 'objective';
		$entry->score = $line;
		$entry->scoreboardId = $line;
		$packet = new SetScorePacket();
		$packet->type = SetScorePacket::TYPE_REMOVE;
		$packet->entries[] = $entry;

		$player->getNetworkSession()->sendDataPacket($packet);
	}
}
