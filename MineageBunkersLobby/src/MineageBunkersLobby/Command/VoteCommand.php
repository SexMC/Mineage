<?php
declare(strict_types=1);

namespace MineageBunkersLobby\Command;

use MineageBunkersLobby\MineageBunkersLobby;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function strtolower;

class VoteCommand extends Command{

	public function __construct(private readonly MineageBunkersLobby $plugin, string $name, Translatable|string $description = '', Translatable|string|null $usageMessage = null, array $aliases = []){
		parent::__construct($name, $description, $usageMessage, $aliases);
		$this->setPermission('mineage.bunkers.command.vote');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		if(!$sender instanceof Player){
			return;
		}
		$session = $this->plugin->getSessionManager()->getSession($sender);
		if($session === null){
			return;
		}

		$game = $this->plugin->getGameManager()->getGameFromPlayer($sender->getName());
		if($game === null){
			$sender->sendMessage(TextFormat::RED . 'You are not in a game.');
			return;
		}
		if($game->isStarting()){
			$sender->sendMessage(TextFormat::RED . 'Map voting has already ended.');
			return;
		}

		if(!isset($args[0])){
			$sender->sendMessage(TextFormat::RED . 'Provide a map.');
			return;
		}

		$map = match (strtolower($args[0])) {
			'classic', 'c' => 'Classic',
			default => null
		};
		if($map === null){
			$sender->sendMessage(TextFormat::RED . 'Provide a valid map.');
			return;
		}

		if($session->getCurrentVote() == $map){
			$sender->sendMessage(TextFormat::RED . 'You already voted for ' . $map . '.');
			return;
		}

		if($game->addVote($map)){
			$session->setCurrentVote($map);
			$sender->sendMessage(TextFormat::GREEN . 'You voted for ' . $map . '.');
		}else{
			$sender->sendMessage(TextFormat::RED . 'Map vote failed, please try again.');
		}
	}
}
