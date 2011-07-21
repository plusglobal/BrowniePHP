<?php

class BrwSanitize {

	static function html($str) {
		if (!is_string($str)) {
			return $str;
		}
		return htmlspecialchars($str, ENT_NOQUOTES, Configure::read('App.encoding'));
	}

	function url($string) {
		if (!strtolower(substr($string, 0, 6)) != 'http://' and strtolower(substr($string, 0, 7)) != 'https://') {
			return 'http://' . $string;
		} else {
			return $string;
		}
	}

}