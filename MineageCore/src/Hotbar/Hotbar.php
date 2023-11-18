<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Hotbar;

use Mineage\MineageCore\Forms\Forms;
use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Utils\CommandUtils;
use Mineage\MineageCore\Utils\DefaultConfigData;
use Mineage\MineageCore\Utils\ItemParser;
use pocketmine\player\Player;

class Hotbar extends CoreModule{
	public function __construct(MineageCore $core){
		parent::__construct(
			core: $core,
			name: "hotbar",
			default_config_data: DefaultConfigData::hotbar()
		);

		if($this->enabled or $this->bypass_enable_check){
			$this->core->getServer()->getPluginManager()->registerEvents(new HotbarListener($core, $this), $core);
		}
	}

	public function cancelDrop() : bool{
		return $this->getConfig()->get("cancel-drop");
	}

	public function cancelSlotChange() : bool{
		return $this->getConfig()->get("cancel-slot-change");
	}

	public function isHotbarWorld(string $world) : bool{
		return in_array($world, $this->getConfig()->get("hotbar-worlds"));
	}

	public function equip(Player $player){
		$player->getInventory()->setContents($this->getItems());
	}

	public function getItems(bool $register_interact_events = true) : array{
		$output_items = [];
		$items = $this->getConfig()->get("items");

		foreach($items as $item_data){
			$item = ItemParser::parse($item_data["item"])[0];
			$item = HotbarItem::create($item);

			if($register_interact_events){
				$this->registerInteractEvents($item, $item_data);
			}

			$output_items[$item_data["slot"]] = $item;
		}
		return $output_items;
	}

	protected function registerInteractEvents(HotbarItem $item, array $item_data){
		switch($item_data["interact-action"]){
			case "commands":
				$item->doOnBlockInteract(function(Player $player) use ($item, $item_data){
					CommandUtils::parseCommandsAndDispatch($player, $item_data["commands"], ["@player" => $player->getName()]);
				});
				break;
			case "form":
				/** @var Forms $forms */
				$forms = $this->core->module_manager->getModuleByName("forms");
				if($forms !== null){
					$item->doOnBlockInteract(function(Player $player) use ($item, $item_data, $forms){
						$forms->openForm($player, $item_data["form-name"]);
					});
					break;
				}
				$this->core->getLogger()->notice("Could not open form '{$item_data["form-name"]}': is forms module disabled?");
				break;
		}
	}
}
