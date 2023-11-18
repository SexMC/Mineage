<?php
declare(strict_types=1);

namespace MineageBunkersLobby\Scoreboard\Type;

use MineageBunkersLobby\MineageBunkersLobby;
use MineageBunkersLobby\Party\Party;
use MineageBunkersLobby\PreGame\PreGame;
use MineageBunkersLobby\Scoreboard\Scoreboard;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function date;

class VotingScoreboardType extends Scoreboard{

	public const ID = 101;

	protected int $id = self::ID;

	protected function initialize() : void{
		$sCfg = MineageBunkersLobby::getInstance()->getConfig()->getNested('scoreboards')['voting'];
		$this->title = TextFormat::colorize($sCfg['title']);
		$this->lines[] = [0, TextFormat::colorize($sCfg['lines']['first-divider'])];
		$this->lines[] = [1, TextFormat::colorize($sCfg['lines']['team'])];
		$this->lines[] = [2, TextFormat::colorize($sCfg['lines']['map'])];
		$this->lines[] = [3, '  '];
		$this->lines[] = [4, TextFormat::colorize($sCfg['lines']['map-votes'])];
		$this->lines[] = [5, TextFormat::colorize($sCfg['lines']['map-votes-classic'])];
		$this->lines[] = [6, '   '];
		$this->lines[] = [7, TextFormat::colorize($sCfg['lines']['how-to-vote'])];
		$this->lines[] = [8, '    '];
		$this->lines[] = [9, TextFormat::colorize($sCfg['lines']['voting-end'])];
		$this->lines[] = [10, TextFormat::colorize($sCfg['lines']['final-divider']) . '§7'];
	}

	public function update(Player $player = null, Party|PreGame $object = null) : void{
		if(!$object instanceof PreGame){
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
		foreach($this->lines as $line){
			$s = str_replace(
				[
					'@v_team',
					'@v_map',
					'@v_classic-votes',
					'@v_voting-ends'
				],
				[
					$object->getPlayerTeam($player, true),
					$object->getMap(),
					$object->getVotesForMap('Classic'),
					date('i:s', $object->getCountdown()),
				],
				$line[1]);

			$lines[] = [$line[0], $s];
		}
		$array[] = $lines;

		foreach($array[1] as $line){
			parent::removeLine($player, $line[0]);
			parent::createLine($player, $line[0], $line[1]);
		}
	}
}
