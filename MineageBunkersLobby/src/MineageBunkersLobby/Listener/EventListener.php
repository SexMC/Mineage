<?php
declare(strict_types=1);

namespace MineageBunkersLobby\Listener;

use MineageBunkersLobby\Forms\SpectateForm;
use MineageBunkersLobby\MineageBunkersLobby;
use MineageBunkersLobby\PreGame\PreGame;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerExperienceChangeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function str_replace;

class EventListener implements Listener{

	public function __construct(private readonly MineageBunkersLobby $plugin){}

	/**
	 * @priority HIGHEST
	 * @
	 */
	public function onJoin(PlayerJoinEvent $event) : void{
		$event->setJoinMessage('');
		$this->plugin->getSessionManager()->createSession($event->getPlayer());
	}

	/**
	 * @priority HIGHEST
	 */
	public function onLeave(PlayerQuitEvent $event) : void{
		$event->setQuitMessage('');
		$this->plugin->getSessionManager()->removeSession($event->getPlayer());
	}

	/**
	 * @priority HIGHEST
	 */
	public function onChat(PlayerChatEvent $event) : void{
		$event->cancel();

		$player = $event->getPlayer();
		$session = $this->plugin->getSessionManager()->getSession($player);
		if($session === null){
			return;
		}

		if($session->getChatWait() > time()){
			return;
		}

		$message = $event->getMessage();
		$party = $this->plugin->getPartyManager()->getPartyFromPlayer($player->getName());
		$isInParty = $party !== null;
		if($isInParty and ($party->usingPartyChat($player) or $message[0] == '*')){
			$party->sendMessage($player, str_replace('*', '', $message));
		}else{
			$team = $this->plugin->getGameManager()->getGameFromPlayer($player->getName())?->getPlayerTeam($player, true);
			if($team !== null and $team != 'None'){
				$prefix = TextFormat::GRAY . '[' . $team . TextFormat::GRAY . '] ';
			}else{
				$prefix = '';
			}

			$this->plugin->getServer()->broadcastMessage($prefix . TextFormat::WHITE . $player->getName() . ': ' . $message);
		}

		$session->setChatWait(time() + 1);
	}

	/**
	 * @priority HIGHEST
	 */
	public function onItemUse(PlayerItemUseEvent $event) : void{
		$player = $event->getPlayer();
		$item = $event->getItem();

		$joinQueue = $this->plugin->getUtils()->getSpawnItem('join-queue');
		$leaveQueue = $this->plugin->getUtils()->getSpawnItem('leave-queue');
		$rejoinGame = $this->plugin->getUtils()->getSpawnItem('rejoin-game');

		$game = $this->plugin->getGameManager()->getGameFromPlayer($player->getName());
		switch($item->getCustomName()){
			case $joinQueue->getCustomName():
				if($this->plugin->getGameManager()->queuePlayer($player)){
					$player->getInventory()->setItem(0, clone $leaveQueue);
				}
				break;
			case $leaveQueue->getCustomName():
				if($this->plugin->getGameManager()->unqueuePlayer($player)){
					$player->getInventory()->setItem(0, clone $joinQueue);
				}
				break;
			case $rejoinGame->getCustomName():
				if($game instanceof PreGame and $game->hasStarted()){
					$player->transfer($game->getAddress(), $game->getPort(), 'Re-transfer to play on game-server: ' . $game->getServer());
				}else{
					$player->sendMessage(TextFormat::RED . 'Re-joining game has failed.');
				}
				break;
			case $this->plugin->getUtils()->getSpawnItem('spectate')->getCustomName():
				SpectateForm::send($player);
				break;
			case $this->plugin->getUtils()->getPreGameItem('join-blue-team')->getCustomName():
				$game?->setPlayerTeam($player, 'Blue');
				break;
			case $this->plugin->getUtils()->getPreGameItem('join-red-team')->getCustomName():
				$game?->setPlayerTeam($player, 'Red');
				break;
			case $this->plugin->getUtils()->getPreGameItem('join-yellow-team')->getCustomName():
				$game?->setPlayerTeam($player, 'Yellow');
				break;
			case $this->plugin->getUtils()->getPreGameItem('join-green-team')->getCustomName():
				$game?->setPlayerTeam($player, 'Green');
				break;
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onTransaction(InventoryTransactionEvent $event) : void{
		$transaction = $event->getTransaction();
		$actions = $transaction->getActions();
		foreach($actions as $action){
			if($action instanceof SlotChangeAction){
				$inventory = $action->getInventory();
				foreach($inventory->getViewers() as $viewer){
					if($viewer instanceof Player and !$viewer->isCreative()){
						$event->cancel();
					}
				}
			}
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onDamage(EntityDamageEvent $event) : void{
		$event->cancel();
	}

	/**
	 * @priority HIGHEST
	 */
	public function onExhaust(PlayerExhaustEvent $event) : void{
		$event->cancel();
	}

	/**
	 * @priority HIGHEST
	 */
	public function onExperienceChange(PlayerExperienceChangeEvent $event) : void{
		$event->cancel();
	}

	/**
	 * @priority HIGHEST
	 */
	public function onDrop(PlayerDropItemEvent $event) : void{
		$event->cancel();
	}

	/**
	 * @priority HIGHEST
	 */
	public function onInventoryPickup(EntityItemPickupEvent $event) : void{
		$event->cancel();
	}

	/**
	 * @priority HIGHEST
	 */
	public function blockSpread(BlockSpreadEvent $event) : void{
		$event->cancel();
	}

	/**
	 * @priority HIGHEST
	 */
	public function onBlockForm(BlockFormEvent $event) : void{
		$event->cancel();
	}

	/**
	 * @priority HIGHEST
	 */
	public function onBlockGrow(BlockGrowEvent $event) : void{
		$event->cancel();
	}

	/**
	 * @priority HIGHEST
	 */
	public function onLeaveDecay(LeavesDecayEvent $event) : void{
		$event->cancel();
	}

	/**
	 * @priority HIGHEST
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void{
		if($event->getPlayer()->isSurvival() or !$this->plugin->getServer()->isOp($event->getPlayer()->getName())){
			$event->cancel();
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		if($event->getPlayer()->isSurvival() or !$this->plugin->getServer()->isOp($event->getPlayer()->getName())){
			$event->cancel();
		}
	}
}
