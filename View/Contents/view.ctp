<div class="view" id="<?php echo $model;?>_view">
	<div class="clearfix">
	<h1><?php  echo __($brwConfig['names']['singular']);?></h1>
	<div class="actions-view">
		<ul class="actions neighbors">
			<?php

			if (!empty($neighbors['prev'])) {
				echo '
				<li class="prev">
					' . $this->Html->link(__d('brownie', 'Previous'),
					array('action' => 'view', $model, $neighbors['prev'][$model]['id']) + $this->params['named'],
					array('title' => __d('brownie', 'Previous'))).'
				</li>';
			}
			if (!empty($neighbors['next'])) {
				echo '
				<li class="next">
					' . $this->Html->link(__d('brownie', 'Next'),
					array('action' => 'view', $model, $neighbors['next'][$model]['id']) + $this->params['named'],
					array('title' => __d('brownie', 'Next'))).'
				</li>';
			}
			?>
			<?php
			if (!empty($this->params['named']['back_to'])) {
				$backToUrl = array('plugin' => 'brownie', 'controller' => 'contents');
				$named = $this->params['named'];
				$back_to = $named['back_to'];
				unset($named['back_to']);
				switch($back_to) {
					case 'index':
						$backToUrl += array('action' => 'index', $model) + $named;
					break;
				}
				echo '
				<li class="back">
					' . $this->Html->link(__d('brownie', 'Back'), $backToUrl, array('title' => __d('brownie', 'Back'))) . '
				<li>';
			}
			?>
		</ul>
		<?php echo $this->element('actions', array('record' => $record, 'calledFrom' => 'view', 'inView' => true)) ?>
	</div>

	<table class="view">
	<?php
	$i=0;
	foreach ($record[$model] as $field_name => $field_value) {
		if (!empty($schema[$field_name]) and !in_array($field_name, $brwConfig['fields']['no_view'])) {
			echo '
			<tr>
				<td class="label">' . __($brwConfig['fields']['names'][$field_name]) . '</td>';
				if (in_array($field_name, $i18nFields)) {
					echo '
					<td class="multiLang">
					' . $this->element('i18n_view_field', array('data' => $record['BrwI18n_' . $field_name])) . '
					</td>';
				} else {
					echo '
					<td class="fcktxt">
					' . (($field_value === null or $field_value === '') ? '&nbsp;' : $field_value ) . '
					</td>';
				}
				echo '
			</tr>';
		}
	}
	?>
	<?php foreach ($record['HABTM'] as $rel): ?>
		<?php if (!in_array($rel['model'], $brwConfig['hide_related']['hasAndBelongsToMany'])): ?>
		<tr>
			<td class="label"><?php echo $rel['name'] ?></td>
			<td class="habtm">
				<ul>
				<?php foreach ($rel['data'] as $id => $name) : ?>
					<li><?php echo $this->Html->link($name, array('plugin' => 'brownie',
					'controller' => 'contents', 'action' => 'view', $rel['model'], $id)) ?></li>
				<?php endforeach ?>
				</ul>
			</td>
		</tr>
		<?php endif; ?>
	<?php endforeach; ?>
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
			' . $this->Html->link(__d('brownie', 'Add'), array(
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