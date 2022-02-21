<?php

namespace ColorMatch\Utils;

class GetFormattingColor {
	
	public function get($currentColor) {
		switch($currentColor) {
			case 0:
				return "§f§lWHITE - ";
			case 1:
				return "§6§lORANGE - ";
			case 2:
				return "§d§lMAGENTA - ";
			case 3:
				return "§b§lLIGHT BLUE - ";
			case 4:
				return "§e§lYELLOW - ";
			case 5:
				return "§a§lLIME - ";
			case 6:
				return "§d§lPINK - ";
			case 7:
				return "§8§lGRAY - ";
			case 8:
				return "§7§lLIGHT GRAY - ";
			case 9:
				return "§3§lCYAN - ";
			case 10:
				return "§5§lPURPLE - ";
			case 11:
				return "§1§lBLUE - ";
			case 12:
				return "§g§lBROWN - ";
			case 13:
				return "§2§lGREEN - ";
			case 14:
				return "§4§lRED - ";
			case 15:
				return "§0§lBLACK - ";
		}
		return "";
	}
}