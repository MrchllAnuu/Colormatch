<?php

namespace ColorMatch\Arena\Setup;

use ColorMatch\ColorMatch;
use ColorMatch\ConfigManager;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\player\Player;

class FormSetup {

	public $arena;
	public $data;
	public $plugin;
	public $player;
	public $config;

	public function __construct($arena, ColorMatch $plugin, Player $p) {
		$this->arena = $arena;
		$this->plugin = $plugin;
		$this->config = new ConfigManager($this->arena, $this->plugin);
		$this->player = $p;
	}

	public function mainMenu() {
		$form = new SimpleForm(function (Player $player, ?int $data): void {
			if ($data !== null) {
				switch ($data) {
					case 0:
						$this->showLocationsPage();
						break;
					case 1:
						$this->showIntPlayerPage();
						break;
					case 2:
						$this->showFeaturesPage();
						break;
					case 3:
						$this->showRewardPage();
						break;
				}
			} else {
				$this->plugin->ins[$this->arena]->setup = false;
				$this->player->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('disable_setup_mode'));
			}
		});
		$form->setTitle($this->arena . ": Setup Screen");
		$form->addButton("Locations");
		$form->addButton("Intervals & Players");
		$form->addButton("Features");
		$form->addButton("Rewards");
		$this->player->sendForm($form);
	}

	public function showLocationsPage() {
		$form = new SimpleForm(function (Player $player, ?int $data): void {
			if ($data !== null) {
				$this->plugin->setters[strtolower($this->player->getName())]['arena'] = $this->arena;
				switch ($data) {
					case 0:
						$this->player->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('break_sign'));
						$this->plugin->setters[strtolower($this->player->getName())]['type'] = 'setjoinsign';
						break;
					case 1:
						$this->player->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('break_sign'));
						$this->plugin->setters[strtolower($this->player->getName())]['type'] = 'setreturnsign';
						break;
					case 2:
						$this->player->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('break_block'));
						$this->plugin->setters[strtolower($this->player->getName())]['type'] = 'setjoinpos';
						break;
					case 3:
						$this->player->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('break_block'));
						$this->plugin->setters[strtolower($this->player->getName())]['type'] = 'setleavepos';
						break;
					case 4:
						$this->player->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('break_block'));
						$this->plugin->setters[strtolower($this->player->getName())]['type'] = 'setlobbypos';
						break;
					case 5:
						$this->player->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('break_block'));
						$this->plugin->setters[strtolower($this->player->getName())]['type'] = 'setspecspawn';
						break;
					case 6:
						$this->player->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('first_corner'));
						$this->plugin->setters[strtolower($this->player->getName())]['type'] = 'setfirstcorner';
						break;
					case 7:
						$this->mainMenu();
						break;
				}
			} else {
				$this->plugin->ins[$this->arena]->setup = false;
				$this->player->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('disable_setup_mode'));
			}
		});
		$form->setTitle($this->arena . " -  Locations Setup");
		$form->addButton("Joinsign");
		$form->addButton("Returnsign");
		$form->addButton("Join Position");
		$form->addButton("Return Position");
		$form->addButton("Pre-Game Position");
		$form->addButton("Spectator Spawn Position");
		$form->addButton("Floor Corners");
		$form->addButton("Back");
		$this->player->sendForm($form);
	}

	public function showIntPlayerPage() {
		$form = new CustomForm(function (Player $player, ?array $data): void {
			if ($data !== null) {
				foreach ($data as $d) {
					if (!is_numeric($d) && $d !== null) {
						$this->player->sendMessage($this->plugin->getPrefix() . "There was an error setting up the data, please ensure all inputs are of a numeric value.");
						return;
					}
				}
				$this->config->setUpdateTime($data[0]);
				$this->config->setMaxRounds($data[2]);
				$this->config->setLobbyTime($data[4]);
				$this->config->setMinPlayers($data[6]);
				$this->config->setMaxPlayers($data[8]);
				$this->mainMenu();
				return;
			} else {
				$this->plugin->ins[$this->arena]->setup = false;
				$this->player->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('disable_setup_mode'));
			}
		});
		$form->setTitle($this->arena . " -  Intverals & Players Setup");
		$form->addInput("");
		$form->addLabel("Sign Update: Set the update rate in seconds for the sign.");
		$form->addInput("");
		$form->addLabel("Max rounds: Set the maximum rounds for this game.");
		$form->addInput("");
		$form->addLabel("Lobby Wait Time: Set the wait time inside the lobby.");
		$form->addInput("");
		$form->addLabel("Min Players: Set the minimum amount of players.");
		$form->addInput("");
		$form->addLabel("Max Players: Set the maximum amount of players.");
		$this->player->sendForm($form);
	}

	public function showFeaturesPage() {
		$form = new CustomForm(function (Player $player, ?array $data): void {
			if ($data !== null) {
				$a1 = ["classic", "furious", "stoned"];
				$a2 = ["wool", "terracotta", "glass", "concrete"];
				$a3 = ["true", "false"];

				$this->config->setType($a1[$data[0]]);
				$this->config->setMaterial($a2[$data[2]]);
				$this->config->setStatus($a3[$data[4]]);
				$this->config->setSpectator($a3[$data[6]]);
				$this->mainMenu();
				return;
			} else {
				$this->plugin->ins[$this->arena]->setup = false;
				$this->player->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('disable_setup_mode'));
			}
		});
		$form->setTitle($this->arena . " -  Features Setup");
		$form->addDropdown("", ["Classic", "Furious", "Stoned"]);
		$form->addLabel("Type: what type of game do you want set?");
		$form->addDropdown("", ["Wool", "Terracotta", "Glass", "Concrete"]);
		$form->addLabel("Materal: what block type do you want set?");
		$form->addDropdown("", ["True", "False"]);
		$form->addLabel("Status: Do you want the join sign to be a custom stats board?");
		$form->addDropdown("", ["True", "False"]);
		$form->addLabel("Spectator: Do you want spectator mode enabled?");
		$this->player->sendForm($form);
	}

	public function showRewardPage() {
		$form = new SimpleForm(function (Player $player, ?int $data): void {
			if ($data !== null) {
				switch ($data) {
					case 0:
						$this->setMoneyReward();
						break;
					case 1:
						$this->plugin->setters[strtolower($this->player->getName())]['arena'] = $this->arena;
						$this->player->sendMessage($this->plugin->getPrefix() . "Hold the item you want, then type 'set'.");
						break;
					case 2:
						$this->mainMenu();
						break;
				}
			} else {
				$this->plugin->ins[$this->arena]->setup = false;
				$this->player->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('disable_setup_mode'));
			}
		});
		$form->setTitle($this->arena . " -  Rewards Setup");
		$form->addButton("Money");
		$form->addButton("Item");
		$form->addButton("Back");
		$this->player->sendForm($form);
	}

	public function setMoneyReward() {
		$form = new CustomForm(function (Player $player, ?array $data): void {
			if ($data !== null) {
				$this->config->setEcoReward($data[0]);
				$this->showRewardPage();
				return;
			} else {
				$this->plugin->ins[$this->arena]->setup = false;
				$this->player->sendMessage($this->plugin->getPrefix().$this->plugin->getMsg('disable_setup_mode'));
			}
		});
		$form->setTitle($this->arena . " -  Money Reward");
		$form->addInput("");
		$form->addLabel("Money Reward: Set the amount of money to be rewarded (leave blank for none)");
		$this->player->sendForm($form);
	}
}