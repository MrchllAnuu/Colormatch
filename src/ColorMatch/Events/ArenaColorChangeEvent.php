<?php

namespace ColorMatch\Events;

use pocketmine\event\plugin\PluginEvent;
use ColorMatch\ColorMatch;
use ColorMatch\Arena\Arena;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

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
