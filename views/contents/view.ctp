<div class="view <?php echo $model;?>">
	<div class="clearfix">
	<h1><?php  __d('brownie', $brwConfig['names']['singular']);?></h1>
	<div class="actions-view">
		<ul class="actions neighbors">
			<?php

			if (!empty($neighbors['prev'])) {
				echo '
				<li class="prev">
					' . $html->link(__d('brownie', 'Previous', true),
					array('action' => 'view', $model, $neighbors['prev'][$model]['id']),
					array('title' => __d('brownie', 'Previous', true))).'
				</li>';
			}
			if (!empty($neighbors['next'])) {
				echo '
				<li class="next">
					' . $html->link(__d('brownie', 'Next', true),
					array('action' => 'view', $model, $neighbors['next'][$model]['id']),
					array('title' => __d('brownie', 'Next', true))).'
				</li>';
			}
			?>
			<?php // echo $html->link(__d('brownie', 'List', true), array('action'=>'index', $model)); ?>
		</ul>
		<?php echo $this->element('actions', array('record' => $record, 'calledFrom' => 'view', 'inView' => true)) ?>
	</div>

	<table class="view">
	<?php
	$i=0;
	foreach ($record[$model] as $field_name => $field_value) {
		if (!empty($schema[$field_name])) {
			$class = ife(($i++ % 2 != 0), 'altrow', '');
			echo '
			<tr class="'.$class.'">
				<td class="label">' . __($brwConfig['fields']['names'][$field_name], true) . '</td>
				<td class="fcktxt">' . ife(!empty($field_value), $field_value, '&nbsp;') . '</td>
			</tr>';
		}
	}
	?>

	</table>
</div>

<?php
$uploadModels = array('images' => 'BrwImage', 'files' => 'BrwFile');
foreach ($uploadModels as $uploadKey => $uploadModel): ?>
<div class="brw-<?php echo $uploadKey ?> index">
	<?php foreach ($brwConfig[$uploadKey] as $catCode => $fileCat): ?>
	<div class="<?php echo $uploadKey . '-' . $catCode ?>">
		<h2><?php echo $fileCat['name_category'] ?></h2>
		<?php
		$canAdd = $permissions[$model]['edit'];
		if ($fileCat['index'] and !empty($record[$uploadModel][$catCode])) {
			$canAdd = false;
		}
		if ($canAdd) {
			echo '<div class="actions ' . $uploadKey . '-actions"><ul class="actions"><li class="add-' . $uploadKey . '">
			' . $html->link(__d('brownie', 'Add', true), array(
				'plugin' => 'brownie', 'controller' => 'contents', 'action' => 'edit_upload',
				$model, $uploadModel, $record[$model]['id'], $catCode
			)) . '</li></ul></div>';
		}
		?>
		<div class="<?php echo $uploadKey ?>-gallery clearfix">
		<?php
		if (!empty($record[$uploadModel][$catCode])) {
			if ($fileCat['index']) {
				if ($uploadKey == 'files') {
					echo $this->element('file', array('file' => $record[$uploadModel][$catCode]));
				} else {
					echo $this->element('image', array('image' => $record[$uploadModel][$catCode]));
				}
			} else {
				foreach ($record[$uploadModel][$catCode] as $upload) {
					if ($uploadKey == 'files') {
						echo $this->element('file', array('file' => $upload));
					} else {
						echo $this->element('image', array('image' => $upload));
					}
				}
			}
		}
		?>
		</div>
	</div>
	<?php endforeach ?>
</div>
<?php endforeach ?>

<?php
foreach ($assoc_models as $key => $assoc) {
	$assoc['calledFrom'] = 'parent';
	echo $this->element('index', $assoc);
}
?>

