<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Discord;

use Mineage\MineageCore\Discord\Class\DiscordMessageBuilder;
use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Utils\DefaultConfigData;
use Mineage\MineageCore\Utils\StringUtils;
use pocketmine\thread\NonThreadSafeValue;

class Discord extends CoreModule{

	public function __construct(MineageCore $core){
		parent::__construct(
			core: $core,
			name: "discord",
			default_config_data: DefaultConfigData::discord()
		);
		$config = $this->getConfig();
		if($config->get("webhook", "") === ""){
			$this->core->getLogger()->notice("Discord module disabled because webhook has not been set.");
			$this->core->getLogger()->notice("Please set your webhook and restart the server.");
			$this->enabled = false;
		}
	}

	public function sendRich(DiscordMessageBuilder $builder, array $replaces = []) : void{
		$this->sendRaw($builder->toArray(), $replaces);
	}

	public function sendRawText(string $text, ?string $username = null, ?string $avatar = null, array $replaces = []) : void{
		$this->sendRaw([$text, $username, $avatar], $replaces);
	}

	public function sendRaw(array $message, array $replaces = []) : void{
		$this->sendAll([$message], $replaces);
	}

	public function sendAll(array $messages, array $replaces = []) : void{
		$replacedMessages = [];
		foreach($messages as $message){
			$replacedMessages[] = StringUtils::recursiveReplace($message, $replaces);
		}
		$this->core->getServer()->getAsyncPool()->submitTask(new DiscordMessageSendTask(new NonThreadSafeValue($replacedMessages), $this->getConfig()->get("webhook")));
	}
}
