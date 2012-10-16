<?php

class BrwSanitize {

	static function html($str) {
		if (!is_string($str)) {
			return $str;
		}
		return str_replace(array('<', '>'), array('&lt;', '&gt;'), $str);
	}


	public function url($string) {
		$http = strtolower(substr($string, 0, 7));
		$https = strtolower(substr($string, 0, 8));
		if ($http != 'http://' and  $https != 'https://') {
			return 'http://' . $string;
		} else {
			return $string;
		}
	}


}