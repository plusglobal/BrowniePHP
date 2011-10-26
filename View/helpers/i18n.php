<?php

class i18nHelper extends Helper {

	function humanize($lang) {
		$out = $lang;
		switch ($lang) {
			case 'deu': $out = __d('brownie', 'German', true); break;
			case 'eng': $out = __d('brownie', 'English', true); break;
			case 'fre': $out = __d('brownie', 'French', true); break;
			case 'ita': $out = __d('brownie', 'Italian', true); break;
			case 'dut': $out = __d('brownie', 'Dutch', true); break;
			case 'spa': $out = __d('brownie', 'Spanish', true); break;
		}
		return $out;
	}

}