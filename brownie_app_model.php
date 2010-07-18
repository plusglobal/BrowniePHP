<?php

class BrownieAppModel extends AppModel {

	var $actsAs = array('Containable');

	function generateMenu(){
		return Configure::listObjects('model');
		/*
		$out = array();
		foreach($items as $item){

		}
		*/
	}

}

/*
if ( !function_exists('image_type_to_extension') ) {

    function image_type_to_extension ($type, $dot = true)
    {
        $e = array ( 1 => 'gif', 'jpeg', 'png', 'swf', 'psd', 'bmp',
            'tiff', 'tiff', 'jpc', 'jp2', 'jpf', 'jb2', 'swc',
            'aiff', 'wbmp', 'xbm');

        // We are expecting an integer.
        $type = (int)$type;
        if (!$type) {
            trigger_error( '...come up with an error here...', E_USER_NOTICE );
            return null;
        }

        if ( !isset($e[$type]) ) {
            trigger_error( '...come up with an error here...', E_USER_NOTICE );
            return null;
        }

        return ($dot ? '.' : '') . $e[$type];
    }

}

if ( !function_exists('image_type_to_mime_type') ) {

    function image_type_to_mime_type ($type)
    {
        $m = array ( 1 => 'image/gif', 'image/jpeg', 'image/png',
            'application/x-shockwave-flash', 'image/psd', 'image/bmp',
            'image/tiff', 'image/tiff', 'application/octet-stream',
            'image/jp2', 'application/octet-stream', 'application/octet-stream',
            'application/x-shockwave-flash', 'image/iff', 'image/vnd.wap.wbmp', 'image/xbm');

        // We are expecting an integer.
        $type = (int)$type;
        if (!$type) {
            trigger_error( '...come up with an error here...', E_USER_NOTICE );
            return null;
        }

        if ( !isset($m[$type]) ) {
            trigger_error( '...come up with an error here...', E_USER_NOTICE );
            return null;
        }

        return $m[$type];
    }

}

*/