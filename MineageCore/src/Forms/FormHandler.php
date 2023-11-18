<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Forms;

use Mineage\MineageCore\MineageCore;
use pocketmine\player\Player;

abstract class FormHandler{
	public function __construct(protected MineageCore $core){ }

	abstract public function handle(Player $player, array $data) : void;
}
