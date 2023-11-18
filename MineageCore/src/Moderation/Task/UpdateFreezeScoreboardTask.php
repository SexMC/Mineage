<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Moderation\Task;

use Mineage\MineageCore\MineageCore;
use Mineage\MineageCore\Moderation\Moderation;
use Mineage\MineageCore\Module\CoreModule;
use Mineage\MineageCore\Module\ModuleTask;
use Mineage\MineageCore\Utils\Scoreboard;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class UpdateFreezeScoreboardTask extends ModuleTask{
	private int $time_passed = 0;
	private string $scoreboard_title = "";
	private array $scoreboard_lines = [];

	/** @var Moderation */
	protected readonly CoreModule $owner;

	public function __construct(
		MineageCore $core,
		CoreModule $owner,
		private readonly Player $player,
		private readonly Player $target
	){
		parent::__construct($core, $owner);
		$this->scoreboard_title = TextFormat::colorize($owner->getConfig()->getNested("freeze.scoreboard.title"));
		$this->scoreboard_lines = $owner->getConfig()->getNested("freeze.scoreboard.lines");
	}

	public function onCancel() : void{
		Scoreboard::removeScore($this->player);
	}

	public function onRun() : void{
		++$this->time_passed;

		Scoreboard::setScore($this->player, $this->scoreboard_title);
		foreach($this->scoreboard_lines as $line => $scoreboard_line){
			$scoreboard_line = TextFormat::colorize($scoreboard_line);
			$scoreboard_line = str_replace(
				["@staff", "@time", "@player"],
				[$this->player->getName(), $this->time_passed, $this->target->getName()],
				$scoreboard_line
			);
			Scoreboard::setScoreLine($this->player, $line + 1, $scoreboard_line);
		}
	}
}
