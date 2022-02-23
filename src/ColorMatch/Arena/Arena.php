<?php

namespace ColorMatch\Arena;

use ColorMatch\ColorMatch;
use ColorMatch\Events\PlayerJoinArenaEvent;
use ColorMatch\Events\PlayerLoseArenaEvent;
//use ColorMatch\Events\PlayerWinArenaEvent;
use ColorMatch\Events\ArenaColorChangeEvent;
use ColorMatch\Utils\GetFormattingColor;
use pocketmine\block\BlockFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;
use pocketmine\world\Position;
use Throwable;

class Arena implements Listener{

    public $id;
    public $plugin;
    public $data;

    public array $lobbyp = [];
    public array $ingamep = [];
    public array $spec = [];

    public $game = 0;

    public $currentColor = 0;

    public array $winners = [];

    public $deads = [];

    public $setup = false;
    public $getFile;
	private $players;
	private array $rewardItem = [];

	public function __construct($id, ColorMatch $plugin) {
        $this->id = $id;
        $this->plugin = $plugin;
        $this->data = $plugin->arenas[$id];
        $this->checkWorlds();
        $this->resetFloor();
        //$this->registerCmd("cm", ['description' => "ColorMatch command", 'permission' => "cm.command"]);
        //$game->registerCmd("cm", ['description' => "main command", 'usage' => "/cm", 'permission' => "cm.command"]);
    }

    public function enableScheduler() {
        $this->plugin->getScheduler()->scheduleRepeatingTask(new ArenaSchedule($this), 20);
    }

