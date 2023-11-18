<?php
declare(strict_types=1);

namespace MineagePunishments\Manager;

use MineagePunishments\Base;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class AdminManager{

	private array $banReasons;
	private array $muteReasons;

	private array $activeBans = [];
	private array $activeMutes = [];

	public function __construct(private readonly Base $plugin){
		$this->banReasons = $this->plugin->getConfig()->getNested('ban-reasons');
		$this->muteReasons = $this->plugin->getConfig()->getNested('mute-reasons');

		$this->plugin->getNetwork()->executeSelect('mineage.get.bans', [], function($rows){
			if(sizeof($rows) < 1){
				return;
			}

			foreach($rows as $row){
				$player = (string) $row['Player'];
				$expires = (int) $row['Expires'];
				if($expires == -1 or time() < $expires){
					$this->activeBans[$player] =
						[
							'reason' => (string) $row['Reason'],
							'staff' => (string) $row['Staff'],
							'happened' => (int) $row['Happened'],
							'expires' => $expires
						];
				}else{
					$this->removeActiveBan(null, $player, true);
				}
			}
		});

		$this->plugin->getNetwork()->executeSelect('mineage.get.mutes', [], function($rows){
			if(sizeof($rows) < 1){
				return;
			}

			foreach($rows as $row){
				$player = (string) $row['Player'];
				$expires = (int) $row['Expires'];
				if(time() < $expires){
					$this->activeMutes[$player] =
						[
							'reason' => (string) $row['Reason'],
							'staff' => (string) $row['Staff'],
							'happened' => (int) $row['Happened'],
							'expires' => $expires
						];
				}else{
					$this->removeActiveMute(null, $player, true);
				}
			}
		});
	}

	public function addActiveBan(null|CommandSender|Player $sender, string $player, string $reason, string $staff, int $happened, int $expires, bool $silent = false) : bool{
		$isBlacklist = $expires == -1;
		$english = $isBlacklist ? 'blacklisted' : 'banned';
		if(isset($this->activeBans[$player])){
			$sender?->sendMessage(TextFormat::RED . $player . ' is already ' . $english . '.');
			return false;
		}

		$this->activeBans[$player] =
			[
				'reason' => $reason,
				'staff' => $staff,
				'happened' => $happened,
				'expires' => $expires
			];

		$this->plugin->getNetwork()?->executeGeneric('mineage.register.bans.entry', [
			'player' => $player,
			'reason' => $reason,
			'staff' => $staff,
			'happened' => $happened,
			'expires' => $expires,
		]);

		$this->plugin->getNetwork()?->executeGeneric('mineage.register.history.entry', [
			'player' => $player,
			'pdate' => time(),
			'ptype' => $english,
			'reason' => $reason,
			'staff' => $staff,
		]);

		$this->plugin->getServer()->getPlayerExact($player)?->kick(
			TextFormat::colorize($this->plugin->getConfig()->getNested('title-screen-just-' . $english . '-message', false)) . TextFormat::EOL .
			TextFormat::WHITE . 'Reason: ' . TextFormat::GRAY . $reason . ' | ' .
			($isBlacklist ? '' : TextFormat::WHITE . 'Expires on: ' . TextFormat::GRAY . date('F j, Y @ g:i a', $expires)) . TextFormat::EOL .
			($isBlacklist ? TextFormat::colorize($this->plugin->getConfig()->getNested('title-screen-punished-message-appeal', false)) : '')
		);

		if(!$silent){
			$this->plugin->getServer()->broadcastMessage(TextFormat::colorize(
				str_replace(['@player', '@reason', '@staff'], [$player, $reason, $staff], $this->plugin->getConfig()->getNested('just-' . $english . '-public-message')))
			);
		}

		$sender?->sendMessage(TextFormat::GREEN . 'You ' . $english . ' the account ' . $player . '.');
		return true;
	}

	public function removeActiveBan(null|CommandSender|Player $sender, string $player, bool $dbOnly = false, bool $isBlacklist = false) : bool{
		$this->plugin->getNetwork()->executeGeneric('mineage.clear.bans.entry', ['player' => $player]);

		if($dbOnly){
			return true;
		}

		$english = $isBlacklist ? 'blacklisted' : 'banned';
		if(!isset($this->activeBans[$player])){
			$sender?->sendMessage(TextFormat::RED . $player . ' is not ' . $english . '.');
			return false;
		}

		if($isBlacklist and $this->activeBans[$player]['expires'] != -1){
			$sender?->sendMessage(TextFormat::RED . $player . ' is not blacklisted.');
			return false;
		}

		if(!$isBlacklist and $this->activeBans[$player]['expires'] == -1){
			$sender?->sendMessage(TextFormat::RED . $player . ' is not banned.');
			return false;
		}

		unset($this->activeBans[$player]);
		$sender?->sendMessage(TextFormat::GREEN . 'You un-' . $english . ' the account ' . $player . '.');
		return true;
	}

	public function addActiveMute(null|CommandSender|Player $sender, string $player, string $reason, string $staff, int $happened, int $expires) : bool{
		if(isset($this->activeMutes[$player])){
			$sender?->sendMessage(TextFormat::RED . $player . ' is already muted.');
			return false;
		}

		$this->activeMutes[$player] =
			[
				'reason' => $reason,
				'staff' => $staff,
				'happened' => $happened,
				'expires' => $expires
			];

		$this->plugin->getNetwork()?->executeGeneric('mineage.register.mutes.entry', [
			'player' => $player,
			'reason' => $reason,
			'staff' => $staff,
			'happened' => $happened,
			'expires' => $expires,
		]);

		$this->plugin->getNetwork()?->executeGeneric('mineage.register.history.entry', [
			'player' => $player,
			'pdate' => time(),
			'ptype' => 'mute',
			'reason' => $reason,
			'staff' => $staff,
		]);

		$sender?->sendMessage(TextFormat::GREEN . 'You muted the account ' . $player . '.');
		return true;
	}

	public function removeActiveMute(null|CommandSender|Player $sender, string $player, bool $dbOnly = false) : bool{
		$this->plugin->getNetwork()->executeGeneric('mineage.clear.mutes.entry', ['player' => $player]);

		if($dbOnly){
			return true;
		}

		if(!isset($this->activeMutes[$player])){
			$sender?->sendMessage(TextFormat::RED . $player . ' is not muted.');
			return false;
		}

		unset($this->activeMutes[$player]);
		$sender?->sendMessage(TextFormat::GREEN . 'You un-muted the account ' . $player . '.');
		return true;
	}

	public function getActiveBans(bool $blacklists = false) : array{
		$array = [];
		if($blacklists){
			foreach($this->activeBans as $player => $activeBan){
				if($activeBan['expires'] == -1){
					$array[$player] = $activeBan;
				}
			}
		}else{
			foreach($this->activeBans as $player => $activeBan){
				if($activeBan['expires'] != -1){
					$array[$player] = $activeBan;
				}
			}
		}

		return $array;
	}

	public function getBan(string $player) : ?array{
		if(isset($this->activeBans[$player]) and $this->activeBans[$player]['expires'] !== -1){
			return $this->activeBans[$player];
		}

		return null;
	}

	public function getBlacklist(string $player) : ?array{
		if(isset($this->activeBans[$player]) and $this->activeBans[$player]['expires'] === -1){
			return $this->activeBans[$player];
		}

		return null;
	}

	public function getMute(string $player) : ?array{
		return $this->activeMutes[$player] ?? null;
	}

	public function isBlacklisted(string $player) : bool{
		return $this->getBlacklist($player) !== null;
	}

	public function isBanned(string $player) : bool{
		return $this->getBan($player) !== null;
	}

	public function getMultipleActiveBan(array $players) : array{
		$array = [];
		foreach($players as $player){
			if(isset($this->activeBans[$player])){
				$array[$player] = $this->activeBans[$player];
			}
		}

		return $array;
	}

	public function isMuted(string $player) : bool{
		return isset($this->activeMutes[$player]);
	}

	public function checkMute(Player $player) : bool{
		if(!$this->isMuted($player->getName())){
			return false;
		}

		return $this->activeMutes[$player->getName()]['expires'] < time();
	}

	public function getActiveMutes() : array{
		return $this->activeMutes;
	}

	public function getBanReasons() : array{
		return $this->banReasons;
	}

	public function matchStringToBanReason(string $string) : ?string{
		foreach($this->banReasons as $reason => $detail){
			foreach($detail['aliases'] as $alias){
				if(strtolower($string) === $alias){
					return $reason;
				}
			}
		}

		return null;
	}

	public function getMuteReasons() : array{
		return $this->muteReasons;
	}

	public function matchStringToMuteReason(string $string) : ?string{
		foreach($this->muteReasons as $reason => $detail){
			foreach($detail['aliases'] as $alias){
				if(strtolower($string) === $alias){
					return $reason;
				}
			}
		}

		return null;
	}
}
