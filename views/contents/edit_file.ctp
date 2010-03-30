<div class="form">
<?php
$adding = true;
echo $form->create('Content', array('type' => 'file', 'action' => 'edit_file', 'plugin' => 'brownie'));
?>
<h1><?php __d('brownie', 'Add file'); ?></h1>
<?php
$session->flash();

echo $form->input('model', array('value' => $model, 'type' => 'hidden'));

if(!empty($brwConfig['files'][$categoryCode])){
		$file = $brwConfig['files'][$categoryCode];
		$i = 0;
		echo '
		<fieldset>
		<legend>' . $file['name_category'] . '</legend>';

		echo '
		' . $form->input('BrwFile.id', array('value' => $fileId)) . '
		' . $form->input('BrwFile.file', array('type' => 'file', 'label' => __d('brownie', 'File', true)));
		if($fileId){
			echo '
			<div>' .
			__d('brownie', 'You can leave the file field blank if you don\'t want to change the file', true)
			. '</div>';
		}
		echo '
		' . $form->input('BrwFile.model', array('value' => $model, 'type' => 'hidden')) . '
		' .	$form->input('BrwFile.category_code', array('value' => $categoryCode, 'type' => 'hidden'));

		if($file['description']) {
			echo $form->input('BrwFile.description', array('label' => __d('brownie', 'File description', true)));
		}
		echo $form->input('BrwFile.record_id', array('value' => $recordId, 'type' => 'hidden'));

		echo '
		</fieldset>';
}

?>


<?php echo $form->end(__d('brownie', 'Submit', true)); ?>
</div>