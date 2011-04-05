<?php

class PanelComponent extends Object{

	function initialize(&$Controller, $settings = array()) {
		$defaultSettings = array(
			'multiSitesModel' => false,
			'css' => array(
				'/brownie/css/brownie',
				'/brownie/css/fancybox/jquery.fancybox-1.3.1',
			),
			'js' => array(
				'/brownie/js/jquery-1.3.2.min',
				'/brownie/js/jquery.fancybox-1.3.1.pack',
				'/brownie/js/jquery.selso',
				'/brownie/js/jquery.comboselect',
				'/brownie/js/brownie',
			),
			'customHome' => false,
		);
		if (file_exists(WWW_ROOT . 'css' . DS . 'brownie.css')) {
			$defaultSettings['css'][] = 'brownie';
		}
		if (file_exists(WWW_ROOT . 'js' . DS . 'brownie.js')) {
			$defaultSettings['js'][] = 'brownie';
		}
		if (!empty($settings['js']) and !is_array($settings['js'])) {
			$settings['js'] = (array)$settings['js'];
		}
		if (!empty($settings['css']) and !is_array($settings['css'])) {
			$settings['css'] = (array)$settings['css'];
		}

		if (file_exists(WWW_ROOT . 'js' . DS . 'tiny_mce' . DS . 'jquery.tinymce.js')) {
			$settings['js'][] = 'tiny_mce/jquery.tinymce';
		} elseif (file_exists(WWW_ROOT . 'js' . DS . 'fckeditor' . DS . 'fckeditor.js')) {
			$settings['js'][] = 'fckeditor/fckeditor';
		}
		$settings = Set::merge($defaultSettings, $settings);

		Configure::write('multiSitesModel', $settings['multiSitesModel']);
		Configure::write('brwSettings', $settings);


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