<?php

namespace ColorMatch\Arena;

use pocketmine\block\tile\Sign;
use pocketmine\block\utils\SignText;
use pocketmine\scheduler\Task;

class ArenaSchedule extends Task{

    private $mainTime;
    private int $time = 0;
    private $startTime;
    private int $updateTime = 0;

    private $forcestart = false;

    private $arena;

    #sign lines
    private $line1;
    private $line2;
    private $line3;
    private $line4;

    public function __construct(Arena $arena) {
        $this->arena = $arena;
		$this->startTime = $this->arena->data['arena']['starting_time'];
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
			$this->mainTime = $this->arena->data['arena']['max_game_time'];
			$this->time = 0;
            if(count($this->arena->lobbyp) >= $this->arena->getMinPlayers() || $this->forcestart === true) {
                $this->startTime--;
                foreach ($this->arena->lobbyp as $p) {
                    $p->sendPopup(str_replace("%1", $this->startTime, $this->arena->plugin->getMsg('starting')));
                    if ($this->startTime === 1) {
                        $p->sendPopup(str_replace("%1", $this->startTime, $this->arena->plugin->getMsg('starting_1_sec')));
                    }
                    if ($this->startTime === 0) {
                        $p->sendPopup(('§aCommence The Game!'));
                    }
                }

                if($this->startTime <= 0) {
                    if(count($this->arena->lobbyp) >= $this->arena->getMinPlayers() || $this->forcestart === true) {
                        $this->arena->startGame();
                        $this->startTime = $this->arena->data['arena']['starting_time'];
                        $this->forcestart = false;
                    }
                    else{
                        $this->startTime = $this->arena->data['arena']['starting_time'];
                    }
                }
            }
            else{
                $this->startTime = $this->arena->data['arena']['starting_time'];
            }
        }
        if($this->arena->game === 1) {
            $this->startTime = $this->arena->data['arena']['starting_time'];
            $this->mainTime--;
            if($this->mainTime === 0) {
                $this->arena->stopGame();
            } else {
                if($this->time == $this->arena->data['arena']['color_wait_time']) {
					$this->arena->gamePopup("freeze");
					$this->arena->playEndingSound(4);
                    $this->arena->removeAllExpectOne();
                }
                if ($this->time == $this->arena->data['arena']['color_wait_time'] - 3) {
                	$this->arena->playEndingSound(1);
                	$this->arena->gamePopup(3);
				}
				if ($this->time == $this->arena->data['arena']['color_wait_time'] - 2) {
					$this->arena->playEndingSound(2);
					$this->arena->gamePopup(2);
				}
				if ($this->time == $this->arena->data['arena']['color_wait_time'] - 1) {
					$this->arena->playEndingSound(3);
					$this->arena->gamePopup(1);
				}
                if($this->time == $this->arena->data['arena']['color_wait_time'] + 3) {
                    $this->time = 0;
                    $this->arena->setColor(rand(0, 15));
                    $this->arena->resetFloor();
					$this->arena->gamePopup("wait");
                }
                if(count($this->arena->ingamep) <= 1) {
                    $this->arena->checkAlive();
                }
                $this->time++;
            }
        }
    }
}