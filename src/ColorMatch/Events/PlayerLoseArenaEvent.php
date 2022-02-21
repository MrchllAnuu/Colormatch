<?php

namespace ColorMatch\Events;

use ColorMatch\Arena\Arena;
use ColorMatch\ColorMatch;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;

class PlayerLoseArenaEvent extends PluginEvent {
    protected $player;
    protected $arena;

	public function __construct(ColorMatch $plugin, Player $player, Arena $arena, $ingame) {
        parent::__construct($plugin);
        $this->player = $player;
        $this->arena = $arena;
		$sound = PlaySoundPacket::create("ambient.weather.thunder", $player->getPosition()->x, $this->arena->data['arena']['floor_y'], $player->getPosition()->z, 1, 1);
		$player->getServer()->broadcastPackets($ingame, [$sound]);
    }
    
    public function getPlayer() {
        return $this->player;
    }
}