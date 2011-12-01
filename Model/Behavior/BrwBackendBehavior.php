<?php

class BrwBackendBehavior extends ModelBehavior {


	function setup($Model, $config = array()) {}


	function beforeFind($Model, $query) {
		$authModel = AuthComponent::user('model');
		$authId = AuthComponent::user('id');
		if ($authModel and $authModel != 'BrwUser' and !empty($Model->brwConfigPerAuthUser[$authModel])) {
			if ($Model->brwConfigPerAuthUser[$authModel]['type'] == 'owned') {
				if ($Model->name == $authModel) {
					$query['conditions'][$Model->name . '.id'] = $authId;
				} elseif (!empty($Model->belongsTo[$authModel])) {
					$fk = $Model->belongsTo[$authModel]['foreignKey'];
					$query['conditions'][$Model->name . '.' . $fk] = $authId;
				}
			}
		}
		return $query;
	}


}