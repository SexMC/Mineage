<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Chat;

use Mineage\MineageCore\Chat\Commands\ClearChatCommand;
use Mineage\MineageCore\Chat\Commands\MuteChatCommand;
use Mineage\MineageCore\Chat\Commands\StaffChatCommand;
use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Utils\DefaultConfigData;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use WeakMap;

class Chat extends CoreModule{
	private array $blocked_messages = [];
	private bool $global_chat_muted = false;

	protected WeakMap $chat_cooldown;

	public function __construct(MineageCore $core){
		parent::__construct(
			core: $core,
			name: "chat",
			default_config_data: DefaultConfigData::chat()
		);

		if($this->enabled or $this->bypass_enable_check){
			$this->core->getServer()->getPluginManager()->registerEvents(new ChatListener($core, $this), $core);
			$commands = [
				new ClearChatCommand($core, $this),
				new MuteChatCommand($core, $this),
				new StaffChatCommand($core, $this),
			];
			foreach($commands as $command){
				$this->core->getServer()->getCommandMap()->register("mineage", $command);
			}
		}

		$this->chat_cooldown = new WeakMap();
	}

	public function arePrivateMessagesBlocked(Player $origin, Player $target) : bool{
		return isset($this->blocked_messages[spl_object_hash($origin)][spl_object_hash($target)]);
	}

	public function blockPrivateMessages(Player $origin, Player $target) : void{
		$origin_hash = spl_object_hash($origin);
		if(!isset($this->blocked_messages[$origin_hash])){
			$this->blocked_messages[$origin_hash] = [];
		}
		$this->blocked_messages[$origin_hash][spl_object_hash($target)] = $target;
	}

	public function unblockPrivateMessages(Player $origin, Player $target) : void{
		unset($this->blocked_messages[spl_object_hash($origin)][spl_object_hash($target)]);
	}

	public function isGlobalChatMuted() : bool{
		return $this->global_chat_muted;
	}

	public function muteGlobalChat(){
		$this->global_chat_muted = true;
	}

	public function unmuteGlobalChat(){
		$this->global_chat_muted = false;
	}

	public function clearChat(){
		$this->core->getServer()->broadcastMessage(str_repeat(TextFormat::EOL, 1000), $this->core->getServer()->getOnlinePlayers());
	}

	public function getStaffChatFormat() : string{
		return $this->getConfig()->getNested("staffchat.format", "");
	}

	public function setCooldown(Player $player, ?int $cooldown = null) : void{
		$cooldown = $cooldown ?? $this->getConfig()->getNested("cooldown.time", 5);
		$this->chat_cooldown[$player] = time() + $cooldown;
	}

	public function getChatCooldown(Player $player) : ?int{
		if(!isset($this->chat_cooldown[$player])){
			return 0;
		}
		return $this->chat_cooldown[$player] - time();
	}

	public function testChatCooldown(Player $player) : bool{
		$cooldown = $this->getConfig()->getNested("cooldown.time", 5);
		if(!isset($this->chat_cooldown[$player])){
			$this->chat_cooldown[$player] = time() + $cooldown;
			return true;
		}

		if($this->chat_cooldown[$player] - time() <= 0){
			unset($this->chat_cooldown[$player]);
			return true;
		}
		return false;
	}
}
