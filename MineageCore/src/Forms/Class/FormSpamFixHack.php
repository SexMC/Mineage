<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Forms\Class;

use pocketmine\player\Player;

class FormSpamFixHack{
	/** @var bool[] */
	private static array $form_lock = [];

	public static function isLocked(Player $player) : bool{
		return isset(self::$form_lock[spl_object_hash($player)]);
	}

	public static function lock(Player $player) : void{
		self::$form_lock[spl_object_hash($player)] = true;
	}

	public static function unlock(Player $player) : void{
		unset(self::$form_lock[spl_object_hash($player)]);
	}
}
