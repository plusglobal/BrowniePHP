<?php

class BrwBackendBehavior extends ModelBehavior {

	function setup($Model, $config = array()) {}


	function beforeFind($Model, $query) {
		$authModel = Configure::read('brwSettings.authModel');
		if ($authModel and $authModel != 'BrwUser' and !empty($Model->brwConfigPerAuthUser[$authModel])) {
			if ($Model->brwConfigPerAuthUser[$authModel]['type'] == 'owned') {
				$fk = $Model->belongsTo[$authModel]['foreignKey'];
				$query['conditions'][$Model->name . '.' . $fk] = Configure::read('brwSettings.authUser.id');
			}
		}
		return $query;
	}

}