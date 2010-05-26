<?php

class CmsComponent extends Object{


	function initialize(&$Controller, $settings) {
		ClassRegistry::init('BrwImage')->Behaviors->attach('Brownie.Image');
		ClassRegistry::init('BrwFile')->Behaviors->attach('Brownie.File');

		$relatedModels = array();
		foreach ($Controller->modelNames as $model) {
			$relatedModels = array_merge($relatedModels, $this->accesibleModels($Controller->{$model}));
		}
		foreach ($relatedModels as $Model) {
			$this->attachUploads($Model);
		}
		$Controller->loadModel('Brownie.BrwUser');
	}


	function attachUploads($Model) {
		if(!empty($Model->brownieCmsConfig['images'])){
			$Model->bindModel(array('hasMany' => array('BrwImage' => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwImage.model' => $Model->alias)
			))));
		}
		if(!empty($Model->brownieCmsConfig['files'])){
			$Model->bindModel(array('hasMany' => array('BrwFile' => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwFile.model' => $Model->alias)
			))));
		}
		$Model->Behaviors->attach('Brownie.Cms');
	}


	function accesibleModels($Model) {
		/*
		acá tengo que hacer un recorrido recursivo para poder attachar a todos los modelos
		por ahora retorno sólo los directos
		*/
		return $this->directRelated($Model);
	}


	function directRelated($Model) {
		$ret[] = $Model;
		$relations = array('hasMany', 'belongsTo', 'hasAndBelongsToMany', 'hasOne');
		foreach($relations as $relation) {
			if(!empty($Model->{$relation})){
				foreach($Model->{$relation} as $aliasModel => $relatedModel){
					$ret[] = $Model->$aliasModel;
				}
			}
		}
		return $ret;
	}

}
