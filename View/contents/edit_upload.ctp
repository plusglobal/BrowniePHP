<div class="form">
<?php
$adding = true;
echo $this->Form->create('Content', array(
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
	echo $this->Form->input($uploadType . '.' . $i . '.id', array('value' => $uploadId, 'type' => 'hidden'));
	echo $this->Form->input($uploadType . '.' . $i . '.model', array('value' => $model, 'type' => 'hidden'));
	echo $this->Form->input($uploadType . '.' . $i . '.record_id', array('value' => $recordId, 'type' => 'hidden'));
	echo $this->Form->input($uploadType . '.' . $i . '.category_code', array('value' => $categoryCode, 'type' => 'hidden'));
	$params = array(
		'type' => 'file',
		'label' => ($uploadType == 'BrwFile')? __d('brownie', 'File'): __d('brownie', 'Image')
	);
	if($uploadId) {
		$params['after'] = '<div>'
			. __d('brownie', 'You can leave the file field blank if you don\'t want to change the file')
			. '</div>';
	}
	echo $this->Form->input($uploadType . '.' . $i . '.file', $params);
	if($upload['description']) {
		echo $this->Form->input($uploadType . '.' . $i . '.description', array('label' => __d('brownie', 'Description')));
	}
?>
</fieldset>
<?php endfor ?>
<?php echo $this->Form->end(__d('brownie', 'Save')); ?>
</div>