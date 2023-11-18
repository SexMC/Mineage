<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Forms\Class;

use pocketmine\form\Form;
use pocketmine\player\Player;
use function str_starts_with;

class SimpleForm implements Form{
	public static string|array $cache = [];

	protected array $formData = [];

	protected \Closure $callable;

	public function __construct(\Closure $callable){
		$this->formData["type"] = "form";
		$this->formData["content"] = "";

		$this->callable = $callable;
	}

	public function setFormData(array $formData) : void{
		$this->formData = $formData;
	}

	public function getFormData() : array{
		return $this->formData;
	}

	public function getEncodedFormData() : string{
		return json_encode($this->formData);
	}

	public function send(Player $player){
		if(!FormSpamFixHack::isLocked($player)){
			$player->sendForm($this);
			FormSpamFixHack::lock($player);
		}
	}

	public function setTitle(string $title){
		$this->formData["title"] = $title;
	}

	public function setContent(string $text){
		$this->formData["content"] = $text;
	}

	public function addButton(string $button, string $imageURL = null){
		$content = ["text" => $button];

		if($imageURL !== null){
			$content["image"]["type"] = (str_starts_with($imageURL, "http://") or str_starts_with($imageURL, "https://")) ? "url" : "path";
			$content["image"]["data"] = $imageURL;
		}

		$this->formData["buttons"][] = $content;
	}

	public function handleResponse(Player $player, $data) : void{
		if($data !== null){
			($this->callable)($player, $data);
		}
		FormSpamFixHack::unlock($player);
	}

	public function jsonSerialize() : mixed{
		return $this->formData;
	}
}
