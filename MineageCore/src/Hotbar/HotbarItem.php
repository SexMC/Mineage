<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Hotbar;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class HotbarItem extends Item{
	protected ?\Closure $on_block_interact = null;
	protected ?\Closure $on_entity_interact = null;

	public static function create(Item $original) : HotbarItem{
		return (new self(new ItemIdentifier($original->getTypeId())))->setNamedTag($original->getNamedTag());
	}

	public function doOnBlockInteract(\Closure $on_block_interact){
		$this->on_block_interact = $on_block_interact;
	}

	public function doOnEntityInteract(\Closure $on_entity_interact){
		$this->on_entity_interact = $on_entity_interact;
	}

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		if($this->on_block_interact !== null){
			($this->on_block_interact)($player, VanillaBlocks::AIR(), VanillaBlocks::AIR(), 0, Vector3::zero());
		}
		return ItemUseResult::NONE();
	}

	public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, array &$returnedItems) : ItemUseResult{
		if($this->on_block_interact !== null){
			($this->on_block_interact)($player, $blockReplace, $blockClicked, $face, $clickVector);
		}
		return ItemUseResult::NONE();
	}

	public function onInteractEntity(Player $player, Entity $entity, Vector3 $clickVector) : bool{
		if($this->on_entity_interact !== null){
			($this->on_entity_interact)($player, $entity, $clickVector);
		}
		return false;
	}
}
