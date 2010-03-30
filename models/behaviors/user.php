<?php

class UserBehavior extends ModelBehavior {

	function setup($Model, $config = array()) {

	}


	function beforeValidate(&$Model) {
		$Model->validate = array (

		);
	}




}