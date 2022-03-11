<?php

namespace ColorMatch\Arena;

use pocketmine\block\tile\Sign;
use pocketmine\block\utils\SignText;
use pocketmine\scheduler\Task;

class ArenaSchedule extends Task{

    private int $currentRound;
    private int $time = 0;
    private $lobbyTime;
    private int $updateTime = 0;
    private int $colorTime;
    private float $percentage;

    private $forcestart = false;

    private $arena;

    #sign lines
    private $line1;
    private $line2;
    private $line3;
    private $line4;

    public function __construct(Arena $arena) {
        $this->arena = $arena;
        $this->resetVars();
		$this->lobbyTime = $this->arena->data['arena']['lobby_time'];
		$this->line1 = str_replace("&", "§", $this->arena->data['signs']['status_line_1']);
        $this->line2 = str_replace("&", "§", $this->arena->data['signs']['status_line_2']);
        $this->line3 = str_replace("&", "§", $this->arena->data['signs']['status_line_3']);
        $this->line4 = str_replace("&", "§", $this->arena->data['signs']['status_line_4']);
        if(!$this->arena->plugin->getServer()->getWorldManager()->isWorldGenerated($this->arena->data['signs']['join_sign_world'])) {
            $this->arena->plugin->getServer()->getWorldManager()->generateWorld($this->arena->data['signs']['join_sign_world'], null);
            $this->arena->plugin->getServer()->getWorldManager()->loadWorld($this->arena->data['signs']['join_sign_world']);
        }
        if(!$this->arena->plugin->getServer()->getWorldManager()->isWorldLoaded($this->arena->data['signs']['join_sign_world'])) {
            $this->arena->plugin->getServer()->getWorldManager()->loadWorld($this->arena->data['signs']['join_sign_world']);
        }
    }

    public function onRun() : void {
        if(strtolower($this->arena->data['signs']['enable_status']) === 'true') {
            $this->updateTime++;
            if($this->updateTime >= $this->arena->data['signs']['sign_update_time']) {
                $vars = ['%alive', '%dead', '%status', '%type', '%max', '&'];
                $replace = [count(array_merge($this->arena->ingamep, $this->arena->lobbyp)), count($this->arena->deads), $this->arena->getStatus(), $this->arena->data['type'], $this->arena->getMaxPlayers(), "§"];
				$tile = $this->arena->plugin->getServer()->getWorldManager()->getWorldByName($this->arena->data['signs']['join_sign_world'])->getTileAt($this->arena->data['signs']['join_sign_x'], $this->arena->data['signs']['join_sign_y'], $this->arena->data['signs']['join_sign_z']);
				//Doesn't update for some reason, look into it later.
				if ($tile instanceof Sign) {
					$tile->setText(new SignText([str_replace($vars, $replace, $this->line1), str_replace($vars, $replace, $this->line2), str_replace($vars, $replace, $this->line3), str_replace($vars, $replace, $this->line4)]));
				}
                $this->updateTime = 0;
            }
        }

        if($this->arena->game === 0) {
            if(count($this->arena->lobbyp) >= $this->arena->getMinPlayers() || $this->forcestart === true) {
                $this->lobbyTime--;
                foreach ($this->arena->lobbyp as $p) {
                    $p->sendPopup(str_replace("%1", $this->lobbyTime, $this->arena->plugin->getMsg('starting')));
                    if ($this->lobbyTime === 1) {
                        $p->sendPopup(str_replace("%1", $this->lobbyTime, $this->arena->plugin->getMsg('starting_1_sec')));
                    }
                }
                if ($this->lobbyTime <= 0) {
                	$this->arena->startGame();
                	$this->lobbyTime = $this->arena->data['arena']['lobby_time'];
                	$this->forcestart = false;
                }
            } else {
                $this->lobbyTime = $this->arena->data['arena']['lobby_time'];
            }
        }
        if($this->arena->game === 1) {
            $this->lobbyTime = $this->arena->data['arena']['lobby_time'];
            if($this->currentRound > $this->arena->data['arena']['max_rounds']) {
                $this->arena->stopGame();
                $this->resetVars();
                return;
            }
            switch ($this->time) {
				case 0:
					$this->arena->removeAllExpectOne();
					break;
				case -3:
					$this->currentRound++;
					$this->setNewColorTime();
					$this->arena->resetFloor();
					$this->arena->gamePopup('wait');
					break;
				case -8:
					$this->time = $this->colorTime;
					$this->arena->setColor(rand(0, 15));
					$this->arena->gamePopup('3+');
					break;
			}
            if (count($this->arena->ingamep) <= 1) {
            	$this->arena->checkAlive();
            }
			$this->arena->playEndingSound($this->time);
			$this->arena->gamePopup($this->time);
            $this->time--;
        }
    }

    private function resetVars() {
		$this->currentRound = 0;
		$this->colorTime = 5;
		$this->time = -3;
		$this->percentage = 0.2;
	}

    private function setNewColorTime() {
    	$percent = $this->currentRound / $this->arena->data['arena']['max_rounds'];
    	if ($percent >= $this->percentage && $percent <= ($this->percentage + 0.1) && $this->colorTime > 1) {
    		$this->colorTime--;
    		$this->arena->messageArenaPlayers("Time has been reduced to " . $this->colorTime . "s.");
    		if ($this->percentage < 1) {
				$this->percentage = $this->percentage + 0.2;
			}
    	}
	}
}