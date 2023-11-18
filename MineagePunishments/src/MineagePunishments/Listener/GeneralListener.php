<?php
declare(strict_types=1);

namespace MineagePunishments\Listener;

use MineagePunishments\Base;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\utils\TextFormat;
use function array_key_first;
use function array_key_last;
use function array_unique;
use function count;
use function is_int;
use function is_string;
use const PHP_INT_MAX;

class GeneralListener implements Listener{

	public function __construct(private readonly Base $plugin){ }

	public function onLogin(PlayerLoginEvent $event) : void{
		$player = $event->getPlayer();
		$extraInfo = $player->getPlayerInfo()->getExtraData();
		$extraInfoDid = $extraInfo['DeviceId'];
		$extraInfoCid = $extraInfo['ClientRandomId'];
		if(!is_string($extraInfoDid) === null or $extraInfoDid === "" or is_int($extraInfoCid) === null){
			$player->kick(TextFormat::RED . 'You\'re sending invalid data. Please restart Minecraft and try again.');
			return;
		}

		$ip = $player->getNetworkSession()->getIp();
		$this->plugin->getLogger()->info("Getting aliases of " . $player->getName());
		$this->plugin->getNetwork()->executeSelect('mineage.get.aliases', [], function($rows) use($player, $ip, $extraInfoDid, $extraInfoCid) : void{
			$this->plugin->getLogger()->info("Got " . count($rows) . " total rows");

			$aliasesSet = [$player->getName() => true];
			foreach($rows as $row){
				if(
					str_contains($row['IP'], $ip) or
					str_contains($row['DID'], $extraInfoDid) or
					str_contains($row['CID'], (string) $extraInfoCid)
				){
					$aliasesSet[$row['Player']] = true;
				}
			}
			$aliases = array_keys($aliasesSet);
			$this->plugin->getLogger()->info("Found " . count($aliases) . " aliases of " . $player->getName() . " (" . implode(", ", $aliases) . ")");

			$banInfos = $this->plugin->getAdminManager()->getMultipleActiveBan($aliases);
			if(count($banInfos) === 0){
				return;
			}

			if(count($banInfos) === 1){
				$bannedAlias = array_key_first($banInfos);
				$banInfo = $banInfos[$bannedAlias];
			}else{
				foreach($banInfos as $key => $value){
					if($value['expires'] === -1){
						$bannedAlias = $key;
						$banInfo = $value;
						break;
					}
				}

				if(!isset($bannedAlias, $banInfo)){
					uasort($banInfos, fn($a, $b) => $a['expires'] <=> $b['expires']);
					$bannedAlias = array_key_last($banInfos);
					$banInfo = $banInfos[$bannedAlias];
				}
			}

			$expires = $banInfo['expires'];
			$isBlacklist = $expires === -1;
			if($isBlacklist or $expires > time()){
				if($player->isConnected()){
					$player->kick(
						TextFormat::colorize($this->plugin->getConfig()->getNested((!$isBlacklist ? 'title-screen-banned-message' : 'title-screen-blacklisted-message'), false)) . TextFormat::EOL .
						($player->getName() !== $bannedAlias ? TextFormat::DARK_RED . 'Account: ' . $bannedAlias . TextFormat::EOL : '') .
						TextFormat::WHITE . 'Reason: ' . TextFormat::GRAY . $banInfo['reason'] . ' | ' .
						($isBlacklist ? '' : TextFormat::WHITE . 'Expires on: ' . TextFormat::GRAY . date('F j, Y @ g:i a', $expires) . TextFormat::EOL) .
						($isBlacklist ? TextFormat::colorize($this->plugin->getConfig()->getNested('title-screen-punished-message-appeal', false)) : '')
					);
				}else{
					$this->plugin->getLogger()->info("Player " . $player->getName() . " is not connected.");
				}
			}else{
				$this->plugin->getLogger()->info("Ban for " . $player->getName() . " has expired.");
				$this->plugin->getAdminManager()->removeActiveBan(null, $player->getName());
			}
		});
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$ip = $player->getNetworkSession()->getIp();
		$extraInfo = $player->getPlayerInfo()->getExtraData();
		$extraInfoDid = $extraInfo['DeviceId'];
		$extraInfoCid = $extraInfo['ClientRandomId'];
		$this->plugin->getNetwork()?->executeSelect('mineage.get.aliases.entry', ['player' => $player->getName()], function($rows) use($player, $ip, $extraInfoDid, $extraInfoCid) : void{
			if(count($rows) > 0){
				$row = $rows[0];

				$ipArray = [$ip];
				if($row['IP'] !== ""){
					foreach(explode(',', $row['IP']) as $string){
						$ipArray[] = $string;
					}
				}

				$didArray = [$extraInfoDid];
				if($row['DID'] !== ""){
					foreach(explode(',', $row['DID']) as $string){
						$didArray[] = $string;
					}
				}

				if($extraInfoCid === PHP_INT_MAX){
					// Client ids above this value are capped to this value creating false positives
					$this->plugin->getLogger()->warning("Client id $extraInfoCid of " . $player->getName() . " is above " . PHP_INT_MAX . ", not adding to aliases.");
				}else{
					$cidArray = [$extraInfoCid];
					if($row['CID'] !== ""){
						foreach(explode(',', $row['CID']) as $string){
							$cidArray[] = $string;
						}
					}
				}

				$this->plugin->getNetwork()?->executeGeneric('mineage.update.aliases.entry', [
					'player' => $player->getName(),
					'ip' => implode(',', array_unique($ipArray)),
					'did' => implode(',', array_unique($didArray)),
					'cid' => isset($cidArray) ? implode(',', array_unique($cidArray)) : $row['CID']
				]);
			}else{
				if($extraInfoCid === PHP_INT_MAX){
					$this->plugin->getLogger()->warning("Client id $extraInfoCid of " . $player->getName() . " is above " . PHP_INT_MAX . ", not adding to aliases.");
					$cid = "";
				}else{
					$cid = (string) $extraInfoCid;
				}

				$this->plugin->getNetwork()?->executeGeneric('mineage.register.aliases.entry', [
					'player' => $player->getName(),
					'ip' => $ip,
					'did' => $extraInfoDid,
					'cid' => $cid
				]);
			}
		});
	}

	public function onQuit(PlayerQuitEvent $event) : void{
		if(!$event->getPlayer()->spawned){
			$event->setQuitMessage('');
		}
	}

	public function onChat(PlayerChatEvent $event) : void{
		$player = $event->getPlayer();
		if(!$this->plugin->getServer()->isOp($player->getName()) and $this->plugin->getAdminManager()->isMuted($player->getName())){
			if($this->plugin->getAdminManager()->checkMute($player)){
				$this->plugin->getAdminManager()->removeActiveMute(null, $player->getName());
				return;
			}

			$player->sendMessage(TextFormat::RED . 'You are muted, your message was not sent.');
			$event->cancel();
		}
	}
}
