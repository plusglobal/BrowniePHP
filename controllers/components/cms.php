<?php

class CmsComponent extends Object{

	function initialize(&$Controller, $settings) {
		$multiSitesModel = false;
		if (!empty($settings['multiSitesModel'])) {
			$multiSitesModel = $settings['multiSitesModel'];
		}
		Configure::write('multiSitesModel', $multiSitesModel);

		ClassRegistry::init('BrwUser')->Behaviors->attach('Brownie.BrwUser');
		//ClassRegistry::init('BrwGroup')->Behaviors->attach('Brownie.BrwGroup');

		$this->_attachAllUploads();

		if ($Controller->Session->check('BrwUser')) {
			$BrwUser = $Controller->Session->read('BrwUser');
			unset($BrwUser['BrwUser']['password']);
			$Controller->set('BrwUser', $BrwUser);
		}

	}

	function _attachAllUploads() {
		ClassRegistry::init('BrwImage')->Behaviors->attach('Brownie.BrwImage');
		ClassRegistry::init('BrwFile')->Behaviors->attach('Brownie.File');
		$models = App::objects('model');
		foreach ($models as $model) {
			if ($Model = ClassRegistry::getObject($model)) {
				$this->_attachUploads($Model);
			}
		}
	}


	function _attachUploads($Model) {
		if(!empty($Model->brownieCmsConfig['images'])){
			$Model->bindModel(array('hasMany' => array('BrwImage' => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwImage.model' => $Model->name)
			))));
		}
		if(!empty($Model->brownieCmsConfig['files'])){
			$Model->bindModel(array('hasMany' => array('BrwFile' => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwFile.model' => $Model->name)
			))));
		}
		$Model->Behaviors->attach('Brownie.Cms');
	}

}