<?php

class BrwBackendBehavior extends ModelBehavior {

	public $cachedBelongsTo;

	function setup($Model, $config = array()) {
		// I don't know why $Model->belongsTo is empty in BrwBackendBehavior::beforeFind(), so I have to cache it
		$this->cachedBelongsTo = $Model->belongsTo;
	}


	function beforeFind($Model, $query) {
		$authModel = AuthComponent::user('model');
		$authId = AuthComponent::user('id');
		if ($authModel and $authModel != 'BrwUser' and !empty($Model->brwConfigPerAuthUser[$authModel])) {
			if ($Model->brwConfigPerAuthUser[$authModel]['type'] == 'owned') {
				if ($Model->name == $authModel) {
					$query['conditions'][$Model->name . '.id'] = $authId;
				} elseif (!empty($this->cachedBelongsTo[$authModel])) {
					$fk = $this->cachedBelongsTo[$authModel]['foreignKey'];
					$query['conditions'][$Model->name . '.' . $fk] = $authId;
				}
			}
		}
		return $query;
	}


}