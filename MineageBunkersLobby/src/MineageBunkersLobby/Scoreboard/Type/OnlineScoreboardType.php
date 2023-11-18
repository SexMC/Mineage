<?php
declare(strict_types=1);

namespace MineageBunkersLobby\Scoreboard\Type;

use MineageBunkersLobby\MineageBunkersLobby;
use MineageBunkersLobby\Party\Party;
use MineageBunkersLobby\PreGame\PreGame;
use MineageBunkersLobby\Scoreboard\Scoreboard;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function count;
use function str_contains;

class OnlineScoreboardType extends Scoreboard{

	public const ID = 100;

	protected int $id = self::ID;

	protected function initialize() : void{
		$sCfg = MineageBunkersLobby::getInstance()->getConfig()->getNested('scoreboards')['online'];
		$this->title = TextFormat::colorize($sCfg['title']);
		$this->lines[] = [0, TextFormat::colorize($sCfg['lines']['first-divider'])];
		$this->lines[] = [1, TextFormat::colorize($sCfg['lines']['online'])];
		$this->lines[] = [2, TextFormat::colorize($sCfg['lines']['in-game'])];
		$this->lines[] = [3, TextFormat::colorize($sCfg['lines']['in-queue'])];
		$this->lines[] = [4, TextFormat::colorize($sCfg['lines']['your-party'])];
		$this->lines[] = [5, '  '];
		$this->lines[] = [6, TextFormat::colorize($sCfg['lines']['queued'])];
		$this->lines[] = [7, TextFormat::colorize($sCfg['lines']['final-divider']) . '§7'];
	}

	public function update(Player $player = null, Party|PreGame $object = null) : void{
		if($object instanceof PreGame){
			return;
		}

		$session = MineageBunkersLobby::getInstance()->getSessionManager()->getSession($player);
		if($session === null){
			return;
		}
		if($session->getCurrentScoreboard() != self::ID){
			parent::send($player, $session, false);
		}

		$array = [$this->title];
		$lines = [];
		$isQueued = MineageBunkersLobby::getInstance()->getGameManager()->isQueued($player);
		foreach($this->lines as $line){
			$s = str_replace(
				[
					'@v_online',
					'@v_in-game',
					'@v_in-queue',
					'@v_your-party',
					'  ',
					'@v_queued',
				],
				[
					count(Server::getInstance()->getOnlinePlayers()),
					count(MineageBunkersLobby::getInstance()->getGameManager()->getInGame()),
					count(MineageBunkersLobby::getInstance()->getGameManager()->getQueued()),
					($object === null ? self::REMOVE_LINE_IDENTIFIER : count($object->getMembers() ?? 1) . '/' . Party::CAPACITY),
					(!$isQueued ? self::REMOVE_LINE_IDENTIFIER : '  '),
					(!$isQueued ? self::REMOVE_LINE_IDENTIFIER : TextFormat::ITALIC . TextFormat::BOLD . TextFormat::GREEN . 'Queued for game'),
				],
				$line[1]);

			$lines[] = [$line[0], $s];
		}
		$array[] = $lines;

		foreach($array[1] as $line){
			parent::removeLine($player, $line[0]);
			if(!str_contains($line[1], self::REMOVE_LINE_IDENTIFIER)){
				parent::createLine($player, $line[0], $line[1]);
			}
		}
	}
}
