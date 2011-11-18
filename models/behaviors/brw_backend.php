<?php

class BrwBackendBehavior extends ModelBehavior {


	function setup($Model, $config = array()) {}


	function beforeFind($Model, $query) {
		$authModel = Configure::read('brwSettings.authModel');
		if ($authModel and $authModel != 'BrwUser' and !empty($Model->brwConfigPerAuthUser[$authModel])) {
			if ($Model->brwConfigPerAuthUser[$authModel]['type'] == 'owned') {
				if ($Model->name == $authModel) {
					$query['conditions'][$Model->name . '.id'] = Configure::read('brwSettings.authUser.id');
				} elseif (!empty($Model->belongsTo[$authModel])) {
					$fk = $Model->belongsTo[$authModel]['foreignKey'];
					$query['conditions'][$Model->name . '.' . $fk] = Configure::read('brwSettings.authUser.id');
				}
			}
		}
		return $query;
	}


}