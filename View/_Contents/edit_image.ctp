<div class="form">
<?php
$adding = true;
echo $this->Form->create('Content', array('type' => 'file', 'action' => 'edit_image', 'plugin' => 'brownie'));
?>
<h1><?php echo __d('brownie', 'Add image'); ?></h1>
<?php
echo $this->Form->input('model', array('value' => $model, 'type' => 'hidden'));

if(!empty($brwConfig['images'][$categoryCode])){
		$image = $brwConfig['images'][$categoryCode];
		$i = 0;
		echo '
		<fieldset>
		<legend>' . $image['name_category'] . '</legend>';

		echo '
		' . $this->Form->input('BrwImage.id', array('value' => $imageId)) . '
		' . $this->Form->input('BrwImage.file', array('type' => 'file', 'label' => __d('brownie', 'Image')));
		if($imageId){
			echo '<div>'.__d('brownie', 'You can leave the Image field blank if you don\'t want to change the image').'</div>';
		}
		echo '
		' . $this->Form->input('BrwImage.model', array('value' => $model, 'type' => 'hidden')) . '
		' .	$this->Form->input('BrwImage.category_code', array('value' => $categoryCode, 'type' => 'hidden'));

		if($image['description']) {
			echo $this->Form->input('BrwImage.description', array('label' => __d('brownie', 'Image description')));
		}
		echo $this->Form->input('BrwImage.record_id', array('value' => $recordId, 'type' => 'hidden'));

		echo '
		</fieldset>';
}

?>


<?php echo $this->Form->end(__d('brownie', 'Submit')); ?>
</div>