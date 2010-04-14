<div class="form">
<?php
$url = array(
	'plugin' => 'brownie',
	'controller' => 'contents',
	'action' => 'add_images',
	$model, $recordId, $categoryCode
);
echo $form->create('Content', array('url' => $url, 'type' => 'file'));
?>
<h1><?php __d('brownie', 'Add image'); ?></h1>
<?php
$session->flash();

echo $form->input('model', array('value' => $model, 'type' => 'hidden'));

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
		' . $form->input('BrwImage.'.$i.'.id') . '
		' . $form->input('BrwImage.'.$i.'.file', array('type' => 'file', 'label' => __d('brownie', 'Image', true))) . '
		' . $form->input('BrwImage.'.$i.'.model', array('value' => $model, 'type' => 'hidden')) . '
		' .	$form->input('BrwImage.'.$i.'.category_code', array('value' => $categoryCode, 'type' => 'hidden'));

		if($image['description']) {
			echo $form->input('BrwImage.'.$i.'.description', array('label' => __d('brownie', 'Image description', true)));
		}
		echo $form->input('BrwImage.'.$i.'.record_id', array('value' => $recordId, 'type' => 'hidden'));

	}

		echo '
		</fieldset>';
}

?>


<?php echo $form->end(__d('brownie', 'Submit', true)); ?>
</div>