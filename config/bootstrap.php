<?php

$defaultSettings = array(
	'css' => array(
		'/brownie/css/brownie',
		'/brownie/css/fancybox/jquery.fancybox-1.3.1',
	),
	'js' => array(
		'/brownie/js/jquery-1.3.2.min',
		'/brownie/js/jquery.fancybox-1.3.1.pack',
		'/brownie/js/jquery.selso',
		'/brownie/js/jquery.comboselect',
		'/brownie/js/jquery.jDoubleSelect',
		'/brownie/js/brownie',
	),
	'customHome' => false,
	'userModels' => array('BrwUser'),
	'uploadsPath' => './uploads',
	'dateFormat' => 'Y-m-d',
	'datetimeFormat' => 'Y-m-d h:i:s',
	'defaultExportType' => 'csv',
	'companyName' => __d('brownie', 'Control panel'),
);
if (file_exists(WWW_ROOT . 'css' . DS . 'brownie.css')) {
	$defaultSettings['css'][] = 'brownie';
}
if (file_exists(WWW_ROOT . 'js' . DS . 'brownie.js')) {
	$defaultSettings['js'][] = 'brownie';
}
if (file_exists(WWW_ROOT . 'js' . DS . 'tiny_mce' . DS . 'jquery.tinymce.js')) {
	$defaultSettings['js'][] = 'tiny_mce/jquery.tinymce';
} elseif (file_exists(WWW_ROOT . 'js' . DS . 'fckeditor' . DS . 'fckeditor.js')) {
	$defaultSettings['js'][] = 'fckeditor/fckeditor';
} elseif (file_exists(WWW_ROOT . 'js' . DS . 'ckeditor' . DS . 'ckeditor.js')) {
	$defaultSettings['js'][] = 'ckeditor/ckeditor';
}

Configure::write('brwSettings', Set::merge($defaultSettings, (array)Configure::read('brwSettings')));

