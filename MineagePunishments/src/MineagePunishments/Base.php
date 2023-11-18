<?php
declare(strict_types=1);

namespace MineagePunishments;

use MineagePunishments\Command\ACBanCommand;
use MineagePunishments\Command\AliasCommand;
use MineagePunishments\Command\BanCommand;
use MineagePunishments\Command\BlacklistCommand;
use MineagePunishments\Command\MuteCommand;
use MineagePunishments\Command\PunishmentHistoryCommand;
use MineagePunishments\Command\UnbanCommand;
use MineagePunishments\Command\UnblacklistCommand;
use MineagePunishments\Command\UnmuteCommand;
use MineagePunishments\Listener\AnticheatListener;
use MineagePunishments\Listener\GeneralListener;
use MineagePunishments\Manager\AdminManager;
use MineagePunishments\Manager\FormManager;
use pocketmine\plugin\PluginBase;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;

class Base extends PluginBase{

	public static Base $instance;

	private DataConnector $network;
	private AdminManager $adminManager;
	private FormManager $formManager;

	public static function getInstance() : Base{
		return self::$instance;
	}

	public function getNetwork() : ?DataConnector{
		return $this->network;
	}

	public function getAdminManager() : AdminManager{
		return $this->adminManager;
	}

	public function getFormManager() : FormManager{
		return $this->formManager;
	}

	protected function onLoad() : void{
		$mysql = $this->getConfig()->get('mysql');
		$this->network = libasynql::create($this, ['type' => 'mysql', 'mysql' => [
			'host' => $mysql['host'],
			'username' => $mysql['username'],
			'password' => $mysql['password'],
			'schema' => $mysql['schema']]], ['mysql' => 'mysql.sql']);
		$this->network->executeGeneric('mineage.create.history.table', [], null, function(SqlError $error_) use (&$error){
			$error = $error_;
		});

		$this->network->executeGeneric('mineage.create.aliases.table', [], null, function(SqlError $error_) use (&$error){
			$error = $error_;
		});

		$this->network->executeGeneric('mineage.create.bans.table', [], null, function(SqlError $error_) use (&$error){
			$error = $error_;
		});

		$this->network->executeGeneric('mineage.create.mutes.table', [], null, function(SqlError $error_) use (&$error){
			$error = $error_;
		});

		$this->network->waitAll();
	}

	protected function onEnable() : void{
		self::$instance = $this;

		foreach(['ban', 'banlist', 'pardon'] as $originalCmd){
			if(($cmd = $this->getServer()->getCommandMap()->getCommand($originalCmd)) !== null){
				$this->getServer()->getCommandMap()->unregister($cmd);
			}
		}

		$this->getServer()->getCommandMap()->registerAll('MineagePunishments', [
			new PunishmentHistoryCommand($this),
			new ACBanCommand($this),
			new BanCommand($this),
			new UnbanCommand($this),
			new BlacklistCommand($this),
			new UnblacklistCommand($this),
			new MuteCommand($this),
			new UnmuteCommand($this),
			new AliasCommand($this),
		]);

		$this->adminManager = new AdminManager($this);
		$this->formManager = new FormManager($this);

		$this->getServer()->getPluginManager()->registerEvents(new GeneralListener($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new AnticheatListener($this), $this);
	}
}
