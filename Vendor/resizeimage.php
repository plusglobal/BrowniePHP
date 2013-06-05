<?php
/**
* helped by http://mediumexposure.com/smart-image-resizing-while-preserving-transparency-php-and-gd-library/
*/
function brwResizeImage($file, $sizes) {
	if (!is_file($file)) {
		return false;
	}
	list($oldWidth, $oldHeight, $type) = getimagesize($file);
	$ext = image_type_to_extension($type, false);
	switch ($ext) {
		case 'gif':
			$oldImage = imagecreatefromgif($file);
		break;
		case 'png':
			$oldImage = imagecreatefrompng($file);
		break;
		case 'jpg': case 'jpeg':
			$oldImage = imagecreatefromjpeg($file);
			if (brw_image_fix_orientation($oldImage, $file)) {
				$tmp = $oldWidth; $oldWidth = $oldHeight; $oldHeight = $tmp;
			}
		break;
		default:
			return false;
		break;
	}

	$s = explode('x', $sizes);
	if(count($s == 2) and ctype_digit($s[0]) and ctype_digit($s[1])) {
		$newWidth = $s[0];
		$newHeight = $s[1];
		$crop = 'resizeCrop';
	} else {
		$s = explode('_', $sizes);
		if (count($s == 2) and ctype_digit($s[0]) and ctype_digit($s[1])) {
			$newWidth = $s[0];
			$newHeight = $s[1];
			$crop = 'resize';
		} else {
			return false;
		}
	}

	if ($crop == 'resize') {
		if ($oldWidth < $newWidth) {
			$newWidth = $oldWidth;
		}
		if ($oldHeight < $newHeight) {
			$newHeight = $oldHeight;
		}
	}

	switch ($crop) {
		case 'resize':
			//Maintain the aspect ratio of the image and makes sure that it fits
			//within the maxW(newWidth) and maxH(newHeight) (thus some side will be smaller)
			$widthScale = $heightScale = 2;
			if ($newWidth) {
				$widthScale = $newWidth / $oldWidth;
			}
			if ($newHeight) {
				$heightScale = $newHeight / $oldHeight;
			}
			if ($widthScale < $heightScale) {
				$maxWidth = $newWidth;
				$maxHeight = false;
			} elseif ($widthScale > $heightScale ) {
				$maxHeight = $newHeight;
				$maxWidth = false;
			} else {
				$maxHeight = $newHeight;
				$maxWidth = $newWidth;
			}
			if ($maxWidth > $maxHeight) {
				$applyWidth = $maxWidth;
				$applyHeight = ($oldHeight*$applyWidth)/$oldWidth;
			} elseif ($maxHeight > $maxWidth) {
				$applyHeight = $maxHeight;
				$applyWidth = ($applyHeight*$oldWidth)/$oldHeight;
			} else {
				$applyWidth = $maxWidth;
				$applyHeight = $maxHeight;
			}
			$startX = $startY = 0;
		break;

		case 'resizeCrop':
			//resize to max, then crop to center
			$ratioX = $newWidth / $oldWidth;
			$ratioY = $newHeight / $oldHeight;
			if ($ratioX < $ratioY) {
				$startX = round(($oldWidth - ($newWidth / $ratioY))/2);
				$startY = 0;
				$oldWidth = round($newWidth / $ratioY);
				$oldHeight = $oldHeight;
			} else {
				$startX = 0;
				$startY = round(($oldHeight - ($newHeight / $ratioX))/2);
				$oldWidth = $oldWidth;
				$oldHeight = round($newHeight / $ratioX);
			}
			$applyWidth = $newWidth;
			$applyHeight = $newHeight;
		break;
	}

	//create new image
	if($ext == 'gif') {
		$newImage = imagecreate($applyWidth, $applyHeight);
		imagetruecolortopalette($newImage, true, imagecolorstotal($oldImage));
	} else {
		$newImage = imagecreatetruecolor($applyWidth, $applyHeight);
	}

	//maintain transparency to gif and png
	if ($ext == 'gif' and $trnprt_indx = imagecolortransparent($oldImage) and $trnprt_indx != -1) {
		$trnprt_color = imagecolorsforindex($oldImage, $trnprt_indx); // Get the original image's transparent color's RGB values
		$trnprt_indx = imagecolorallocate($newImage, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']); // Allocate the same color in the new image resource
		imagefill($newImage, 0, 0, $trnprt_indx); // Completely fill the background of the new image with allocated color.
		imagecolortransparent($newImage, $trnprt_indx); // Set the background color for new image to transparent
	} elseif ($ext == 'png') {
		imagealphablending($newImage, false);
		$color = imagecolorallocatealpha($newImage, 0, 0, 0, 127); // Create a new transparent color for image
		imagefill($newImage, 0, 0, $color); // Completely fill the background of the new image with allocated color.
		imagesavealpha($newImage, true); // Restore transparency blending
	}

	//put old image on top of new image
	imagecopyresampled($newImage, $oldImage, 0, 0, $startX, $startY, $applyWidth, $applyHeight, $oldWidth, $oldHeight);

	//save the image to disk
	$quality = Configure::read('brwSettings.defaultImageQuality');
	$saved = false;
	switch ($ext) {
		case 'gif':
			$saved = imagegif($newImage, $file, $quality);
		break;
		case 'png':
			$saved = imagepng($newImage, $file, 9);
		break;
		case 'jpg': case 'jpeg':
			$saved = imagejpeg($newImage, $file, $quality);
		break;
		default:
			return false;
		break;
	}

	imagedestroy($newImage);
	imagedestroy($oldImage);

	return $saved;
}


function is_animated_gif($filename) {
	$filecontents = file_get_contents($filename);
	$str_loc = $count = 0;
	// There is no point in continuing after we find a 2nd frame
	while ($count < 2) {
		$where1 = strpos($filecontents, "\x00\x21\xF9\x04", $str_loc);
		if ($where1 === FALSE) {
			break;
		} else {
			$str_loc = $where1 + 1;
			$where2 = strpos($filecontents, "\x00\x2C", $str_loc);
			if ($where2 === FALSE) {
				break;
			} else {
				if ($where1 + 8 == $where2) {
					$count++;
				}
				$str_loc = $where2 + 1;
			}
		}
	}
	if ($count > 1) {
		return true;
	} else {
		return false;
	}

}

if ( !function_exists('image_type_to_extension') ) {

    function image_type_to_extension ($type, $dot = true) {
        $e = array ( 1 => 'gif', 'jpeg', 'png', 'swf', 'psd', 'bmp',
            'tiff', 'tiff', 'jpc', 'jp2', 'jpf', 'jb2', 'swc', 'aiff', 'wbmp', 'xbm');

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

function brw_image_fix_orientation(&$image, $filename) {
	$exif = exif_read_data($filename);
	if (!empty($exif['Orientation'])) {
		switch ($exif['Orientation']) {
			case 3: $image = imagerotate($image, 180, 0); break;
			case 6: $image = imagerotate($image, -90, 0); break;
			case 8: $image = imagerotate($image, 90, 0); break;
		}
		if (in_array($exif['Orientation'], array(6, 8))) {
			return true;
		}
	}
	return false;
}