<?php

namespace ColorMatch;

use ColorMatch\Arena\Arena;
use ColorMatch\Arena\Setup\FormSetup;
use pocketmine\block\BaseSign;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class ColorMatch extends PluginBase implements Listener{

	public $cfg;
	public $msg;
	public $arenas = [];
	public $ins = [];
	public $selectors = [];
	public $inv = [];
	public $armorInv = [];
	public $offHandItem = [];
	public $setters = [];
	public $pluginName;

	public $economy;
	public $getFile;

	public function onEnable() : void {
		$this->initConfig();
		$this->registerEconomy();
		$this->checkArenas();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function setArenasData(Config $arena, $name) {
		$this->arenas[$name] = $arena->getAll();
		$this->arenas[$name]['enabled'] = true;
		$game = new Arena($name, $this);
		$game->enableScheduler();
		$this->ins[$name] = $game;
		$this->getServer()->getPluginManager()->registerEvents($game, $this);
	}

	public function initConfig() {
		$this->saveResource("config.yml");
		$this->cfg = new Config($this->getDataFolder()."config.yml", Config::YAML);
		@mkdir($this->getDataFolder()."arenas/");
		$this->saveResource("languages/English.yml");
		$this->msg = new Config($this->getDataFolder()."languages/{$this->cfg->get('Language')}.yml", Config::YAML);
		$this->getServer()->getLogger()->info("[ColorMatch] Selected language: {$this->cfg->get('Language')}");
		}

	public function checkArenas() {
		foreach (glob($this->getDataFolder() . "arenas/*.yml") as $file) {
			$arena = new Config($file, Config::YAML);
			$this->arenas[basename($file, ".yml")] = $arena->getAll();
			$fname = basename($file);
			if (strtolower($arena->get("enabled")) === "false") {
				$this->arenas[basename($file, ".yml")]['enabled'] = 'false';
				$this->getLogger()->error("Arena \"$fname\" is currently disabled.");
				return;
			}
			if ($this->checkFile($arena) === false) {
				$this->arenas[basename($file, ".yml")]['enabled'] = 'false';
				$this->getLogger()->error("Arena \"$fname\" is not set properly.");
				return;
			}
			$this->setArenasData($arena, basename($file, ".yml"));
		}
	}

	public function checkFile(Config $arena) {
		if(!(is_numeric($arena->getNested("signs.join_sign_x")) && is_numeric($arena->getNested("signs.join_sign_y")) && is_numeric($arena->getNested("signs.join_sign_z")) && is_string($arena->getNested("signs.join_sign_world")) && is_string($arena->getNested("signs.status_line_1")) && is_string($arena->getNested("signs.status_line_2")) && is_string($arena->getNested("signs.status_line_3")) && is_string($arena->getNested("signs.status_line_4")) && is_numeric($arena->getNested("signs.return_sign_x")) && is_numeric($arena->getNested("signs.return_sign_y")) && is_numeric($arena->getNested("signs.return_sign_z")) && is_string($arena->getNested("arena.arena_world")) && is_numeric($arena->getNested("arena.join_position_x")) && is_numeric($arena->getNested("arena.join_position_y")) && is_numeric($arena->getNested("arena.join_position_z")) && is_numeric($arena->getNested("arena.lobby_position_x")) && is_numeric($arena->getNested("arena.lobby_position_y")) && is_numeric($arena->getNested("arena.lobby_position_z")) && is_numeric($arena->getNested("arena.first_corner_x")) && is_numeric($arena->getNested("arena.first_corner_z")) && is_numeric($arena->getNested("arena.second_corner_x")) && is_numeric($arena->getNested("arena.second_corner_z")) && is_numeric($arena->getNested("arena.spec_spawn_x")) && is_numeric($arena->getNested("arena.spec_spawn_y")) && is_numeric($arena->getNested("arena.spec_spawn_z")) && is_numeric($arena->getNested("arena.leave_position_x")) && is_numeric($arena->getNested("arena.leave_position_y")) && is_numeric($arena->getNested("arena.leave_position_z")) && is_string($arena->getNested("arena.leave_position_world")) && is_numeric($arena->getNested("arena.max_rounds")) && is_numeric($arena->getNested("arena.max_players")) && is_numeric($arena->getNested("arena.min_players")) && is_numeric($arena->getNested("arena.lobby_time")) && /*is_numeric($arena->getNested("arena.color_wait_time")) && */is_numeric($arena->getNested("arena.floor_y")) && is_numeric($arena->getNested("arena.money_reward")))) {
			return false;
		}
		if(!((strtolower($arena->get("type")) == "furious" || strtolower($arena->get("type")) == "stoned" || strtolower($arena->get("type")) == "classic") && (strtolower($arena->get("material")) == "wool" || strtolower($arena->get("material")) == "terracotta" || strtolower($arena->get("material")) == "glass" || strtolower($arena->get("material")) == "concrete") && (strtolower($arena->getNested("signs.enable_status")) == "true" || strtolower($arena->getNested("signs.enable_status")) == "false") && (strtolower($arena->getNested("arena.spectator_mode")) == "true" || strtolower($arena->getNested("arena.spectator_mode")) == "false") && (strtolower($arena->get("enabled")) == "true" || strtolower($arena->get("enabled")) == "false"))) {
			return false;
		}
		return true;
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args): bool {
		switch($cmd->getName()) {
			case "cm":
				if(isset($args[0])) {
					if($sender instanceof Player) {
						switch (strtolower($args[0])) {
							case "help":
								if (!$sender->hasPermission("cm.command.help")) {
									$sender->sendMessage($this->getMsg('has_not_permission'));
									break;
								}
								$msg = "§9--- §c§lColorMatch help§l§9 ---§r§f";
								if ($sender->hasPermission('cm.command.leave')) $msg .= $this->getMsg('onleave');
								if ($sender->hasPermission('cm.command.join')) $msg .= $this->getMsg('onjoin');
								if ($sender->hasPermission('cm.command.start')) $msg .= $this->getMsg('start');
								if ($sender->hasPermission('cm.command.stop')) $msg .= $this->getMsg('stop');
								if ($sender->hasPermission('cm.command.kick')) $msg .= $this->getMsg('kick');
								if ($sender->hasPermission('cm.command.set')) $msg .= $this->getMsg('set');
								if ($sender->hasPermission('cm.command.delete')) $msg .= $this->getMsg('delete');
								if ($sender->hasPermission('cm.command.create')) $msg .= $this->getMsg('create');
								if ($sender->hasPermission('cm.command.toggle')) $msg .= $this->getMsg('toggle');
								$sender->sendMessage($msg);
								break;
							case "create":
								if (!$sender->hasPermission('cm.command.create')) {
									$sender->sendMessage($this->getMsg('has_not_permission'));
									break;
								}
								if (!isset($args[1]) || isset($args[2])) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('create_help'));
									break;
								}
								if ($this->arenaExist($args[1])) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('arena_already_exist'));
									break;
								}
								$a = new Config($this->getDataFolder() . "arenas/$args[1].yml", Config::YAML);
								file_put_contents($this->getDataFolder() . "arenas/$args[1].yml", $this->getResource('arenas/default.yml'));
								$this->arenas[$args[1]] = $a->getAll();
								$sender->sendMessage($this->getPrefix() . $this->getMsg('arena_create'));
								break;
							case "set":
								if (!$sender->hasPermission('cm.command.set')) {
									$sender->sendMessage($this->getMsg('has_not_permission'));
									break;
								}
								if (!isset($args[1]) || isset($args[2])) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('set_help'));
									break;
								}
								if (!$this->arenaExist($args[1])) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('arena_doesnt_exist'));
									break;
								}
								if ($this->isArenaSet($args[1])) {
									$a = $this->ins[$args[1]];
									if ($a->game !== 0 || count(array_merge($a->ingamep, $a->lobbyp, $a->spec)) > 0) {
										$sender->sendMessage($this->getPrefix() . $this->getMsg('arena_running'));
										break;
									}
									$a->setup = true;
								}
								$form = new FormSetup($args[1], $this, $sender);
								$form->mainMenu();
								break;
							case "delete":
								if (!$sender->hasPermission('cm.command.delete')) {
									$sender->sendMessage($this->getMsg('has_not_permission'));
									break;
								}
								if (!isset($args[1]) || isset($args[2])) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('delete_help'));
									break;
								}
								if (!$this->arenaExist($args[1])) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('arena_doesnt_exist'));
									break;
								}
								unlink($this->getDataFolder() . "arenas/$args[1].yml");
								unset($this->arenas[$args[1]]);
								$sender->sendMessage($this->getPrefix() . $this->getMsg('arena_delete'));
								break;
							case "join":
								if (!$sender->hasPermission('cm.command.join')) {
									$sender->sendMessage($this->getMsg('has_not_permission'));
									break;
								}
								if (!isset($args[1]) || isset($args[2])) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('join_help'));
									break;
								}

								$this->getFile = new Config($this->getDataFolder() . "arenas/$args[1].yml", Config::YAML);
								if ($this->getFile->get('enabled') === 'false') {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('arena_not_enabled'));
									break;
								}
								$this->getFile = new Config($this->getDataFolder() . "arenas/$args[1].yml", Config::YAML);
								$players = (array_merge($this->ins[$args[1]]->ingamep, $this->ins[$args[1]]->lobbyp, $this->ins[$args[1]]->spec));
								if (array_search($sender, $players) !== strtolower($sender->getName())) {
									$this->ins[$args[1]]->joinToArena($sender);
								} else {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('cannot_rejoin'));
								}
								break;
							case "leave":
								if (!$sender->hasPermission('cm.command.leave')) {
									$sender->sendMessage($this->getMsg('has_not_permission'));
									break;
								}
								if (isset($args[1])) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('leave_help'));
									break;
								}
								if ($this->getPlayerArena($sender) === false) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('use_cmd_in_game'));
									break;
								}
								$this->getPlayerArena($sender)->leaveArena($sender);
								break;
							case "start":
								if (!$sender->hasPermission('cm.command.start')) {
									$sender->sendMessage($this->getMsg('has_not_permission'));
									break;
								}
								if (isset($args[2]) || !isset($args[1])) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('start_help'));
									break;
								}
								if (!$this->arenaExist($args[1])) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('arena_doesnt_exist'));
									break;
								}
								$this->getFile = new Config($this->getDataFolder() . "arenas/$args[1].yml", Config::YAML);
								if ($this->getFile->get('enabled') === 'false') {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('arena_not_enabled'));
									break;
								}
								if (count($this->ins[$args[1]]->lobbyp) === 0) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('no_players'));
									break;
								}
								if (count($this->ins[$args[1]]->ingamep) > 0) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('ingame'));
									break;
								}
								$this->ins[$args[1]]->startGame();
								break;
							case "toggle":
								if (!$sender->hasPermission('cm.command.toggle')) {
									$sender->sendMessage($this->getMsg('has_not_permission'));
									break;
								}
								if (isset($args[2]) || !isset($args[1])) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('toggle_help'));
									break;
								}
								if (!$this->arenaExist($args[1])) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('arena_doesnt_exist'));
									break;
								}
								if (isset($this->ins[$args[1]]) && count(array_merge($this->ins[$args[1]]->ingamep, $this->ins[$args[1]]->lobbyp, $this->ins[$args[1]]->spec)) > 0) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('ingame'));
									break;
								}
								$arena = new ConfigManager($args[1], $this);
								if ($this->arenas[$args[1]]['enabled'] === 'false') {
									$arena->setToggle('true');
									$this->arenas[$args[1]]['enabled'] = 'true';
									$currentToggle = "on";
								} else {
									$arena->setToggle('false');
									$this->arenas[$args[1]]['enabled'] = 'false';
									$currentToggle = "off";
								}
								$sender->sendMessage(str_replace("%1", $currentToggle, $this->getPrefix().$this->getMsg('toggle_confirm')));
								break;
							case "stop":
								if (!$sender->hasPermission('cm.command.stop')) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('has_not_permission'));
									break;
								}
								if (isset($args[2]) || !isset($args[1])) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('stop_help'));
									break;
								}
								if (!$this->arenaExist($args[1])) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('arena_doesnt_exist'));
									break;
								}
								$this->getFile = new Config($this->getDataFolder() . "arenas/$args[1].yml", Config::YAML);
								if ($this->getFile->get('enabled') === 'false') {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('arena_not_enabled'));
									break;
								}
								$players = count(array_merge($this->ins[$args[1]]->ingamep, $this->ins[$args[1]]->lobbyp, $this->ins[$args[1]]->spec));
								if ($players === 0) {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('no_players'));
								} else {
									$this->ins[$args[1]]->abruptStop();
								}
								break;
							case "kick":
								if(!$sender->hasPermission('cm.command.kick')) {
									$sender->sendMessage($this->getMsg('has_not_permission'));
									break;
								}
								if(!isset($args[2]) || isset($args[4])) {
									$sender->sendMessage($this->getPrefix().$this->getMsg('kick_help'));
									break;
								}
								$this->getFile = new Config($this->getDataFolder() . "arenas/$args[2].yml", Config::YAML);
								if ($this->getFile->get('enabled') === 'false') {
									$sender->sendMessage($this->getPrefix() . $this->getMsg('arena_not_enabled'));
									break;
								}
								if(!isset(array_merge($this->ins[$args[1]]->ingamep, $this->ins[$args[1]]->lobbyp, $this->ins[$args[1]]->spec)[strtolower($args[2])])) {
									$sender->sendMessage($this->getPrefix().$this->getMsg('player_not_exist'));
									break;
								}
								if(!isset($args[3])) {
									$args[3] = "";
								}
								$this->ins[$args[1]]->kickPlayer($args[2], $args[3]);
								break;
							default:
								$sender->sendMessage($this->getPrefix().$this->getMsg('help'));
						}
						return true;
					}
					$sender->sendMessage('You can only run this command only in-game.');
					return false;
				}
				$sender->sendMessage($this->getPrefix().$this->getMsg('help'));
		}
		return true;
	}
	public function arenaExist($name) {
		if(isset($this->arenas[$name])) {
			return true;
		}
		return false;
	}

	public function getMsg($key) {
		return TextFormat::colorize($this->msg->get($key));
	}

	public function onBlockTouch(PlayerInteractEvent $e) {
		$p = $e->getPlayer();
		$b = $e->getBlock()->getPosition();
		if(isset($this->selectors[strtolower($p->getName())])) {
			$p->sendMessage(TextFormat::BLUE."X: ".TextFormat::GREEN.$b->x.TextFormat::BLUE." Y: ".TextFormat::GREEN.$b->y.TextFormat::BLUE." Z: ".TextFormat::GREEN.$b->z);
		}
	}

	public function getPrefix() {
		return str_replace("&", "§", $this->cfg->get('Prefix'));
	}

	public function onJoin(PlayerJoinEvent $p) {
		if(isset($this->inv[strtolower($p->getPlayer()->getName())])) {
			foreach($this->inv as $slot => $i) {
				$p->getPlayer()->getInventory()->setItem($slot, $i);
				unset($this->inv[strtolower($p->getPlayer()->getName())]);
			}
		}
		if(isset($this->armorInv[strtolower($p->getPlayer()->getName())])) {
			foreach ($this->armorInv[strtolower($p->getPlayer()->getName())] as $slot => $i) {
				$p->getPlayer()->getArmorInventory()->setItem($slot, $i);
				unset($this->armorInv[strtolower($p->getPlayer()->getName())]);
			}
		}
		if(isset($this->offHandItem[strtolower($p->getPlayer()->getName())])) {
			$p->getPlayer()->getOffHandInventory()->setItem(0, $this->offHandItem[strtolower($p->getPlayer()->getName())]);
			unset($this->offHandItem[strtolower($p->getPlayer()->getName())]);
		}
	}

	public function onBlockBreak(BlockBreakEvent $e) {
		$p = $e->getPlayer();
		if(isset($this->setters[strtolower($p->getName())]['arena']) && isset($this->setters[strtolower($p->getName())]['type'])) {
			$e->cancel();
			$b = $e->getBlock()->getPosition();
			$arena = new ConfigManager($this->setters[strtolower($p->getName())]['arena'], $this);
			if($this->setters[strtolower($p->getName())]['type'] == "setjoinsign") {
				if ($e->getBlock() instanceof BaseSign) {
					$arena->setJoinSign($b->x, $b->y, $b->z, $b->getWorld()->getDisplayName());
				} else {
					$p->sendMessage($this->getPrefix().$this->getMsg('signnotfound'));
				}
			}
			if($this->setters[strtolower($p->getName())]['type'] == "setreturnsign") {
				if ($e->getBlock() instanceof BaseSign) {
					$arena->setReturnSign($b->x, $b->y, $b->z);
				} else {
					$p->sendMessage($this->getPrefix().$this->getMsg('signnotfound'));
				}
			}
			if($this->setters[strtolower($p->getName())]['type'] == "setjoinpos") {
				$arena->setJoinPos($b->x, $b->y, $b->z);
				$arena->setArenaWorld($b->getWorld()->getDisplayName());
			}
			if($this->setters[strtolower($p->getName())]['type'] == "setlobbypos") {
				$arena->setLobbyPos($b->x, $b->y, $b->z);
			}
			if($this->setters[strtolower($p->getName())]['type'] == "setfirstcorner") {
				$arena->setFirstCorner($b->x, $b->y, $b->z);
				$p->sendMessage($this->getPrefix().$this->getMsg('second_corner'));
				$this->setters[strtolower($p->getName())]['type'] = "setsecondcorner";
				return;
			}
			if($this->setters[strtolower($p->getName())]['type'] == "setsecondcorner") {
				$arena->setSecondCorner($b->x, $b->z);
			}
			if($this->setters[strtolower($p->getName())]['type'] == "setspecspawn") {
				$arena->setSpecSpawn($b->x, $b->y, $b->z);
			}
			if($this->setters[strtolower($p->getName())]['type'] == "setleavepos") {
				$arena->setLeavePos($b->x, $b->y, $b->z, $b->getWorld()->getDisplayName());
			}
			$form = new FormSetup($this->setters[strtolower($p->getName())]['arena'], $this, $p);
			unset($this->setters[strtolower($p->getName())]);
			$form->showLocationsPage();
			return;
		}
	}

	public function onChat(PlayerChatEvent $e) {
		$p = $e->getPlayer();
		$msg = strtolower(trim($e->getMessage()));
		if (isset($this->setters[strtolower($p->getName())]['arena'])) {
			$e->cancel();
			$arena = new ConfigManager($this->setters[strtolower($p->getName())]['arena'], $this);
			if ($msg === 'set') {
				$item = $p->getInventory()->getItemInHand();
				if ($item->getId() == 0) {
					$arena->setItemReward(0);
					$p->sendMessage($this->getPrefix() . $this->getMsg('itemreward_disable'));
				} else {
					$arena->setItemReward($item->jsonSerialize());
				}
				$p->sendMessage($this->getPrefix() . $this->getMsg('disable_setup_mode'));
				$a = $this->ins[$this->setters[strtolower($p->getName())]['arena']];
				$a->setup = false;
				unset($this->setters[strtolower($p->getName())]['arena']);
				return;
			} else {
				$p->sendMessage($this->getPrefix() . $this->getMsg('invalid_arguments'));
			}
		}
	}

	public function onQuit(PlayerQuitEvent $e) {
		$p = $e->getPlayer();
		$this->unsetPlayers($p);
	}

	public function onKick(PlayerKickEvent $e) {
		$p = $e->getPlayer();
		$this->unsetPlayers($p);
	}

	public function unsetPlayers(Player $p) {
		if(isset($this->selectors[strtolower($p->getName())])) {
			unset($this->selectors[strtolower($p->getName())]);
		}
		if(isset($this->setters[strtolower($p->getName())])) {
			$this->reloadArena($this->setters[strtolower($p->getName())]['arena']);
			if($this->isArenaSet($this->setters[strtolower($p->getName())]['arena'])) {
				$a = new Arena($this->setters[strtolower($p->getName())]['arena'], $this);
				$a->setup = false;
			}
			unset($this->setters[strtolower($p->getName())]);
		}
	}

	public function reloadArena($name) {
		$arena = new Config($this->getDataFolder()."arenas/$name.yml");
		if(isset($this->ins[$name])) $this->ins[$name]->setup = false;
		if(!$this->checkFile($arena) || $arena->get('enabled') === "false") {
			$this->arenas[$name] = $arena->getAll();
			$this->arenas[$name]['enabled'] = 'false';
			return;
		}
		$this->arenas[$name] = $arena->getAll();
		return;
	}

	public function getPlayerArena(Player $p) {
		foreach($this->ins as $arena) {
			$players = array_merge($arena->ingamep, $arena->lobbyp, $arena->spec);
			if(isset($players[strtolower($p->getName())])) {
				return $arena;
			}
		}
		return false;
	}

	public function isArenaSet($name) {
		if(isset($this->ins[$name])) return true;
		return false;
	}

	public function registerEconomy() {
		$economy = ["BedrockEconomy"];
		foreach($economy as $plugin) {
			$ins = $this->getServer()->getPluginManager()->getPlugin($plugin);
			if($ins instanceof Plugin && $ins->isEnabled()) {
				$this->economy = $ins;
				$this->pluginName = $plugin;
				$this->getServer()->getLogger()->info("[ColorMatch] Hooked economy into $plugin");
				return;
			}
		}
		$this->economy = null;
	}
}