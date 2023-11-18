<?php
declare(strict_types=1);

namespace MineageBunkersLobby\session;

use MineageBunkersLobby\FormAPI\FormSpamFix;
use MineageBunkersLobby\MineageBunkersLobby;
use MineageBunkersLobby\Scoreboard\Type\OnlineScoreboardType;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Session{

	public const QUEUE_ATTEMPTS = 3;
	public const QUEUE_WAIT_TIME = 5;

	private int $chatWait = 1;
	private int $queueWait = -1;

	private int $queueAttempts = self::QUEUE_ATTEMPTS;

	private int $currentScoreboard = 0;
	private string $currentVote = '';

	public function __construct(private readonly Player $player){}

	public function onJoin() : void{
		$this->player->setNameTag(TextFormat::GREEN . $this->player->getDisplayName());
		$this->player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()?->getSpawnLocation());

		$this->player->setGamemode(GameMode::ADVENTURE());
		$this->player->setFlying(false);
		$this->player->setAllowFlight(false);

		$this->player->getInventory()->setHeldItemIndex(0);
		$this->player->getArmorInventory()->clearAll();
		$this->player->extinguish();
		$this->player->setHealth($this->player->getMaxHealth());
		$this->player->getHungerManager()->setFood($this->player->getHungerManager()->getMaxFood());
		$this->player->setAbsorption(0);

		MineageBunkersLobby::getInstance()->getUtils()->sendSpawnKit($this->player);

		//MineageBunkersLobby::getInstance()->getScoreboardManager()->sendScoreboard($this->player);
		//MineageBunkersLobby::getInstance()->getScoreboardManager()->updateScoreboardForAll(ScoreboardManager::LINE_ONLINE);
		MineageBunkersLobby::getInstance()->getScoreboardManager()->getScoreboard(OnlineScoreboardType::ID)->send($this->player);
	}

	public function onQuit() : void{
		//Server::getInstance()->removeOnlinePlayer($this->player);//at this point the server still counts this player online, therefore not updating the scoreboard
		//MineageBunkersLobby::getInstance()->getScoreboardManager()->removeScoreboard($this->player);
		//MineageBunkersLobby::getInstance()->getScoreboardManager()->updateScoreboardForAll(ScoreboardManager::LINE_ONLINE);
		MineageBunkersLobby::getInstance()->getGameManager()->unqueuePlayer($this->player, false);
		MineageBunkersLobby::getInstance()->getGameManager()->getGameFromPlayer($this->player->getName())?->removePlayer($this->player, $this);

		if(FormSpamFix::isLocked($this->player)){
			FormSpamFix::unlock($this->player);
		}
	}

	public function getChatWait() : int{
		return $this->chatWait;
	}

	public function setChatWait(int $chatWait) : void{
		$this->chatWait = $chatWait;
	}

	public function getQueueWait() : int{
		return $this->queueWait;
	}

	public function doQueueAttempt() : void{
		$this->queueAttempts--;
		if($this->queueAttempts == 0){
			$this->queueWait = time() + self::QUEUE_WAIT_TIME;
			$this->resetQueueAttempts();
		}

		if($this->queueWait !== -1 and $this->queueWait < time()){
			$this->queueWait = -1;
		}
	}

	public function resetQueueAttempts() : void{
		$this->queueAttempts = self::QUEUE_ATTEMPTS;
	}

	public function getCurrentScoreboard() : int{
		return $this->currentScoreboard;
	}

	public function setCurrentScoreboard(int $currentScoreboard = 0) : void{
		$this->currentScoreboard = $currentScoreboard;
	}

	public function getCurrentVote() : string{
		return $this->currentVote;
	}

	public function setCurrentVote(string $currentVote) : void{
		$this->currentVote = $currentVote;
	}
}
