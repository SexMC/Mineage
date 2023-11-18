<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Utils;

class StringUtils{
	public static function replace(string $text, array $replace) : string{
		return str_replace(array_keys($replace), array_values($replace), $text);
	}

	public static function recursiveReplace(array $search, array $replace) : array{
		$output = [];
		foreach($search as $key => $value){
			if(is_array($value)){
				$output[$key] = self::recursiveReplace($value, $replace);
			}elseif(is_string($value)){
				$output[$key] = self::replace($value, $replace);
			}else{
				$output[$key] = $value;
			}
		}
		return $output;
	}
}
