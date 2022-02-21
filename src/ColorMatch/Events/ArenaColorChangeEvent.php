<?php

namespace ColorMatch\Events;

use ColorMatch\Arena\Arena;
use ColorMatch\ColorMatch;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\plugin\PluginEvent;

class ArenaColorChangeEvent extends PluginEvent implements Cancellable {
    protected $arena;
    protected $oldColor;
    protected $newColor;

    use CancellableTrait;

	public function __construct(ColorMatch $plugin, Arena $arena, $oldColor, $newColor) {
        parent::__construct($plugin);
        $this->arena = $arena;
        $this->newColor = $newColor;
        $this->oldColor = $oldColor;
    }
    
    public function getNewColor() {
        return $this->newColor;
    }
}
