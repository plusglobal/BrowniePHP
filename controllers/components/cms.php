<?php

class CmsComponent extends Object{

	function initialize(&$Controller, $settings) {
		ClassRegistry::init('BrwImage')->Behaviors->attach('Brownie.BrwImage');
		ClassRegistry::init('BrwFile')->Behaviors->attach('Brownie.File');

		$this->attachAllUploads();

		if ($Controller->Session->check('BrwUser')) {
			$BrwUser = $Controller->Session->read('BrwUser');
			unset($BrwUser['BrwUser']['password']);
			$Controller->set('BrwUser', $BrwUser);
		}

	}

	function attachAllUploads() {
		$models = App::objects('model');
		foreach ($models as $model) {
			if ($Model = ClassRegistry::getObject($model)) {
				$this->attachUploads($Model);
			}
		}
	}

}
