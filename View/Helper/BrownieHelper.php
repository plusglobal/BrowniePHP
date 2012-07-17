<?php

class BrownieHelper extends AppHelper {

	public function picture($picSizes, $index = 0) {
		$i = 0;
		foreach ($picSizes as $key => $value) {
			if ($index == $i) {
				return $picSizes[$key];
			}
			$i++;
		}
		return $picSizes;
	}

}

