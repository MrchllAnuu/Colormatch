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
        $this->playDeathSound($ingame);

    }
    
    public function getPlayer() {
        return $this->player;
    }

    private function playDeathSound($ingame) {
		foreach ($ingame as $p) {
			$sound = PlaySoundPacket::create("ambient.weather.thunder", $p->getPosition()->x, $p->getPosition()->y, $p->getPosition()->z, 0.8, 1);
			$this->player->getServer()->broadcastPackets([$p], [$sound]);
		}
	}
}