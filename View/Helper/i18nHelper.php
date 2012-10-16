<?php

class i18nHelper extends Helper {

	public function humanize($lang) {
		$out = $lang;
		switch ($lang) {
			case 'cat': $out = __d('brownie', 'Catalá'); break;
			case 'por': $out = __d('brownie', 'Portugues'); break;
			case 'deu': $out = __d('brownie', 'German'); break;
			case 'eng': $out = __d('brownie', 'English'); break;
			case 'fre': $out = __d('brownie', 'French'); break;
			case 'ita': $out = __d('brownie', 'Italian'); break;
			case 'dut': $out = __d('brownie', 'Dutch'); break;
			case 'spa': $out = __d('brownie', 'Spanish'); break;
		}
		return $out;
	}

}