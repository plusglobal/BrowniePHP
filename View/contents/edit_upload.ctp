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
<?php
if ($uploadType == 'BrwFile') {
	$upload = $brwConfig['files'][$categoryCode];
} else {
	$upload = $brwConfig['images'][$categoryCode];
}
?>
<h1><?php echo $upload['name_category'] ?></h1>
<?php for ($i = 0; $i < $max; $i++): ?>
<fieldset>
	<?php
	echo $form->input($uploadType . '.' . $i . '.id', array('value' => $uploadId, 'type' => 'hidden'));
	echo $form->input($uploadType . '.' . $i . '.model', array('value' => $model, 'type' => 'hidden'));
	echo $form->input($uploadType . '.' . $i . '.record_id', array('value' => $recordId, 'type' => 'hidden'));
	echo $form->input($uploadType . '.' . $i . '.category_code', array('value' => $categoryCode, 'type' => 'hidden'));
	$params = array(
		'type' => 'file',
		'label' => ($uploadType == 'BrwFile')? __d('brownie', 'File', true): __d('brownie', 'Image', true)
	);
	if($uploadId) {
		$params['after'] = '<div>'
			. __d('brownie', 'You can leave the file field blank if you don\'t want to change the file', true)
			. '</div>';
	}
	echo $form->input($uploadType . '.' . $i . '.file', $params);
	if($upload['description']) {
		echo $form->input($uploadType . '.' . $i . '.description', array('label' => __d('brownie', 'Description', true)));
	}
?>
</fieldset>
<?php endfor ?>
<?php echo $form->end(__d('brownie', 'Save', true)); ?>
</div>