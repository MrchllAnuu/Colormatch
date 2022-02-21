<?php

namespace ColorMatch\Events;

use ColorMatch\Arena\Arena;
use ColorMatch\ColorMatch;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\player\Player;

class PlayerWinArenaEvent extends PluginEvent {
    protected $player;
    protected $arena;

	public function __construct(ColorMatch $plugin, Player $player, Arena $arena) {
        parent::__construct($plugin);
        $this->player = $player;
        $this->arena = $arena;
    }
    
    public function getPlayer() {
        return $this->player;
    }
}