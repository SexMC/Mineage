<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Utils;

use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\StringToEffectParser;

class EffectParser{
	/**
	 * @param array|string $effects
	 *
	 * @return EffectInstance[]
	 */
	public static function parse(array|string $effects) : array{
		$output = [];
		$effects = is_array($effects) ? $effects : [$effects];
		foreach($effects as $effect){
			if($effect instanceof Effect or !is_string($effect)){
				continue;
			}

			$parts = explode(":", $effect);
			$effect = StringToEffectParser::getInstance()->parse($parts[0]);

			if($effect !== null){
				$output[] = new EffectInstance(
					$effect,
					intval($parts[1]) * 20,
					intval($parts[2]),
					filter_var($parts[3], FILTER_VALIDATE_BOOL)
				);
			}
		}
		return $output;
	}
}
