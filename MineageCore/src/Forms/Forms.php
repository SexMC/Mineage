<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Forms;

use Mineage\MineageCore\Forms\Handler\WorldPickerHandler;
use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Utils\DefaultConfigData;
use pocketmine\player\Player;

class Forms extends CoreModule{
	/** @var FormHandler[] */
	protected array $form_handlers = [];

	public const FORM_HANDLER_WORLD_PICKER = "world-picker";
	public const FORM_HANDLER_QUEUE_RANKED = "queue-ranked";
	public const FORM_HANDLER_PLAYER_SETTINGS = "player-settings";

	public function __construct(MineageCore $core){
		parent::__construct(
			core: $core,
			name: "forms",
			default_config_data: DefaultConfigData::forms()
		);

		$this->form_handlers = [
			self::FORM_HANDLER_WORLD_PICKER => new WorldPickerHandler($core)
		];
	}

	public function registerFormHandler(string $name, FormHandler $handler) : void{
		$this->form_handlers[$name] = $handler;
	}

	public function getFormHandler(string $handler_name) : ?FormHandler{
		return $this->form_handlers[$handler_name] ?? null;
	}

	public function getFormConfigData(string $form_name) : ?array{
		return $this->getConfig()->getNested("forms.$form_name") ?? null;
	}

	public function openForm(Player $player, string $form_name){
		$form_config_data = $this->getFormConfigData($form_name);
		$handler = $this->getFormHandler($form_config_data["action"]);

		if($handler !== null){
			$handler->handle($player, $form_config_data);
		}
	}
}
