<?php

declare(strict_types=1);

namespace MineageBunkersLobby\Manager;

use MineageBunkersLobby\Scoreboard\Scoreboard;
use MineageBunkersLobby\Scoreboard\Type\OnlineScoreboardType;
use MineageBunkersLobby\Scoreboard\Type\ReadyScoreboardType;
use MineageBunkersLobby\Scoreboard\Type\VotingScoreboardType;
use MineageBunkersLobby\Scoreboard\Type\WaitingScoreboardType;

class ScoreboardManager{

	private array $scoreboards = [];

	public function __construct(){
		$this->scoreboards[OnlineScoreboardType::ID] = new OnlineScoreboardType();
		$this->scoreboards[VotingScoreboardType::ID] = new VotingScoreboardType();
		$this->scoreboards[ReadyScoreboardType::ID] = new ReadyScoreboardType();
		$this->scoreboards[WaitingScoreboardType::ID] = new WaitingScoreboardType();
	}

	public function getScoreboard(int $key) : ?Scoreboard{
		if(!isset($this->scoreboards[$key])){
			return null;
		}

		return $this->scoreboards[$key];
	}
}
