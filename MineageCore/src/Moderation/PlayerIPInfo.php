<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Moderation;

readonly class PlayerIPInfo{
	public function __construct(
		public string $country,
		public string $timezone,
		public bool $proxy,
	){}
}
