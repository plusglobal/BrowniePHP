<?php
App::uses('FormAuthenticate', 'Controller/Component/Auth');

class BrwAuthenticate extends FormAuthenticate {


	public function authenticate(CakeRequest $request, CakeResponse $response) {

		foreach (Configure::read('brwSettings.userModels') as $userModel) {
			$this->settings['userModel'] = $userModel;
			$request->data[$userModel] = $request->data['BrwUser'];
	        $authenticated = parent::authenticate($request, $response);
	        if ($authenticated) {
	        	ClassRegistry::init($userModel)->updateLastLogin($authenticated['id']);
				return array_merge($authenticated, array('model' => $userModel));
			}
		}
		$newUser = ClassRegistry::init('BrwUser')->checkAndCreate(
			$request->data['BrwUser']['email'],
			$request->data['BrwUser']['password']
		);
		if ($newUser) {
			return array_merge($newUser, array('model' => 'BrwUser'));
		}
		return false;
    }

}