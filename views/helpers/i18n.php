<?php

class i18nHelper extends Helper {

	function humanize($lang) {
		switch ($lang) {
			case 'eng': $out = __d('brownie', 'English', true); break;
			case 'spa': $out = __d('brownie', 'Spanish', true); break;
			case 'ita': $out = __d('brownie', 'Italian', true); break;
			default: $out = $lang; break;
		}
		return $out;
	}

}