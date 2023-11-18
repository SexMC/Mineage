<?php
declare(strict_types=1);

namespace MineageBunkersLobby\Forms;

use MineageBunkersLobby\FormAPI\SimpleForm;
use MineageBunkersLobby\MineageBunkersLobby;
use MineageBunkersLobby\PreGame\PreGame;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class SpectateForm{

	public static function send(Player $player) : void{
		$form = new SimpleForm(function($player, $data = null) : void{
			if($data === null or !$player instanceof Player){
				return;
			}

			$server = $data[0];
			$player->transfer($data[1], $data[2], 'Transfer to spectate game-server: ' . $server);
			MineageBunkersLobby::getInstance()->getNetwork()?->executeSelect('mineage.get.game.server.entry', ['server' => $server], function($rows) use ($player, $data, $server){
				if(sizeof($rows) > 0){
					$specs = $rows[0]['Spectating'];
					MineageBunkersLobby::getInstance()->getNetwork()?->executeGeneric('mineage.update.game.server.entry.spectating', [
						'server' => $server,
						'spectating' => ($specs == '' ? $player->getName() : $specs . ',' . $player->getName()),
					]);
				}
			});
		});

		$form->setTitle(MineageBunkersLobby::getInstance()->getUtils()->getSpawnItem('spectate')->getCustomName());
		foreach(MineageBunkersLobby::getInstance()->getGameManager()->getGames() as $game){
			if($game instanceof PreGame and $game->hasStarted()){
				$form->addButton($game->getMap() . TextFormat::EOL . 'Players: ' . count($game->getPlayers()), -1, '', [$game->getServer(), $game->getAddress(), $game->getPort()]);
			}
		}

		$form->send($player);
	}
}
