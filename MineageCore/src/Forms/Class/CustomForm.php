<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Forms\Class;

use pocketmine\form\Form;
use pocketmine\player\Player;

class CustomForm implements Form{
	protected array $form_data = [];

	protected \Closure $callable;

	public function __construct(\Closure $callable){
		$this->form_data["type"] = "custom_form";
		$this->form_data["content"] = [];

		$this->callable = $callable;
	}

	public function setFormData(array $form_data) : void{
		$this->form_data = $form_data;
	}

	public function getFormData() : array{
		return $this->form_data;
	}

	public function getEncodedFormData() : string{
		return json_encode($this->form_data);
	}

	public function send(Player $player){
		if(!FormSpamFixHack::isLocked($player)){
			$player->sendForm($this);
			FormSpamFixHack::lock($player);
		}
	}

	public function setTitle(string $title){
		$this->form_data["title"] = $title;
	}

	public function addLabel(string $label){
		$this->form_data["content"][] = [
			"type" => "label",
			"text" => $label,
		];
	}

	public function addToggle(string $toggle, bool $value = null){
		$this->form_data["content"][] = [
			"type" => "toggle",
			"text" => $toggle,
			"default" => $value !== null ? $value : false
		];
	}

	public function addSlider(string $slider, int $min, int $max, int $step = null, int $default = null){
		$this->form_data["content"][] = [
			"type" => "slider",
			"text" => $slider,
			"min" => $min,
			"max" => $max,
			"step" => $step !== null ? $step : 1,
			"default" => $default !== null ? $default : 1
		];
	}

	public function addDropdown(string $dropdown, array $options, int $default = null){
		$this->form_data["content"][] = [
			"type" => "dropdown",
			"text" => $dropdown,
			"options" => $options,
			"default" => $default !== null ? $default : 1
		];
	}

	public function addInput(string $input, string $placeholder = "", string $default = null){
		$this->form_data["content"][] = [
			"type" => "input",
			"text" => $input,
			"placeholder" => $placeholder,
			"default" => $default !== null ? $default : ""
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if($data !== null){
			($this->callable)($player, $data);
		}
		FormSpamFixHack::unlock($player);
	}

	public function jsonSerialize() : mixed{
		return $this->form_data;
	}
}
