<div class="model-index">

<div class="index clearfix">
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
				array_merge(
					array('action' => 'edit', $model, 'after_save' => ($this->params['action'] == 'view') ? 'parent':'index'),
					$filters
				)
			) . '</li>';
		}
		if ($brwConfig['actions']['import']) {
			echo '
			<li class="import">' . $html->link(
				__d('brownie', 'Import', true),
				array('action' => 'import', $model)
			) . '</li>';
		}
		if ($brwConfig['actions']['export']) {
			$url = array_merge(array('action' => 'export', $model), $this->params['named'], $filters);
			echo '<li class="export">' . $html->link(__d('brownie', 'Export', true), $url) . '</li>';
		}
		?>
		</ul>
	</div>
</div>
<?php
if ($brwConfig['fields']['filter'] and $calledFrom == 'index') {
	echo $this->element('form_filter', array('brwConfig' => $brwConfig));
}


$i = 0;
if ($records):
	$pageParams = $this->Paginator->params['paging'][$model];
	if ($pageParams['pageCount'] > 1 or $pageParams['count'] > 20) {
		echo $this->element('pagination', array('model' => $model));
	}

	echo '<table id="index">';
	foreach ($records as $record):
		if ($i == 0) {
			echo '
			<tr>';
			foreach($brwConfig['paginate']['fields'] as $field_name) {
				if (!empty($schema[$field_name])) {
					echo '
					<th class="' . $field_name . ' ' . $schema[$field_name]['class']
					. '">' . $paginator->sort(
						__($brwConfig['fields']['names'][$field_name], true),
						$field_name,
						array('model' => $model, 'escape' => false)
					) . '</th>';
				}
			}
			if (($brwConfig['sortable'] and empty($this->params['named']['sort'])) or !empty($isTree)) {
				echo '<th class="actions">' . __d('brownie', 'Sort', true) . '</th>';
			}
			echo '
				<th class="actions">' . __d('brownie', 'Actions', true) . '</th>
			</tr>';
		}

		$class = ife(($i++ % 2 != 0), 'altrow', '');

		echo '
		<tr class="'.$class.' list">';

		foreach($brwConfig['paginate']['fields'] as $field_name) {
			if (!empty($schema[$field_name])) {
				echo '
				<td class="' . $field_name . ' ' . $schema[$field_name]['class'] . ' field">'
					. ( !empty($record[$model][$field_name]) ? $record[$model][$field_name] : '&nbsp;' )
				. '</td>';
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
				array('class' => 'down', 'title' => __d('brownie', 'Sort down', true))
			);
			?>
			</td>
		<?php endif ?>

			<td class="actions">
			<?php
			$paramsAction = array(
				'filters' => $filters, 'record' => $record, 'model' => $model, 'calledFrom' => $calledFrom
			);
			echo $this->element('actions', $paramsAction);
			?>
			</td>
		</tr>
	<?php endforeach;
	echo '</table>';
else:
	echo '<p class="norecords">'
	. sprintf(__d('brownie', 'There are no %s', true), $brwConfig['names']['plural'])
	. '</p>';
endif;

if ($records) {
	echo $this->element('pagination', array('model' => $model));
	$this->Paginator = null;
}
?>
</div>