    public function onBlockTouch(PlayerInteractEvent $e)
    {
        $b = $e->getBlock()->getPosition();
        $p = $e->getPlayer();
        if ($p->hasPermission("cm.sign") || $this->plugin->getServer()->isOp($p->getName())) {
            if ($b->x == $this->data["signs"]["join_sign_x"] && $b->y == $this->data["signs"]["join_sign_y"] && $b->z == $this->data["signs"]["join_sign_z"] && $b->world === $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data["signs"]["join_sign_world"])) {
                if ($this->getPlayerMode($p) === 0 || $this->getPlayerMode($p) === 1 || $this->getPlayerMode($p) === 2) {
                    return;
                }
                $this->getFile = new Config($this->plugin->getDataFolder() . "arenas/$this->id.yml", Config::YAML);
                if ($this->getFile->get('enabled') === 'true') {
                    $this->joinToArena($p);
                    return;
                } elseif ($this->getFile->get('enabled') === 'false') {
                    $p->sendMessage($this->plugin->getPrefix() . $this->plugin->getMsg('arena_not_enabled'));
                    return;
                }
            }
                if ($b->x == $this->data["signs"]["return_sign_x"] && $b->y == $this->data["signs"]["return_sign_y"] && $b->z == $this->data["signs"]["return_sign_z"] && $b->world === $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data["arena"]["arena_world"])) {
                    if ($this->getPlayerMode($p) === 0 || $this->getPlayerMode($p) === 2) {
                        $this->leaveArena($p);
                    }
                }
            return;
            }
        }

    public function getPlayerMode(Player $p) {
        if(isset($this->lobbyp[strtolower($p->getName())])) {
            return 0;
        }
        if(isset($this->ingamep[strtolower($p->getName())])) {
            return 1;
        }
        if(isset($this->spec[strtolower($p->getName())])) {
            return 2;
        }
        return false;
    }

    public function messageArenaPlayers($msg) {
        $ingame = array_merge($this->lobbyp, $this->ingamep, $this->spec);
        foreach($ingame as $p) {
            $p->sendMessage($this->plugin->getPrefix().$msg);
        }
    }

    public function joinToArena(Player $p)
    {
        if ($p->hasPermission("cm.access") || $this->plugin->getServer()->isOp($p->getName())) {
            if ($this->setup === true) {
                $p->sendMessage($this->plugin->getPrefix() . $this->plugin->getMsg('arena_in_setup'));
                return;
            }
            if (count($this->lobbyp) >= $this->getMaxPlayers()) {
                $p->sendMessage($this->plugin->getPrefix() . $this->plugin->getMsg('game_full'));
                return;
            }
            if ($this->game === 1) {
                $p->sendMessage($this->plugin->getPrefix() . $this->plugin->getMsg('ingame'));
                return;
            }
            $event = new PlayerJoinArenaEvent($this->plugin, $p, $this);
            $event->call();
            if ($event->isCancelled()) {
                return;
            }
                $this->saveInv($p);
                $p->teleport(new Position($this->data['arena']['lobby_position_x'], $this->data['arena']['lobby_position_y'], $this->data['arena']['lobby_position_z'], $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data['arena']['arena_world'])));
                $this->lobbyp[strtolower($p->getName())] = $p;
                $vars = ['%1'];
                $replace = [$p->getName()];
                $this->messageArenaPlayers(str_replace($vars, $replace, $this->plugin->getMsg('join_others')));
                //$this->checkLobby();
                return;
        }
        $p->sendMessage($this->plugin->getPrefix() . $this->plugin->getMsg('has_not_permission'));
    }

    public function leaveArena(Player $p) {
		if($this->getPlayerMode($p) == 0) {
            unset($this->lobbyp[strtolower($p->getName())]);
            $p->teleport(new Position($this->data['arena']['leave_position_x'], $this->data['arena']['leave_position_y'], $this->data['arena']['leave_position_z'], $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data['arena']['leave_position_world'])));
        }
        if($this->getPlayerMode($p) == 1) {
            unset($this->ingamep[strtolower($p->getName())]);
            $this->messageArenaPlayers(str_replace("%1", $p->getName(), $this->plugin->getMsg('leave_others')));
			$p->teleport(new Position($this->data['arena']['leave_position_x'], $this->data['arena']['leave_position_y'], $this->data['arena']['leave_position_z'], $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data['arena']['leave_position_world'])));
			$this->checkAlive();
        }
        if($this->getPlayerMode($p) == 2) {
            unset($this->spec[strtolower($p->getName())]);
            $p->setGamemode(Gamemode::SURVIVAL());
            $p->teleport(new Position($this->data['arena']['leave_position_x'], $this->data['arena']['leave_position_y'], $this->data['arena']['leave_position_z'], $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data['arena']['leave_position_world'])));
        }
        if(isset($this->players[strtolower($p->getName())]['arena'])) {
            unset($this->players[strtolower($p->getName())]['arena']);
        }
        if(!$this->plugin->getServer()->getWorldManager()->isWorldGenerated($this->data['arena']['leave_position_world'])) {
            $this->plugin->getServer()->getWorldManager()->generateWorld($this->data['arena']['leave_position_world'], null);
        }
        if(!$this->plugin->getServer()->getWorldManager()->isWorldLoaded($this->data['arena']['leave_position_world'])) {
            $this->plugin->getServer()->getWorldManager()->loadWorld($this->data['arena']['leave_position_world']);
        }
        $p->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('leave'));
        $this->loadInv($p);
        $p->getEffects()->clear();
    }

    public function startGame() {
        $ingame = array_merge($this->lobbyp, $this->ingamep, $this->spec);
        foreach($ingame as $pl) {
            if ($pl === null) {
                return false;
            } else {
				$this->game = 1;
				foreach ($this->lobbyp as $p) {
					unset($this->lobbyp[strtolower($p->getName())]);
					$this->ingamep[strtolower($p->getName())] = $p;
					$p->teleport(new Position($this->data['arena']['join_position_x'], $this->data['arena']['join_position_y'], $this->data['arena']['join_position_z'], $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data['arena']['arena_world'])));
					$this->giveEffect($p);
				}
				$this->setColor(rand(0, 15));
				$this->gamePopup('wait');
                $this->resetFloor();
            }
        }
        return true;
    }

	//TODO add more types.
    public function giveEffect(Player $p) {
    	switch($this->data['type']) {
    		case "furious":
    			$p->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), 2147483647, 3, false));
    			break;
			case "stoned":
				$p->getEffects()->add(new EffectInstance(VanillaEffects::NAUSEA(), 2147483647, 9, false));
				break;
    	}
    	$p->getEffects()->add(new EffectInstance(VanillaEffects::SATURATION(), 2147483647, 10, false));
	}

   public function resetFloor() {
        $colorcount = 0;
        $blocks = 0;
        $y = $this->data['arena']['floor_y'];
        $level =  $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data['arena']['arena_world']);
        for($x = min($this->data['arena']['first_corner_x'], $this->data['arena']['second_corner_x']); $x <= max($this->data['arena']['first_corner_x'], $this->data['arena']['second_corner_x']); $x += 3) {
            for($z = min($this->data['arena']['first_corner_z'], $this->data['arena']['second_corner_z']); $z <= max($this->data['arena']['first_corner_z'], $this->data['arena']['second_corner_z']); $z += 3) {
                $blocks++;
                $color = rand(0, 15);
                if($colorcount === 0 && $blocks === 15 || $colorcount <= 1 && $blocks === 40) {
                    $color = $this->currentColor;
                }
                $block = BlockFactory::getInstance()->get($this->getBlock(), $color);
                if($block->getMeta() === $this->currentColor) {
                    $colorcount++;
                }
				//Switching this plugin to schematics ASAP
				$level->setBlock(new Position($x, $y, $z, $level), $block, true);
				$level->setBlock(new Position($x + 1, $y, $z, $level), $block, true);
				$level->setBlock(new Position($x + 2, $y, $z, $level), $block, true);
				$level->setBlock(new Position($x, $y, $z+1, $level), $block, true);
				$level->setBlock(new Position($x, $y, $z+2, $level), $block, true);
				$level->setBlock(new Position($x + 1, $y, $z + 1, $level), $block, true);
				$level->setBlock(new Position($x + 1, $y, $z + 2, $level), $block, true);
				$level->setBlock(new Position($x + 2, $y, $z + 1, $level), $block, true);
				$level->setBlock(new Position($x + 2, $y, $z + 2, $level), $block, true);
            }
        }
    }

    public function getBlock() {
        if(strtolower($this->data['material']) == "wool") {
            return VanillaBlocks::WOOL()->getId();
        }
        elseif(strtolower($this->data['material']) == "terracotta") {
            return VanillaBlocks::STAINED_CLAY()->getId();
        }
        elseif(strtolower($this->data['material']) == "glass") {
            return VanillaBlocks::STAINED_GLASS()->getId();
        }
        elseif(strtolower($this->data['material']) == "concrete") {
            return VanillaBlocks::CONCRETE()->getId();
        }
        return false;
    }

    public function removeAllExpectOne() {
        $y = $this->data['arena']['floor_y'];
        $level =  $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data['arena']['arena_world']);
        $color = $this->currentColor;
        for($x = min($this->data['arena']['first_corner_x'], $this->data['arena']['second_corner_x']); $x <= max($this->data['arena']['first_corner_x'], $this->data['arena']['second_corner_x']); $x++) {
            for($z = min($this->data['arena']['first_corner_z'], $this->data['arena']['second_corner_z']); $z <= max($this->data['arena']['first_corner_z'], $this->data['arena']['second_corner_z']); $z++) {
                if($level->getBlock(new Vector3($x, $y, $z))->getMeta() !== $color && $level->getBlock(new Vector3($x, $y, $z))->getId() === $this->getBlock()) {
                    $level->setBlock(new Vector3($x, $y, $z), BlockFactory::getInstance()->get(0, 0), false, true);
                }
            }
        }
    }

    public function playEndingSound($soundStage) {
		$ingame = array_merge($this->lobbyp, $this->ingamep, $this->spec);
		foreach ($ingame as $p) {
			switch ($soundStage) {
				case 1:
					$p->getWorld()->addSound($p->getPosition(), new NoteSound(NoteInstrument::DOUBLE_BASS(),24), [$p]);
					$p->getWorld()->addSound($p->getPosition(), new NoteSound(NoteInstrument::PIANO(),12), [$p]);
					break;
				case 2:
					$p->getWorld()->addSound($p->getPosition(), new NoteSound(NoteInstrument::DOUBLE_BASS(),20), [$p]);
					$p->getWorld()->addSound($p->getPosition(), new NoteSound(NoteInstrument::PIANO(),8), [$p]);
					break;
				case 3:
					$p->getWorld()->addSound($p->getPosition(), new NoteSound(NoteInstrument::DOUBLE_BASS(),15), [$p]);
					$p->getWorld()->addSound($p->getPosition(), new NoteSound(NoteInstrument::PIANO(),3), [$p]);
					break;
				case 4:
					$sound = PlaySoundPacket::create("mob.bat.takeoff", $p->getPosition()->x, $p->getPosition()->y, $p->getPosition()->z, 1, 0.8);
					$p->getServer()->broadcastPackets($ingame, [$sound]);
					break;
			}
		}
	}

	public function gamePopup($stage) {
		$ingame = array_merge($this->lobbyp, $this->ingamep, $this->spec);
		$color = new GetFormattingColor();
		$color = $color->get($this->currentColor);
		foreach ($ingame as $p) {
			switch ($stage) {
				case 3:
					$p->sendPopup($color . '⬜⬛⬛⬛⬛⬛⬜');
					break;
				case 2:
					$p->sendPopup($color . '⬜⬜⬛⬛⬛⬜⬜');
					break;
				case 1:
					$p->sendPopup($color . '⬜⬜⬜⬛⬜⬜⬜');
					break;
				case 'wait':
					$p->sendPopup($color . '⬛⬛⬛⬛⬛⬛⬛');
					break;
				case 'freeze':
					$p->sendPopup('§f§lFREEZE');
					break;
			}
		}
	}

    public function onQuit(PlayerQuitEvent $e) {
        if($this->getPlayerMode($e->getPlayer()) !== false) {
            $this->leaveArena($e->getPlayer());
        }
    }

    public function onKick(PlayerKickEvent $e) {
        if($this->getPlayerMode($e->getPlayer()) !== false) {
            $this->leaveArena($e->getPlayer());
        }
    }

    public function checkAlive() {
        if(count($this->ingamep) <= 1) {
            $this->stopGame();
        }
    }

    public function stopGame() {
		foreach($this->ingamep as $p) {
			$this->checkWinners($p);
		}
		$this->game = 0;
		$this->broadcastResults();
		$this->unsetAllPlayers();
    	$this->resetFloor();
		return;
}
    public function abruptStop() {
        $ingame = array_merge($this->lobbyp, $this->ingamep, $this->spec);
        foreach($ingame as $p) {
            $p->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('abrupt_stop'));
        }
        $this->unsetAllPlayers();
        $this->game = 0;
        $this->resetFloor();
    }

    public function unsetAllPlayers() {
		$ingame = array_merge($this->lobbyp, $this->ingamep, $this->spec);
        foreach($ingame as $p) {
			$p->getEffects()->clear();
			$this->loadInv($p);
            unset($this->ingamep[strtolower($p->getName())]);
			unset($this->lobbyp[strtolower($p->getName())]);
            $p->teleport(new Position($this->data['arena']['leave_position_x'], $this->data['arena']['leave_position_y'], $this->data['arena']['leave_position_z'], $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data['arena']['leave_position_world'])));
        }
        foreach($this->spec as $p) {
			$p->setGamemode(Gamemode::SURVIVAL());
			unset($this->spec[strtolower($p->getName())]);
        }

        foreach($this->winners as $pName) {
        	if ($pName !== '---') {
				list($id, $damage, $count) = $this->rewardItem;
				$p = $this->plugin->getServer()->getPlayerExact($pName);
				try {
					$p->getInventory()->addItem(ItemFactory::getInstance()->get($id, $damage, $count));
				} catch (Throwable $e) {
					$this->plugin->getLogger()->error($this->plugin->getMsg('attempted_itemgive'));
				}
				$this->winners = [];
			}
		}
    }
    public function saveInv(Player $p) {
        $items = [];
        foreach($p->getInventory()->getContents() as $slot=>&$item) {
            $items[$slot] = implode(":", [$item->getId(), $item->getMeta(), $item->getCount()]);
        }
        $this->plugin->inv[strtolower($p->getName())] = $items;
        $p->getInventory()->clearAll();
    }

    public function loadInv(Player $p) {
        if(!($p->isOnline())) {
            return;
        }
        $p->getInventory()->clearAll();
        foreach($this->plugin->inv[strtolower($p->getName())] as $slot => $i) {
            list($id, $dmg, $count) = explode(":", $i);
            $item = ItemFactory::getInstance()->get($id, $dmg, $count);
            $p->getInventory()->setItem($slot, $item);
            unset($this->plugin->inv[strtolower($p->getName())]);
        }
    }

	public function deathHandler(Player $p) {
		$ingame = array_merge($this->lobbyp, $this->ingamep, $this->spec);
		$event = new PlayerLoseArenaEvent($this->plugin, $p, $this, $ingame);
		$event->call();
		$p->getInventory()->clearAll();
		unset($this->ingamep[strtolower($p->getName())]);
		if ($this->data['arena']['spectator_mode'] == 'true') {
			$this->spec[strtolower($p->getName())] = $p;
			$p->setGamemode(Gamemode::SPECTATOR());
			$p->teleport(new Position($this->data['arena']['spec_spawn_x'], $this->data['arena']['spec_spawn_y'], $this->data['arena']['spec_spawn_z'], $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data['arena']['arena_world'])));
		} else {
			$p->teleport(new Position($this->data['arena']['leave_position_x'], $this->data['arena']['leave_position_y'], $this->data['arena']['leave_position_z'], $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data['arena']['leave_position_world'])));
			$this->loadInv($p);
		}
		foreach($ingame as $pl) {
			$pl->sendMessage($this->plugin->getPrefix().str_replace(['%2', '%1'], [count($this->ingamep), $p->getName()], $this->plugin->getMsg('death')));
		}
		$this->checkAlive();
	}

    public function onChat(PlayerChatEvent $e) {
        $p = $e->getPlayer();
        if($this->getPlayerMode($p) !== false) {
            $e->cancel();
            $ingame = array_merge($this->lobbyp, $this->ingamep, $this->spec);
            foreach($ingame as $pl) {
                $pl->sendMessage($p->getName()." > ".$e->getMessage());
            }
        }
    }

    public function onDropItem(PlayerDropItemEvent $e) {
        $p = $e->getPlayer();
        if($this->getPlayerMode($p) !== false) {
            $e->cancel();
        }
    }

    public function getMaxPlayers() {
        return $this->data['arena']['max_players'];
    }

    public function getMinPlayers() {
        return $this->data['arena']['min_players'];
    }

    public function checkWinners(Player $p) {
		if(count($this->ingamep) <= 3) {
			array_push($this->winners, $p->getName());
		}
	}

    public function broadcastResults() {
		foreach($this->winners as $p) {
			$this->giveReward($this->plugin->getServer()->getPlayerExact($p));
			//$event = new PlayerWinArenaEvent($this->plugin, $this->plugin->getServer()->getPlayerExact($this->winners[0]), $this);
			//$event->call();
		}
		if(!isset($this->winners[0])) $this->winners[0] = "---";
		if(!isset($this->winners[1])) $this->winners[1] = "---";
		if(!isset($this->winners[2])) $this->winners[2] = "---";
		$vars = ['%1', '%2', '%3'];
		$replace = [$this->winners[0], $this->winners[1], $this->winners[2]];
		$msg = str_replace($vars, $replace, $this->plugin->getMsg('end_game'));
		$ingame = array_merge($this->ingamep, $this->lobbyp, $this->spec);
		foreach($ingame as $p) {
			$p->sendMessage($msg);
		}
	}

    public function setColor($color) {
        ($event = new ArenaColorChangeEvent($this->plugin, $this, $this->currentColor, $color));
		$event->call();
        if($event->isCancelled()) {
            return;
        }
        $this->currentColor = $event->getNewColor();
        foreach($this->ingamep as $p) {
            $p->getInventory()->setItem(3, ItemFactory::getInstance()->get($this->getBlock(), $color, 1));
            $p->getInventory()->setItem(4, ItemFactory::getInstance()->get($this->getBlock(), $color, 1));
            $p->getInventory()->setItem(5, ItemFactory::getInstance()->get($this->getBlock(), $color, 1));
        }
    }

    public function onHit(EntityDamageEvent $e) {
        if ($e->getEntity() instanceof Player) {

            if ($this->getPlayerMode($e->getEntity()) === 1 and $e->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK || $e->getCause() === EntityDamageEvent::CAUSE_STARVATION || $e->getCause() === EntityDamageEvent::CAUSE_MAGIC || $e->getCause() === EntityDamageEvent::CAUSE_PROJECTILE || $e->getCause() === EntityDamageEvent::CAUSE_SUFFOCATION) {
                $e->cancel();
            }

            if ($this->getPlayerMode($e->getEntity()) === 0) {
                $e->cancel();
            }

            if ($this->getPlayerMode($e->getEntity()) === 2) {
                $e->cancel();
            }

            if ($this->getPlayerMode($e->getEntity()) === 1 && ($e->getFinalDamage() >= $e->getEntity()->getHealth())) {
            	$e->cancel();
            	$this->deathHandler($e->getEntity());
            	$e->getEntity()->heal(new EntityRegainHealthEvent($e->getEntity(), $e->getEntity()->getMaxHealth(), EntityRegainHealthEvent::CAUSE_CUSTOM));
            	if ($e->getEntity()->isOnFire()) {
					$e->getEntity()->extinguish();
					//Why doesn't this work is the question, the if statement is reached but extinguish doesn't work unless called later.
				}

			}
        }
    }

    public function onBlockBreak(BlockBreakEvent $e) {
        $p = $e->getPlayer();
        if($this->getPlayerMode($p) !== false) {
            $e->cancel();
        }
    }

    public function onBlockPlace(BlockPlaceEvent $e) {
        $p = $e->getPlayer();
        if($this->getPlayerMode($p) !== false) {
            $e->cancel();
        }
    }

    public function kickPlayer($p, $reason = "")
    {
        $players = array_merge($this->ingamep, $this->lobbyp, $this->spec);
        if ($reason != "") {
            $players[strtolower($p)]->sendMessage(str_replace("%1", $reason, $this->plugin->getMsg('kick_from_game_reason')));
        } else {
            $players[strtolower($p)]->sendMessage(str_replace("%1", $reason, $this->plugin->getMsg('kick_from_game')));
        }
        $this->leaveArena($players[strtolower($p)]);
    }

    public function getStatus() {
        if($this->game === 0) return "lobby";
        if($this->game === 1) return "ingame";
    }

    public function giveReward(Player $p) {
        if(isset($this->data['arena']['item_reward']) && $this->data['arena']['item_reward'] !== null && intval($this->data['arena']['item_reward']) !== 0) {
            foreach(explode(',', str_replace(' ', '', $this->data['arena']['item_reward'])) as $item) {
                $exp = explode(':', $item);
                if(isset($exp[0])) {
                    $this->rewardItem = $exp;
                }
            }
        }

        if(isset($this->data['arena']['money_reward'])) {
        if($this->data['arena']['money_reward'] !== null && intval($this->data['arena']['money_reward']) !== 0 && $this->plugin->economy !== null) {
            if (count($this->winners) === 3) {
				$money = ($this->data['arena']['money_reward'] / 3);
			} elseif (count($this->winners) === 2) {
				$money = ($this->data['arena']['money_reward'] / 2);
			} else {
				$money = $this->data['arena']['money_reward'];
			}
			$ec = $this->plugin->economy;
            switch($this->plugin->pluginName) {
                case "EconomyAPI":
                    $ec->addMoney($p->getName(), $money);
                    break;
                case "BedrockEconomy":
                    $ec->getAPI()->addToPlayerBalance($p->getName(), $money);
                    break;
            }
            $p->sendMessage($this->plugin->getPrefix().str_replace('%1', $money, $this->plugin->getMsg('get_money')));
        }
        }
    }

    public function checkWorlds() {
        if(!$this->plugin->getServer()->getWorldManager()->isWorldGenerated($this->data['arena']['arena_world'])) {
            $this->plugin->getServer()->getWorldManager()->generateWorld($this->data['arena']['arena_world'], null);
        }
        if(!$this->plugin->getServer()->getWorldManager()->isWorldLoaded($this->data['arena']['arena_world'])) {
            $this->plugin->getServer()->getWorldManager()->loadWorld($this->data['arena']['arena_world']);
        }
        if(!$this->plugin->getServer()->getWorldManager()->isWorldGenerated($this->data['signs']['join_sign_world'])) {
            $this->plugin->getServer()->getWorldManager()->generateWorld($this->data['signs']['join_sign_world'], null);
        }
        if(!$this->plugin->getServer()->getWorldManager()->isWorldLoaded($this->data['signs']['join_sign_world'])) {
            $this->plugin->getServer()->getWorldManager()->loadWorld($this->data['signs']['join_sign_world']);
        }
        if(!$this->plugin->getServer()->getWorldManager()->isWorldGenerated($this->data['arena']['leave_position_world'])) {
            $this->plugin->getServer()->getWorldManager()->generateWorld($this->data['arena']['leave_position_world'], null);
        }
        if(!$this->plugin->getServer()->getWorldManager()->isWorldLoaded($this->data['arena']['leave_position_world'])) {
            $this->plugin->getServer()->getWorldManager()->loadWorld($this->data['arena']['leave_position_world']);
        }
    }
}
