<?php

class BrwSanitize {

	static function html($str) {
		return htmlspecialchars($str, ENT_NOQUOTES, Configure::read('App.encoding'));
	}

}