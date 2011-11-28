<div class="form">
<?php
$url = array(
	'plugin' => 'brownie',
	'controller' => 'contents',
	'action' => 'add_images',
	$model, $recordId, $categoryCode
);
echo $this->Form->create('Content', array('url' => $url, 'type' => 'file'));
?>
<h1><?php echo __d('brownie', 'Add image'); ?></h1>
<?php

echo $this->Form->input('model', array('value' => $model, 'type' => 'hidden'));

if (!empty($brwConfig['images'][$categoryCode])) {
		$image = $brwConfig['images'][$categoryCode];
		$i = 0;
		echo '
		<fieldset>
		<legend>' . $image['name_category'] . '</legend>';

	if ($brwConfig['images'][$categoryCode]['index']) {
		$iterations = 1;
	} else {
		$iterations = 10;
	}

	for ($i = 0; $i < $iterations; $i++) {
		echo '
		' . $this->Form->input('BrwImage.'.$i.'.id') . '
		' . $this->Form->input('BrwImage.'.$i.'.file', array('type' => 'file', 'label' => __d('brownie', 'Image'))) . '
		' . $this->Form->input('BrwImage.'.$i.'.model', array('value' => $model, 'type' => 'hidden')) . '
		' .	$this->Form->input('BrwImage.'.$i.'.category_code', array('value' => $categoryCode, 'type' => 'hidden'));

		if($image['description']) {
			echo $this->Form->input('BrwImage.'.$i.'.description', array('label' => __d('brownie', 'Image description')));
		}
		echo $this->Form->input('BrwImage.'.$i.'.record_id', array('value' => $recordId, 'type' => 'hidden'));

	}

		echo '
		</fieldset>';
}

?>


<?php echo $this->Form->end(__d('brownie', 'Submit')); ?>
</div>