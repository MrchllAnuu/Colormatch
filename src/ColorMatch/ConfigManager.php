<?php

namespace ColorMatch;

use pocketmine\utils\Config;

class ConfigManager{

	private $arena;

	public function __construct($id, ColorMatch $plugin) {
		$this->arena = new Config($plugin->getDataFolder()."arenas/$id.yml", Config::YAML);
	}

	public function setType($type) {
		$this->arena->set('type', $type);
		$this->arena->save();
	}
	public function setMaterial($type) {
		$this->arena->set('material', $type);
		$this->arena->save();
	}
	public function setJoinSign($x, $y, $z, $level) {
		$this->arena->setNested('signs.join_sign_x', $x);
		$this->arena->setNested('signs.join_sign_y', $y);
		$this->arena->setNested('signs.join_sign_z', $z);
		$this->arena->setNested('signs.join_sign_world', $level);
		$this->arena->save();
	}
	public function setStatus($type) {
		$this->arena->setNested('signs.enable_status', $type);
		$this->arena->save();
	}
	public function setStatusLine($line, $type) {
		$this->arena->setNested("signs.status_line_$line", $type);
		$this->arena->save();
	}
	public function setUpdateTime($type) {
		$this->arena->setNested('signs.sign_update_time', $type);
		$this->arena->save();
	}
	public function setReturnSign($x, $y, $z) {
		$this->arena->setNested('signs.return_sign_x', $x);
		$this->arena->setNested('signs.return_sign_y', $y);
		$this->arena->setNested('signs.return_sign_z', $z);
		$this->arena->save();
	}
	public function setArenaWorld($type) {
		$this->arena->setNested('arena.arena_world', $type);
		$this->arena->save();
	}
	public function setEcoReward($data) {
		$this->arena->setNested('arena.money_reward', $data);
		$this->arena->save();
	}
	public function setJoinPos($x, $y, $z) {
		$this->arena->setNested('arena.join_position_x', $x);
		$this->arena->setNested('arena.join_position_y', $y);
		$this->arena->setNested('arena.join_position_z', $z);
		$this->arena->save();
	}
	public function setLobbyPos($x, $y, $z) {
		$this->arena->setNested('arena.lobby_position_x', $x);
		$this->arena->setNested('arena.lobby_position_y', $y);
		$this->arena->setNested('arena.lobby_position_z', $z);
		$this->arena->save();
	}
	public function setFirstCorner($x, $y, $z) {
		$this->arena->setNested('arena.first_corner_x', $x);
		$this->arena->setNested('arena.floor_y', $y);
		$this->arena->setNested('arena.first_corner_z', $z);
		$this->arena->save();
	}
	public function setSecondCorner($x, $z) {
		$this->arena->setNested('arena.second_corner_x', $x);
		$this->arena->setNested('arena.second_corner_z', $z);
		$this->arena->save();
	}
	public function setSpectator($data) {
		$this->arena->setNested('arena.spectator_mode', $data);
		$this->arena->save();
	}
	public function setSpecSpawn($x, $y, $z) {
		$this->arena->setNested('arena.spec_spawn_x', $x);
		$this->arena->setNested('arena.spec_spawn_y', $y);
		$this->arena->setNested('arena.spec_spawn_z', $z);
		$this->arena->save();
	}
	public function setLeavePos($x, $y, $z, $level) {
		$this->arena->setNested('arena.leave_position_x', $x);
		$this->arena->setNested('arena.leave_position_y', $y);
		$this->arena->setNested('arena.leave_position_z', $z);
		$this->arena->setNested('arena.leave_position_world', $level);
		$this->arena->save();
	}
	public function setMaxRounds($data) {
		$this->arena->setNested('arena.max_rounds', $data);
		$this->arena->save();
	}
	public function setMaxPlayers($data) {
		$this->arena->setNested('arena.max_players', $data);
		$this->arena->save();
	}
	public function setMinPlayers($data) {
		$this->arena->setNested('arena.min_players', $data);
		$this->arena->save();
	}
	public function setLobbyTime($data) {
		$this->arena->setNested('arena.lobby_time', $data);
		$this->arena->save();
	}
	public function setToggle($data) {
		$this->arena->set('enabled', $data);
		$this->arena->save();
	}
	public function setItemReward($data) {
		$this->arena->setNested('arena.item_reward', $data);
		$this->arena->save();
	}
}