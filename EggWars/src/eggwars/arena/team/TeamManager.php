<?php

/*
 *    _____                  __        __
 *   | ____|   __ _    __ _  \ \      / /   __ _   _ __   ___
 *   |  _|    / _` |  / _` |  \ \ /\ / /   / _` | | '__| / __|
 *   | |___  | (_| | | (_| |   \ V  V /   | (_| | | |    \__ \
 *   |_____|  \__, |  \__, |    \_/\_/     \__,_| |_|    |___/
 *           |___/   |___/
 */

declare(strict_types=1);

namespace eggwars\arena\team;

use eggwars\arena\Arena;
use eggwars\EggWars;
use eggwars\position\EggWarsVector;
use eggwars\utils\Time;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class TeamManager
 * @package eggwars\arena\team
 */
class TeamManager {

    /** @var Arena $arena */
    public $arena;

    /** @var Team[] $teams */
    public $teams = [];

    /** @var null $lastTeam */
    public $lastTeam = null;

    /**
     * TeamManager constructor.
     * @param Arena $arena
     */
    public function __construct(Arena $arena) {
        $this->arena = $arena;
        $this->loadTeams();
    }


    private function loadTeams() {
        foreach ($this->getArena()->arenaData["teams"] as $team => $data) {
            $color = strval($data["color"]);
            $t = new Team($this, $team, $color, []);
            if(isset($data["spawn"]) && count($data["spawn"]) >= 3) {
                $t->setSpawn(EggWarsVector::fromArray($data["spawn"])->asVector3());
            }
            $this->teams[$team] = $t;
        }
    }

    /**
     * @param string $team
     * @return bool
     */
    public function isEnded(string $team): bool {
        if($this->teams[$team]->getTeamsPlayers() <= 0) {
            return true;
        }
        return false;
    }

    public function checkEnd(): bool {
        $alive = 0;
        foreach ($this->teams as $name => $team) {
            if(!$this->isEnded($name)) {
                $alive++;
            }
        }
        if($alive <= 1) {
            foreach ($this->teams as $team) {
                $this->lastTeam = $team;
            }
            return true;
        }
        return false;
    }

    /**
     * @return string $format
     */
    public function getBarFormat(): string {
        $format = str_repeat(" ", 60)."§8Map: §6{$this->getArena()->getMap()->getCustomName()} §f| §7".Time::calculateTime($this->getArena()->progress["gameTime"])."\n";
        foreach ($this->teams as $team) {
            if($team->isAlive()) {
                $format = $format."\n".str_repeat(" ", 60)."§a✖ {$team->getMinecraftColor()}{$team->getTeamName()} Team §7{$team->getPlayersCount()}";
            }
            else {
                $format = $format."\n".str_repeat(" ", 60)."§c✖ {$team->getMinecraftColor()}{$team->getTeamName()} Team §7{$team->getPlayersCount()}";
            }
        }
        return $format;
    }

    /**
     * @param Player $player
     * @param Vector3 $eggVector
     * @return bool
     */
    public function onEggBreak(Player $player, Vector3 $eggVector): bool {
        $team = $this->getArena()->getTeamEggByVector($eggVector);
        if(!$team instanceof Team) {
            return false;
        }
        if($team->inTeam($player)) {
            $player->sendMessage(EggWars::getPrefix()."§cYou can not broke you own egg!");
            return true;
        }
        $team->setAlive(false);
        $this->getArena()->broadcastMessage("§7------------------== §8[§bProgress§8] §7==------------------\n".
            "§7Player {$player->getName()} from team ".$this->getArena()->getTeamByPlayer($player)->getDisplayName()." §7destroyed egg of ".$team->getDisplayName()." §7team.\n".
            "§7---------------------------------------------------");
        return false;
    }

    /**
     * @return Team $lastTeam
     */
    public function getLastTeam(): Team {
        return $this->lastTeam;
    }

    /**
     * @return Arena $arena
     */
    public function getArena(): Arena {
        return $this->arena;
    }
}