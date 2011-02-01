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
		<?php echo $this->element('actions', array('record' => $record, 'inView' => true)) ?>
	</div>

	<table class="view">
	<?php
	$i=0;
	foreach ($record[$model] as $field_name => $field_value) {
		if (!empty($schema[$field_name])) {
			$class = ife(($i++ % 2 != 0), 'altrow', '');
			echo '
			<tr class="'.$class.'">
				<td class="label">' . __(Inflector::humanize(str_replace('_id', '', $field_name)), true) . '</td>
				<td class="fcktxt">' . ife(!empty($field_value), $field_value, '&nbsp;') . '</td>
			</tr>';
		}
	}
	?>

	</table>
</div>
<div class="brw-images index">
<?php
if (!empty($brwConfig['images'])) {
	foreach ($brwConfig['images'] as $catCode => $imgCat) {
		echo '<h2>' . $imgCat['name_category'] . '</h2>';
		if (!empty($record['BrwImage'][$catCode])) {
			if ($imgCat['index']) {
				echo '<div class="images-gallery clearfix">';
				echo $this->element('image', array('image' => $record['BrwImage'][$catCode]));
			} else {
				if ($permissions[$model]['edit']) {
					echo '<div class="actions"><ul><li class="add-image">
					' . $html->link(__d('brownie', 'Add', true), array(
						'controller' => 'contents', 'action' => 'add_images',
						$model, $record[$model]['id'], $catCode
					)) . '</li></ul></div>';
				}
				echo '<div class="images-gallery clearfix">';
				foreach ($record['BrwImage'][$catCode] as $image) {
					echo $this->element('image', array('image' => $image));
				}
			}
		} else {
			if ($permissions[$model]['edit']) {
				echo '
				<div class="actions"><ul><li class="add-image">'
				.  $html->link(__d('brownie', 'Add', true), array(
					'controller' => 'contents',
					'action' => 'add_images',
					$model,	$record[$model]['id'],
					$catCode
				)) . '</li></ul></div>';
			}
			echo '<div class="images-gallery clearfix">';
		}
		echo '</div>';
	}
}
?>
</div>
<div class="brw-files index">
<?php
if (!empty($brwConfig['files'])) {
	foreach ($brwConfig['files'] as $catCode => $fileCat) {
		echo '<h2>' . $fileCat['name_category'] . '</h2>';
		if (!empty($record['BrwFile'][$catCode])) {
			if ($fileCat['index']) {
				echo '<div class="files-gallery clearfix">';
				echo $this->element('file', array('file' => $record['BrwFile'][$catCode]));
			} else {
				if ($permissions[$model]['edit']) {
					echo '<div class="actions"><ul><li class="add-file">
					' . $html->link(__d('brownie', 'Add', true), array(
						'controller' => 'contents', 'action' => 'edit_file',
						$model, $record[$model]['id'], $catCode
					)) . '</li></ul></div>';
				}
				echo '<div class="files-gallery clearfix">';
				foreach ($record['BrwFile'][$catCode] as $file) {
					echo $this->element('file', array('file' => $file));
				}
			}
		} else {
			if ($permissions[$model]['edit']) {
				echo '
				<div class="actions"><ul><li class="add-file">'
				.  $html->link(__d('brownie', 'Add', true), array(
					'controller' => 'contents',
					'action' => 'edit_file',
					$model,	$record[$model]['id'],
					$catCode
				)) . '</li></ul></div>';
			}
			echo '<div class="files-gallery clearfix">';
		}
		echo '</div>';
	}
}
?>
</div>
<?php

foreach ($assoc_models as $key => $assoc) {
	//pr($assoc);
	echo $this->element('index', $assoc);
}
?>

