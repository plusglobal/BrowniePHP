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


	function attachUploads($Model) {
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