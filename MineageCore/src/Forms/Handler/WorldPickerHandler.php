<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Forms\Handler;

use Mineage\MineageCore\Forms\Class\SimpleForm;
use Mineage\MineageCore\Forms\FormHandler;
use Mineage\MineageCore\Utils\ServerUtils;
use Mineage\MineageCore\Utils\StringUtils;
use Mineage\MineageCore\Worlds\Worlds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class WorldPickerHandler extends FormHandler{
	public function handle(Player $player, array $data) : void{
		$form = new SimpleForm(function(Player $player, $response_data) use ($data){
			$button = $data["buttons"][$response_data];

			$button_world = $button["button-world"];
			/** @var Worlds $worlds */
			$worlds = $this->core->module_manager->getModuleByName("worlds");
			$worlds->joinWorld($player, $button_world);
		});

		$form->setTitle(TextFormat::colorize($data["title"]));
		if($data["description"] !== ""){
			$form->setContent(TextFormat::colorize($data["description"]));
		}

		foreach($data["buttons"] as $button){
			$button_title = $button["button-title"];

			if(($world = ServerUtils::getWorldByName($button["button-world"])) !== null){
				/** @var Worlds $worlds */
				$worlds = $this->core->module_manager->getModuleByName("worlds");

				$players = count($world->getPlayers());
				$max_players = "unlimited";

				if(
					$worlds !== null
					and (($world_max_payers = $worlds->getWorldMaxPlayers($button["button-world"])) !== Worlds::WORLD_PLAYER_LIMIT_UNLIMITED)
				){
					$max_players = $world_max_payers;
				}

				$button_title = StringUtils::replace($button_title, [
					"@players" => $players,
					"@max-players" => $max_players
				]);
			}

			$form->addButton($button_title, $button["button-icon"]);
		}

		$form->send($player);
	}

}
