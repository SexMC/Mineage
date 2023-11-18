<?php

declare(strict_types=1);

namespace MineageBunkersLobby\Manager;

use MineageBunkersLobby\session\Session;
use pocketmine\player\Player;

class SessionManager
{

    private array $sessions = [];

    public function createSession(Player $player): void
    {
        $n = $player->getName();
        if (isset($this->sessions[$n])) {
            unset($this->sessions[$n]);
        }

        $session = new Session($player);
        $this->sessions[$n] = $session;

        $session->onJoin();
    }

    public function removeSession(Player $player): void
    {
        if (isset($this->sessions[$player->getName()])) {
            $this->getSession($player)?->onQuit();
            unset($this->sessions[$player->getName()]);
        }
    }

    public function getSession(Player $player): ?Session
    {
        return $this->sessions[$player->getName()] ?? null;
    }
}