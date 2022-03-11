<?php

namespace ColorMatch\Utils;

class GetFormattingColor {
	
	public function get($currentColor) {
		switch($currentColor) {
			case 0:
				return "§f§l";
			case 1:
				return "§6§l";
			case 2:
			case 6:
				return "§d§l";
			case 3:
				return "§b§l";
			case 4:
				return "§e§l";
			case 5:
				return "§a§l";
			case 7:
				return "§8§l";
			case 8:
				return "§7§l";
			case 9:
				return "§3§l";
			case 10:
				return "§5§l";
			case 11:
				return "§1§l";
			case 12:
				return "§g§l";
			case 13:
				return "§2§l";
			case 14:
				return "§4§l";
			case 15:
				return "§0§l";
		}
		return "";
	}
}