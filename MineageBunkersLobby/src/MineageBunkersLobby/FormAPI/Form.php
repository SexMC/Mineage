<?php
declare(strict_types=1);

namespace MineageBunkersLobby\FormAPI;

use pocketmine\form\Form as IForm;
use pocketmine\player\Player;

abstract class Form implements IForm{

	protected array $data = [];
	private $callable;

	public function __construct(?callable $callable){
		$this->callable = $callable;
	}

	public function sendToPlayer(Player $player) : void{
		$player->sendForm($this);
	}

	public function send(Player $player) : void{
		if(!FormSpamFix::isLocked($player)){
			$player->sendForm($this);
			FormSpamFix::lock($player);
		}
	}

	public function handleResponse(Player $player, $data) : void{
		$this->processData($data);
		$callable = $this->getCallable();
		if($callable !== null){
			$callable($player, $data);
		}

		FormSpamFix::unlock($player);
	}

	public function getCallable() : ?callable{
		return $this->callable;
	}

	public function setCallable(?callable $callable) : void{
		$this->callable = $callable;
	}

	public function processData(&$data) : void{}

	public function jsonSerialize() : array{
		return $this->data;
	}
}
