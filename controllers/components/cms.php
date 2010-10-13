<?php

class CmsComponent extends Object{

	function initialize(&$Controller, $settings) {
		$multiSitesModel = false;
		if (!empty($settings['multiSitesModel'])) {
			$multiSitesModel = $settings['multiSitesModel'];
		}
		Configure::write('multiSitesModel', $multiSitesModel);

		ClassRegistry::init('BrwUser')->Behaviors->attach('Brownie.BrwUser');
		ClassRegistry::init('BrwImage')->Behaviors->attach('Brownie.BrwUpload');
		ClassRegistry::init('BrwFile')->Behaviors->attach('Brownie.BrwUpload');

		if ($Controller->Session->check('BrwUser')) {
			$BrwUser = $Controller->Session->read('BrwUser');
			unset($BrwUser['BrwUser']['password']);
			$Controller->set('BrwUser', $BrwUser);
		}

	}

}