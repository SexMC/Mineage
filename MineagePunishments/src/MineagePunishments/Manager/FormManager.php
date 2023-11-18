<?php
declare(strict_types=1);

namespace MineagePunishments\Manager;

use MineagePunishments\Base;
use MineagePunishments\FormAPI\CustomForm;
use MineagePunishments\FormAPI\SimpleForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class FormManager{

	public function __construct(private readonly Base $plugin){ }

	public function openBanForm(Player $player) : void{
		$players = [];
		$keysBanReasons = array_keys($this->plugin->getAdminManager()->getBanReasons());
		foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
			$isOnlineOp = $this->plugin->getServer()->isOp($online->getName());
			if(($isOnlineOp and $this->plugin->getServer()->isOp($player->getName())) or !$isOnlineOp){
				$players[] = $online->getName();
			}
		}

		$form = new CustomForm(function($player, $data = null) use ($players, $keysBanReasons) : void{
			if($data === null){
				return;
			}

			$username = empty($data[0]) ? $players[$data[1]] : $data[0];
			$reason = $keysBanReasons[$data[3]];
			$expires = time() + ($this->plugin->getAdminManager()->getBanReasons()[$reason]['Days'] * 86400) + ($this->plugin->getAdminManager()->getBanReasons()[$reason]['Hours'] * 3600);

			$this->plugin->getAdminManager()->addActiveBan($player, $username, $reason, $player->getName(), time(), $expires, $data[2]);
		});

		$form->addInput('Type a username', 'Username (caps non-sensitive)');
		if(!empty($players)){
			$form->addDropdown('Find a username', $players);
		}
		$form->addToggle('Silent', false);
		$form->addDropdown('Reason', $keysBanReasons);
		$form->setTitle('Ban');
		$player->sendForm($form);
	}

	public function openBlacklistForm(Player $player) : void{
		$players = [];
		foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
			$isOnlineOp = $this->plugin->getServer()->isOp($online->getName());
			if(($isOnlineOp and $this->plugin->getServer()->isOp($player->getName())) or !$isOnlineOp){
				$players[] = $online->getName();
			}
		}

		$form = new CustomForm(function($player, $data = null) use ($players) : void{
			if($data === null){
				return;
			}

			$username = empty($data[0]) ? $players[$data[1]] : $data[0];
			$this->plugin->getAdminManager()->addActiveBan($player, $username, $data[3], $player->getName(), time(), -1, $data[2]);
		});

		$form->addInput('Type a username', 'Username (caps non-sensitive)');
		if(!empty($players)){
			$form->addDropdown('Find a username', $players);
		}
		$form->addToggle('Silent', false);
		$form->addInput('Reason', 'Leave a reason');
		$form->setTitle('Blacklist');
		$player->sendForm($form);
	}

	public function openMuteForm(Player $player) : void{
		$players = [];
		$keysMuteReasons = array_keys($this->plugin->getAdminManager()->getMuteReasons());
		foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
			$isOnlineOp = $this->plugin->getServer()->isOp($online->getName());
			if(($isOnlineOp and $this->plugin->getServer()->isOp($player->getName())) or !$isOnlineOp){
				$players[] = $online->getName();
			}
		}

		$form = new CustomForm(function($player, $data = null) use ($players, $keysMuteReasons) : void{
			if($data === null){
				return;
			}

			$username = empty($data[0]) ? $players[$data[1]] : $data[0];
			$reason = $keysMuteReasons[$data[2]];
			$expires = time() + ($this->plugin->getAdminManager()->getMuteReasons()[$reason]['Days'] * 86400) + ($this->plugin->getAdminManager()->getMuteReasons()[$reason]['Hours'] * 3600);

			$this->plugin->getAdminManager()->addActiveMute($player, $username, $reason, $player->getName(), time(), $expires);
		});

		$form->addInput('Type a username', 'Username (caps non-sensitive)');
		if(!empty($players)){
			$form->addDropdown('Find a username', $players);
		}
		$form->addDropdown('Reason', $keysMuteReasons);
		$form->setTitle('Mute');
		$player->sendForm($form);
	}

	public function openActivePunishmentsForm(Player $player, int $punishment = 0) : void{
		switch($punishment){
			default:
				$players = $this->plugin->getAdminManager()->getActiveBans();
				$title = 'Unban';
				break;
			case 1:
				$players = $this->plugin->getAdminManager()->getActiveBans(true);
				$title = 'Unblacklist';
				break;
			case 2:
				$players = $this->plugin->getAdminManager()->getActiveMutes();
				$title = 'Unmute';
				break;
		}

		$form = new SimpleForm(function($player, $data = null) use ($punishment, $players) : void{
			if($data === null or !isset($players[$data])){
				return;
			}

			$this->openActivePunishmentForm($player, $punishment, $data, $players[$data]);
		});

		foreach(array_keys($players) as $pl){
			$form->addButton($pl, -1, '', $pl);
		}
		$form->setTitle($title);
		$player->sendForm($form);
	}

	public function openActivePunishmentForm(Player $player, int $punishment, string $username, array $info) : void{
		if(!isset($info['reason']) or !isset($info['staff']) or !isset($info['happened']) or !isset($info['expires'])){
			return;
		}

		$form = new SimpleForm(function($player, $data = null) use ($punishment, $username, $info) : void{
			if($data === null){
				return;
			}

			switch($data){
				case 'lift':
					$punishment < 2 ? $this->plugin->getAdminManager()->removeActiveBan($player, $username, false, $info['expires'] == -1) : $this->plugin->getAdminManager()->removeActiveMute($player, $username);
					break;
				case 'back':
					$this->openActivePunishmentsForm($player, $punishment);
					break;
			}
		});

		$expires = $info['expires'] == -1 ? '' : 'Expires on: ' . date('F j, Y @ g:i a', $info['expires']);
		$form->setContent(TextFormat::GRAY . TextFormat::ITALIC .
			'Punishment issued on ' . date('F j, Y @ g:i a', $info['happened']) . TextFormat::EOL . TextFormat::EOL . TextFormat::RESET . TextFormat::WHITE .
			'Reason: ' . $info['reason'] . TextFormat::EOL .
			'Staff: ' . $info['staff'] . TextFormat::EOL .
			$expires
		);
		$form->addButton('Lift Punishment', -1, '', 'lift');
		//$form->addButton('Punishment History', -1, '', 'history');//TODO
		$form->addButton('Back', -1, '', 'back');
		$form->setTitle($username . '\'s Punishment Info');
		$player->sendForm($form);
	}
}
