<?php

namespace ColorMatch\Events;

use ColorMatch\Arena\Arena;
use ColorMatch\ColorMatch;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\player\Player;

class PlayerJoinArenaEvent extends PluginEvent implements Cancellable {
    protected $player;
    protected $arena;

    use CancellableTrait;

	public function __construct(ColorMatch $plugin, Player $player, Arena $arena) {
        parent::__construct($plugin);
        $this->player = $player;
        $this->arena = $arena;
    }
    
    public function getPlayer() {
        return $this->player;
    }
}