<?php
declare(strict_types=1);

namespace MineageBunkersLobby\Command;

use MineageBunkersLobby\MineageBunkersLobby;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function is_string;
use function str_replace;
use function strtolower;
use function time;

class PartyCommand extends Command{

	private string $help =
		TextFormat::GRAY . '----------------------' . TextFormat::EOL .
		TextFormat::RED . '/party accept <player>' . TextFormat::EOL .
		TextFormat::RED . '/party chat' . TextFormat::EOL .
		TextFormat::RED . '/party close' . TextFormat::EOL .
		TextFormat::RED . '/party disband' . TextFormat::EOL .
		TextFormat::RED . '/party info <player>' . TextFormat::EOL .
		TextFormat::RED . '/party invite <player>' . TextFormat::EOL .
		TextFormat::RED . '/party join <player>' . TextFormat::EOL .
		TextFormat::RED . '/party kick <player>' . TextFormat::EOL .
		TextFormat::RED . '/party leader <player>' . TextFormat::EOL .
		TextFormat::RED . '/party leave' . TextFormat::EOL .
		TextFormat::RED . '/party open' . TextFormat::EOL .
		TextFormat::GRAY . '----------------------';

	public function __construct(private readonly MineageBunkersLobby $plugin, string $name, Translatable|string $description = '', Translatable|string|null $usageMessage = null, array $aliases = []){
		parent::__construct($name, $description, $usageMessage, $aliases);
		$this->setPermission('mineage.bunkers.command.party');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		if(!$sender instanceof Player){
			return;
		}
		$session = $this->plugin->getSessionManager()->getSession($sender);
		if($session === null){
			return;
		}

		if(!isset($args[0])){
			$sender->sendMessage($this->help);
			return;
		}

		$party = $this->plugin->getPartyManager()->getPartyFromPlayer($sender->getName());
		$firstArg = strtolower($args[0]);
		switch($firstArg){
			case 'invite':
				if(!isset($args[1])){
					$sender->sendMessage(TextFormat::RED . 'Provide a player to invite to your party.');
					return;
				}

				$arg = str_replace('@', '', $args[1]);
				$target = $this->plugin->getServer()->getPlayerByPrefix($arg);
				if($target === null){
					$sender->sendMessage(TextFormat::RED . 'The player ' . $arg . ' could not be found.');
					return;
				}

				$n = $target->getName();
				if($n == $sender->getName()){
					$sender->sendMessage(TextFormat::RED . 'You cannot invite yourself to your own party.');
					return;
				}

				if($this->plugin->getPartyManager()->getPartyFromPlayer($n) !== null){
					$sender->sendMessage(TextFormat::RED . $n . ' is already in a party.');
					return;
				}

				if($this->plugin->getPartyManager()->getInviteForFromSender($n, $sender->getName()) !== null){
					$sender->sendMessage(TextFormat::RED . $n . ' has already been invited to your party.');
					return;
				}

				if($party !== null and !$party->isLeader($sender->getName())){
					$sender->sendMessage(TextFormat::RED . 'You are not the leader of your party.');
				}else{
					$this->plugin->getPartyManager()->createInvite($sender, $target);
				}
				break;
			case 'accept':
			case 'decline':
				if($party !== null){
					$sender->sendMessage(TextFormat::RED . 'You are already in a party.');
					return;
				}

				if(!isset($args[1])){
					$sender->sendMessage(TextFormat::RED . 'Provide a player who sent you a party invitation.');
					return;
				}

				$arg = str_replace('@', '', $args[1]);
				$target = $this->plugin->getServer()->getPlayerByPrefix($arg);
				if($target === null){
					$sender->sendMessage(TextFormat::RED . 'The player ' . $arg . ' could not be found.');
					return;
				}

				$n = $target->getName();
				if($n == $sender->getName()){
					$sender->sendMessage(TextFormat::RED . 'You cannot use this command on yourself.');
					return;
				}

				$invite = $this->plugin->getPartyManager()->getInviteForFromSender($n, $sender->getName());
				if($invite === null){
					$sender->sendMessage(TextFormat::RED . 'You do not have a party invitation from ' . $n . '.');
					return;
				}

				if($invite->getExpire() < time()){
					$this->plugin->getPartyManager()->closeInvite($invite->getId());
					$sender->sendMessage(TextFormat::RED . 'Your invitation from ' . $n . ' has already expired.');
					return;
				}

				$firstArg == 'accept' ? $invite->accept() : $invite->decline();
				break;
			case 'chat':
				if($party === null){
					$sender->sendMessage(TextFormat::RED . 'You are not in a party.');
					return;
				}

				if($party->updatePartyChat($sender)){
					$sender->sendMessage(TextFormat::GREEN . 'You changed your chat.');
				}
				break;
			case 'disband':
				if($party === null){
					$sender->sendMessage(TextFormat::RED . 'You are not in a party.');
					return;
				}

				if(!$party->isLeader($sender->getName())){
					$sender->sendMessage(TextFormat::RED . 'You are not the leader of your party.');
					return;
				}

				MineageBunkersLobby::getInstance()->getPartyManager()->closeParty($party);
				break;
			case 'kick':
				if($party === null){
					$sender->sendMessage(TextFormat::RED . 'You are not in a party.');
					return;
				}

				if(!$party->isLeader($sender->getName())){
					$sender->sendMessage(TextFormat::RED . 'You are not the leader of your party.');
					return;
				}

				if(!isset($args[1])){
					$sender->sendMessage(TextFormat::RED . 'Provide a player to kick from your party.');
					return;
				}

				$arg = str_replace('@', '', $args[1]);
				$target = $this->plugin->getServer()->getPlayerByPrefix($arg) ?? $arg;
				$targetNull = is_string($target);

				$n = $targetNull ? $target : $target->getName();
				if($n == $sender->getName()){
					$sender->sendMessage(TextFormat::RED . 'You cannot kick yourself from your own party.');
					return;
				}

				if(!$party->isMember($n)){
					$sender->sendMessage(TextFormat::RED . $n . ' is not in your party.');
					return;
				}

				$party->removeMember($n, !$targetNull);
				break;
			case 'leader':
				if($party === null){
					$sender->sendMessage(TextFormat::RED . 'You are not in a party.');
					return;
				}

				if(!$party->isLeader($sender->getName())){
					$sender->sendMessage(TextFormat::RED . 'You are not the leader of your party.');
					return;
				}

				if(!isset($args[1])){
					$sender->sendMessage(TextFormat::RED . 'Provide a player to transfer party leadership.');
					return;
				}

				$arg = str_replace('@', '', $args[1]);
				$target = $this->plugin->getServer()->getPlayerByPrefix($arg);
				if($target === null){
					$sender->sendMessage(TextFormat::RED . 'The player ' . $arg . ' could not be found.');
					return;
				}

				$n = $target->getName();
				if($n == $sender->getName()){
					$sender->sendMessage(TextFormat::RED . 'You cannot transfer party leadership to yourself.');
					return;
				}

				if(!$party->isMember($n)){
					$sender->sendMessage(TextFormat::RED . $n . ' is not in your party.');
					return;
				}

				$party->setLeader($target->getName());
				break;
			case 'leave':
				if($party === null){
					$sender->sendMessage(TextFormat::RED . 'You are not in a party.');
					return;
				}

				if($party->isLeader($sender->getName())){
					$sender->sendMessage(TextFormat::RED . 'You cannot leave your own party.');
					return;
				}

				$party->removeMember($sender);
				break;
			case 'open':
			case 'close':
				if($party === null){
					$sender->sendMessage(TextFormat::RED . 'You are not in a party.');
					return;
				}

				if(!$party->isLeader($sender->getName())){
					$sender->sendMessage(TextFormat::RED . 'You are not the leader of your party.');
					return;
				}

				$open = $firstArg == 'open';
				$party->setPublic($open);
				$sender->sendMessage(TextFormat::GREEN . 'You updated your party\'s privacy to ' . ($open ? 'public' : 'private') . '.');
				break;
			case 'info':
				//info about a party from a provided player
				break;
			case 'join':
				//can't join unless invited or the party is set to open
				break;
			default:
				$sender->sendMessage($this->help);
				break;
		}
	}
}
