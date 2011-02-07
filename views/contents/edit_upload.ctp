<div class="form">
<?php
$adding = true;
echo $form->create('Content', array(
	'type' => 'file',
	'url' => array(
		'plugin' => 'brownie', 'controller' => 'contents', 'action' => 'edit_upload',
		$model, $uploadType, $recordId, $categoryCode, $uploadId
	)
));
?>
<h1><?php __d('brownie', 'Add file'); ?></h1>
<?php
if ($uploadType == 'BrwFile') {
	$upload = $brwConfig['files'][$categoryCode];
} else {
	$upload = $brwConfig['images'][$categoryCode];
}

?>

<fieldset>
	<legend><?php echo $upload['name_category'] ?></legend>
	<?php
	echo $form->input($uploadType . '.id', array('value' => $uploadId, 'type' => 'hidden'));
	echo $form->input($uploadType . '.model', array('value' => $model, 'type' => 'hidden'));
	echo $form->input($uploadType . '.record_id', array('value' => $recordId, 'type' => 'hidden'));
	echo $form->input($uploadType . '.category_code', array('value' => $categoryCode, 'type' => 'hidden'));
	$params = array(
		'type' => 'file',
		'label' => ($uploadType == 'BrwFile')? __d('brownie', 'File', true): __d('brownie', 'Image', true)
	);
	if($uploadId) {
		$params['after'] = '<div>'
			. __d('brownie', 'You can leave the file field blank if you don\'t want to change the file', true)
			. '</div>';
	}
	echo $form->input($uploadType . '.file', $params);
	if($upload['description']) {
		echo $form->input($uploadType . '.description', array('label' => __d('brownie', 'Description', true)));
	}
?>
</fieldset>
<?php echo $form->end(__d('brownie', 'Submit', true)); ?>
</div>