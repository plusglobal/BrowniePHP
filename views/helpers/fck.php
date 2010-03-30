<?php
class FckHelper extends Helper
{
	function load($id, $toolbar = 'Default') {
		$did = '';
		foreach (explode('.', $id) as $v) {
	 		$did .= ucfirst($v);
		}

		echo '
<script type="text/javascript">
fckLoader_'.$did.' = function () {
	var bFCKeditor_'.$did.' = new FCKeditor("'.$did.'");
	bFCKeditor_'.$did.'.BasePath = "'.$this->url('/js/fckeditor/').'";
	bFCKeditor_'.$did.'.ToolbarSet = "'.$toolbar.'";
	bFCKeditor_'.$did.'.ReplaceTextarea();
}
fckLoader_'.$did.'();
</script>';
	}
}
?>