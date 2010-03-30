<?
function resizeImage($cType = 'resize', $id, $imgFolder, $newName = false, $newWidth=false, $newHeight=false, $quality = 95)
{
	$img = $imgFolder . $id;
	list($oldWidth, $oldHeight, $type) = getimagesize($img);
	$ext = image_type_to_extension($type, false);

	if ($cType == 'resize') {
		if ($oldWidth < $newWidth) {
			$newWidth = $oldWidth;
		}
		if ($oldHeight < $newHeight) {
			$newHeight = $oldHeight;
		}
	}

	//check to make sure that the file is writeable, if so, create destination image (temp image)
	if (is_writeable($imgFolder)) {
		if ($newName) {
			$dest = $imgFolder . $newName;
		} else {
			$dest = $imgFolder . 'tmp_' . $id;
		}
	} else {
		//if not let developer know
		$imgFolder = substr($imgFolder, 0, strlen($imgFolder) -1);
		$imgFolder = substr($imgFolder, strrpos($imgFolder, '\\') + 1, 20);
		debug("You must allow proper permissions for image processing. And the folder has to be writable.");
		debug("Run \"chmod 777 on '$imgFolder' folder\"");
		exit();
	}

	//check to make sure that something is requested, otherwise there is nothing to resize.
	//although, could create option for quality only
	if ($newWidth OR $newHeight) {
		 // check to make sure temp file doesn't exist from a mistake or system hang up.
		 // If so delete.
		if (file_exists($dest)) {
			unlink($dest);
		} else {
			switch ($cType) {
				case 'resize':
					//Maintains the aspect ratio of the image and makes sure that it fits
					//within the maxW(newWidth) and maxH(newHeight) (thus some side will be smaller)
					$widthScale = $heightScale = 2;
					if ($newWidth) {
						$widthScale = 	$newWidth / $oldWidth;
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

				case 'crop':
					//a straight centered crop
					$startY = ($oldHeight - $newHeight)/2;
					$startX = ($oldWidth - $newWidth)/2;
					$oldHeight = $newHeight;
					$applyHeight = $newHeight;
					$oldWidth = $newWidth;
					$applyWidth = $newWidth;
				break;
			}

			switch ($ext) {
				case 'gif':
					$oldImage = imagecreatefromgif($img);
				break;
				case 'png' :
					$oldImage = imagecreatefrompng($img);
				break;
				case 'jpg': case 'jpeg':
					$oldImage = imagecreatefromjpeg($img);
				break;
				default:
					return false;
				break;
			}

			//create new image
			$newImage = imagecreatetruecolor($applyWidth, $applyHeight);

			//maintain transparency to gif and png
			if ($ext=='gif' or $ext=='png') {
				imagealphablending($newImage, false);
				imagesavealpha($newImage, true);
				imagefilledrectangle($newImage, 0, 0, $applyWidth, $applyHeight, imagecolorallocatealpha($newImage, 255, 255, 255, 127));
			}

			//put old image on top of new image
			imagecopyresampled($newImage, $oldImage, 0,0 , $startX, $startY, $applyWidth, $applyHeight, $oldWidth, $oldHeight);

			//save the image to disk
			switch ($ext) {
				case 'gif':
					imagegif($newImage, $dest, $quality);
				break;
				case 'png':
					imagepng($newImage, $dest, 9);
				break;
				case 'jpg': case 'jpeg':
					imagejpeg($newImage, $dest, $quality);
				break;
				default:
					return false;
				break;
			}

			chmod($dest, 0777);

			imagedestroy($newImage);
			imagedestroy($oldImage);

			if (!$newName) {
				unlink($img);
				rename($dest, $img);
			}

			return true;
		}

	} else {
		return false;
	}

}


function is_animated_gif($filename)
{
	$filecontents = file_get_contents($filename);
	$str_loc = 0;
	$count = 0;
	while ($count < 2){ // There is no point in continuing after we find a 2nd frame
		$where1=strpos($filecontents,"\x00\x21\xF9\x04",$str_loc);
		if ($where1 === FALSE) break;
		else{
			$str_loc=$where1+1;
			$where2=strpos($filecontents,"\x00\x2C",$str_loc);
			if ($where2 === FALSE) break;
			else{
				if ($where1+8 == $where2) $count++;
				$str_loc=$where2+1;
			}
		}
	}
	if ($count > 1) return true;
	else return false;
}

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


?>
