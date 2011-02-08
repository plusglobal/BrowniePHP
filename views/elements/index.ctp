<div class="index">
	<div class="clearfix">
		<h2><?php echo $brwConfig['names']['plural'] ?></h2>
		<div class="actions">
			<ul>
			<?php
			if ($permissions[$model]['edit'] and $permissions[$model]['add']) {
				echo '
				<li class="add">' . $html->link(
					String::insert(
						__d('brownie', 'Add :name_singular', true),
						array('name_singular' => $brwConfig['names']['singular'])
					),
					array('action' => 'edit', $model, $foreignKeyValue, 'after_save' => 'index')
				) . '</li>';
			}
			if ($brwConfig['actions']['import']) {
				echo '
				<li class="import">' . $html->link(
					__d('brownie', 'Import', true),
					array('action' => 'import', $model)
				) . '</li>';
			}
			?>
			</ul>
		</div>
	</div>
</div>
<?php
$paginator->options(array('url' => Set::merge($this->passedArgs, array('model' => $model))));
$i = 0;
if ($records):
	echo '<table cellpadding="0" cellspacing="0">';
	foreach ($records as $record):
		if ($i == 0) {
			echo '
			<tr>';
			foreach($record[$model] as $field_name => $field_value) {
				if (!empty($schema[$field_name])) {
					echo '
					<th class="' . $field_name . ' ' . $schema[$field_name]['class']
					. '">' . $paginator->sort($field_name, null, array('model' => $model, 'escape' => false)) . '</th>';
				}
			}
			if (($brwConfig['sortable'] and empty($this->params['named']['sort'])) or !empty($isTree)) {
				echo '<th class="actions">' . __d('brownie', 'Sort', true) . '</th>';
			}
			echo '
			<th class="actions">' . __d('brownie', 'Actions', true) . '</th>';
			reset($record[$model]);
			echo '
			</tr>';
		}

		$class = ife(($i++ % 2 != 0), 'altrow', '');

		echo '
		<tr class="'.$class.' list">';

		foreach($record[$model] as $field_name => $field_value) {
			if (!empty($schema[$field_name])) {
				echo '
				<td class="' . $field_name . ' ' . $schema[$field_name]['class'] . ' field">' . ife(!empty($field_value), $field_value, '&nbsp;') . '</td>';
			}
		}

		if (($brwConfig['sortable'] and empty($this->params['named']['sort'])) or !empty($isTree)): ?>
			<td class="sortable actions">
			<?php
			echo $html->link(__d('brownie', 'Sort up', true),
				array('controller' => 'contents', 'action' => 'reorder', $model, 'up', $record[$model]['id']),
				array('class' => 'up', 'title' => __d('brownie', 'Sort up', true))
			);
			echo $html->link(__d('brownie', 'Sort down', true),
				array('controller' => 'contents', 'action' => 'reorder', $model, 'down', $record[$model]['id']),
				array('class' => 'up', 'title' => __d('brownie', 'Sort down', true))
			);
			?>
			</td>
		<?php endif ?>

			<td class="actions">
			<?php echo $this->element('actions', array('record' => $record, 'model' => $model, 'calledFrom' => $calledFrom)) ?>
			</td>
		</tr>
	<?php endforeach;
	echo '</table>';
else:
	echo '<p class="norecords">' . __d('brownie', 'No records', true) . '</p>';
endif;

if ($records) {
	echo '<div class="pagination">';
	if ($numbers = $paginator->numbers(array('model' => $model, 'separator' => ''))) {
		echo '
		<div class="paging clearfix">
			<span class="prev">' . $paginator->prev(
				'&laquo; ' . __d('brownie', 'previous', true), array('model' => $model, 'escape' => false), null, array('class'=>'disabled')
			) . '</span>
			' . $numbers . '
			<span class="next">' . $paginator->next(
				__d('brownie', 'next', true).' &raquo;', array('model' => $model, 'escape' => false), null, array('class'=>'disabled')
			) . '</span>
		</div>';
	}

	echo '
	<p>' . $paginator->counter(array(
		'format' => String::insert(
			__d('brownie', 'Page %page% of %pages%, showing %current% :name_plural out of %count% total, starting on record %start%, ending on %end%', true),
			array('name_plural' => $brwConfig['names']['plural'])
		),
		'model' => $model
	)) . '</p>';

	echo '</div>';
}

unset($paginator);
?>