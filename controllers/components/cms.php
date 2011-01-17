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

		if ($Controller->Session->check('Auth.BrwUser')) {
			$BrwUser = $Controller->Session->read('Auth.BrwUser');
			unset($BrwUser['BrwUser']['password']);
			$Controller->set('BrwUser', $BrwUser);
		}

		if (!empty($Controller->params['brw'])) {
			//cambiar esto por una validacion más en serio
			if (!$Controller->Session->check('Auth.BrwUser')) {
				$Controller->cakeError('error404');
			}
			$Controller->layout = 'ajax';
		}

	}

}