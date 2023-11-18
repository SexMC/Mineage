<?php
declare(strict_types=1);

namespace MineageBunkersLobby;

use MineageBunkersLobby\Command\PartyCommand;
use MineageBunkersLobby\Command\VoteCommand;
use MineageBunkersLobby\Listener\EventListener;
use MineageBunkersLobby\Manager\GameManager;
use MineageBunkersLobby\Manager\PartyManager;
use MineageBunkersLobby\Manager\ScoreboardManager;
use MineageBunkersLobby\Manager\SessionManager;
use MineageBunkersLobby\Task\QueueTask;
use MineageBunkersLobby\Utils\Utils;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;

class MineageBunkersLobby extends PluginBase{

	public static MineageBunkersLobby $instance;

	private ?DataConnector $network = null;

	private Utils $utils;

	private SessionManager $sessionManager;
	private ScoreboardManager $scoreboardManager;
	private GameManager $gameManager;
	private PartyManager $partyManager;

	public static function getInstance() : ?MineageBunkersLobby{
		return self::$instance;
	}

	public function getNetwork() : ?DataConnector{
		return $this->network;
	}

	public function getUtils() : Utils{
		return $this->utils;
	}

	public function getSessionManager() : SessionManager{
		return $this->sessionManager;
	}

	public function getScoreboardManager() : ScoreboardManager{
		return $this->scoreboardManager;
	}

	public function getGameManager() : GameManager{
		return $this->gameManager;
	}

	public function getPartyManager() : PartyManager{
		return $this->partyManager;
	}

	protected function onLoad() : void{
		self::$instance = $this;

		$mysql = $this->getConfig()->get('mysql');
		$this->network = libasynql::create($this, ['type' => 'mysql', 'mysql' => [
			'host' => $mysql['host'],
			'username' => $mysql['username'],
			'password' => $mysql['password'],
			'schema' => $mysql['schema']]], ['mysql' => 'mysql.sql']);
		$this->getNetwork()?->executeGeneric('mineage.create.game.servers.table', [], null, function(SqlError $error_){
			$this->getServer()->getLogger()->critical('create.game.servers.table:' . $error_);
		});
		$this->getNetwork()?->waitAll();

		foreach($this->getConfig()->get('servers') as $name => $_){
			$this->getNetwork()?->executeSelect('mineage.get.game.server.entry', ['server' => $name], function($rows) use ($name){
				if(sizeof($rows) == 0){
					$this->getNetwork()?->executeGeneric('mineage.register.game.server.entry', ['server' => $name], null, function(SqlError $error_){
						$this->getServer()->getLogger()->critical('register.game.server.entry:' . $error_);
					});
				}
			}, function(SqlError $error_){
				$this->getServer()->getLogger()->critical('get.game.server.entry:' . $error_);
			});
		}

		$this->utils = new Utils($this);
		$this->sessionManager = new SessionManager();
		$this->scoreboardManager = new ScoreboardManager();
		$this->gameManager = new GameManager($this);
		$this->partyManager = new PartyManager();

		$this->getServer()->getWorldManager()->loadWorld($this->getConfig()->get('world'));
	}

	protected function onEnable() : void{
		$this->getScheduler()->scheduleRepeatingTask(new QueueTask($this), 20);
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		$this->getServer()->getCommandMap()->registerAll('MineageBunkersLobby', [
			new VoteCommand($this, 'vote', TextFormat::RESET . 'Vote for a map prior to your game starting', '/vote <map>'),
			new PartyCommand($this, 'party', TextFormat::RESET . 'Band a team with your friends', '/party', ['p']),
		]);
	}
}
