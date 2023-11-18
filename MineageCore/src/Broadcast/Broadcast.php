<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Broadcast;

use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Utils\DefaultConfigData;
use pocketmine\utils\TextFormat;

class Broadcast extends CoreModule{
	protected array $messages;
	protected int $index = 0;

	public function __construct(MineageCore $core){
		parent::__construct(
			core: $core,
			name: "broadcast",
			default_config_data: DefaultConfigData::broadcast()
		);

		$this->messages = array_map(function(string $string){
			return TextFormat::colorize($string);
		}, $this->getConfig()->get("messages"));

		if($this->enabled or $this->bypass_enable_check){
			$core->getScheduler()->scheduleRepeatingTask(new BroadcastTask($core, $this), $this->getConfig()->get("period") * 20);
		}
	}

	public function getPreviousMessage() : string{
		return $this->messages[--$this->index] ?? $this->messages[$this->index = count($this->messages) - 1] ?? "";
	}

	public function getCurrentMessage() : string{
		return $this->messages[$this->index] ?? $this->getNextMessage();
	}

	public function getNextMessage() : string{
		return $this->messages[$this->index++ % count($this->messages)] ?? "";
	}
}
