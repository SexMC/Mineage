<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Utils;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use pocketmine\Server;

class Scoreboard{
	private const objectiveName = "objective";
	private const criteriaName = "dummy";

	private const MIN_LINES = 1;
	private const MAX_LINES = 15;

	public const SORT_ASCENDING = 0;
	public const SORT_DESCENDING = 1;

	public const SLOT_LIST = "list";
	public const SLOT_SIDEBAR = "sidebar";
	public const SLOT_BELOW_NAME = "belowname";

	private static array $scoreboards = [];

	public static function setScore(Player $player, string $displayName, int $slotOrder = self::SORT_ASCENDING, string $displaySlot = self::SLOT_SIDEBAR) : void{
		if(isset(self::$scoreboards[$player->getName()])){
			self::removeScore($player);
		}

		$packet = new SetDisplayObjectivePacket();
		$packet->displaySlot = $displaySlot;
		$packet->objectiveName = self::objectiveName;
		$packet->displayName = $displayName;
		$packet->criteriaName = self::criteriaName;
		$packet->sortOrder = $slotOrder;
		$player->getNetworkSession()->sendDataPacket($packet);

		self::$scoreboards[$player->getName()] = self::objectiveName;
	}

	public static function removeScore(Player $player) : void{
		$objectiveName = self::objectiveName;

		$packet = new RemoveObjectivePacket();
		$packet->objectiveName = $objectiveName;
		$player->getNetworkSession()->sendDataPacket($packet);

		if(isset(self::$scoreboards[($player->getName())])){
			unset(self::$scoreboards[$player->getName()]);
		}
	}

	public static function getScoreboards() : array{
		return self::$scoreboards;
	}

	public static function hasScore(Player $player) : bool{
		return isset(self::$scoreboards[$player->getName()]);
	}

	public static function setScoreLine(Player $player, int $line, string $message) : void{
		if(!isset(self::$scoreboards[$player->getName()])){
			Server::getInstance()->getLogger()->error("Cannot set a score to a player with no scoreboard");
			return;
		}
		if($line < self::MIN_LINES || $line > self::MAX_LINES){
			Server::getInstance()->getLogger()->error("Score must be between the value of " . self::MIN_LINES . " to " . self::MAX_LINES . ".");
			Server::getInstance()->getLogger()->error($line . " is out of range");
			return;
		}

		$entry = new ScorePacketEntry();
		$entry->objectiveName = self::objectiveName;
		$entry->type = $entry::TYPE_FAKE_PLAYER;
		$entry->customName = $message;
		$entry->score = $line;
		$entry->scoreboardId = $line;

		$packet = new SetScorePacket();
		$packet->type = $packet::TYPE_CHANGE;
		$packet->entries[] = $entry;
		$player->getNetworkSession()->sendDataPacket($packet);
	}
}
