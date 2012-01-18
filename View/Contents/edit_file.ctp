<div class="form">
<?php
$adding = true;
echo $this->Form->create('Content', array('type' => 'file', 'action' => 'edit_file', 'plugin' => 'brownie'));
?>
<h1><?php echo __d('brownie', 'Add file'); ?></h1>
<?php
echo $this->Form->input('model', array('value' => $model, 'type' => 'hidden'));

if(!empty($brwConfig['files'][$categoryCode])){
		$file = $brwConfig['files'][$categoryCode];
		$i = 0;
		echo '
		<fieldset>
		<legend>' . $file['name_category'] . '</legend>';

		echo '
		' . $this->Form->input('BrwFile.id', array('value' => $fileId)) . '
		' . $this->Form->input('BrwFile.file', array('type' => 'file', 'label' => __d('brownie', 'File')));
		if($fileId){
			echo '
			<div>' .
			__d('brownie', 'You can leave the file field blank if you don\'t want to change the file')
			. '</div>';
		}
		echo '
		' . $this->Form->input('BrwFile.model', array('value' => $model, 'type' => 'hidden')) . '
		' .	$this->Form->input('BrwFile.category_code', array('value' => $categoryCode, 'type' => 'hidden'));

		if($file['description']) {
			echo $this->Form->input('BrwFile.description', array('label' => __d('brownie', 'File description')));
		}
		echo $this->Form->input('BrwFile.record_id', array('value' => $recordId, 'type' => 'hidden'));

		echo '
		</fieldset>';
}

?>


<?php echo $this->Form->end(__d('brownie', 'Submit')); ?>
</div>