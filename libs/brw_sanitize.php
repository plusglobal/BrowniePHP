<?php

class BrwSanitize {

	static function html($str) {
		if (!is_string($str)) {
			return $str;
		}
		return htmlspecialchars($str, ENT_NOQUOTES, Configure::read('App.encoding'));
	}

}