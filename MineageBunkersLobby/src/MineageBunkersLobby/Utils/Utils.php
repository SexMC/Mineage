<?php
declare(strict_types=1);

namespace MineageBunkersLobby\Utils;

use MineageBunkersLobby\ChatFormatter\MineageChatFormatter;
use MineageBunkersLobby\MineageBunkersLobby;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class Utils{

	private MineageChatFormatter $chatFormatter;

	private array $spawnItems;
	private array $preGameItems;

	public function __construct(private readonly MineageBunkersLobby $plugin){
		$this->chatFormatter = new MineageChatFormatter();

		$spawnItemCfg = $this->plugin->getConfig()->getNested('items')['spawn'];
		$this->spawnItems ['join-queue'] = VanillaItems::DYE()->setColor(DyeColor::GRAY())->setCustomName(TextFormat::colorize($spawnItemCfg['join-queue']));
		$this->spawnItems ['leave-queue'] = VanillaItems::DYE()->setColor(DyeColor::GREEN())->setCustomName(TextFormat::colorize($spawnItemCfg['leave-queue']));
		$this->spawnItems ['rejoin-game'] = VanillaItems::MAGMA_CREAM()->setCustomName(TextFormat::colorize($spawnItemCfg['rejoin-game']));
		$this->spawnItems ['spectate'] = VanillaItems::COMPASS()->setCustomName(TextFormat::colorize($spawnItemCfg['spectate']));

		$preGameItemCfg = $this->plugin->getConfig()->getNested('items')['pre-game'];
		$this->preGameItems ['join-blue-team'] = VanillaBlocks::WOOL()->setColor(DyeColor::BLUE())->asItem()->setCustomName(TextFormat::colorize($preGameItemCfg['join-blue-team']));
		$this->preGameItems ['join-red-team'] = VanillaBlocks::WOOL()->setColor(DyeColor::RED())->asItem()->setCustomName(TextFormat::colorize($preGameItemCfg['join-red-team']));
		$this->preGameItems ['join-yellow-team'] = VanillaBlocks::WOOL()->setColor(DyeColor::YELLOW())->asItem()->setCustomName(TextFormat::colorize($preGameItemCfg['join-yellow-team']));
		$this->preGameItems ['join-green-team'] = VanillaBlocks::WOOL()->setColor(DyeColor::GREEN())->asItem()->setCustomName(TextFormat::colorize($preGameItemCfg['join-green-team']));
	}

	public function getChatFormatter() : MineageChatFormatter{
		return $this->chatFormatter;
	}

	public function getSpawnItem(string $string) : ?Item{
		return $this->spawnItems[$string] ?? null;
	}

	public function getPreGameItem(string $string) : ?Item{
		return $this->preGameItems[$string] ?? null;
	}

	public function sendSpawnKit(Player $player) : void{
		$player->getInventory()->clearAll();
		$player->getCursorInventory()->clearAll();

		$player->getInventory()->setItem(0, clone $this->getSpawnItem('join-queue'));
		$player->getInventory()->setItem(8, clone $this->getSpawnItem('spectate'));

		$game = $this->plugin->getGameManager()->getGameFromPlayer($player->getName());
		if($game !== null and $game->hasStarted()){
			$player->getInventory()->setItem(1, clone MineageBunkersLobby::getInstance()->getUtils()->getSpawnItem('rejoin-game'));
		}
	}
}
