<?php
$options = array('url' => Set::merge($this->passedArgs, array('model'=>$model)));
$paginator->options($options);
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
					<th class="'.$field_name.' '.$schema[$field_name]['type'];
					//poner otro class si el campo es foreign_key de otra tabla
					echo '">' . $paginator->sort($field_name, null, array('model' => $model)) . '</th>';
				}
			}
			if ($brwConfig['sortable'] and empty($this->params['named']['sort'])) {
				echo '<th>' . $paginator->sort($brwConfig['sortable']['field'], null, array('model' => $model)) . '</th>';
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
				<td class="'.$field_name.' '.$schema[$field_name]['type'].' field">' . ife(!empty($field_value), $field_value, '&nbsp;') . '</td>';
			}
		}

		if ($brwConfig['sortable'] and empty($this->params['named']['sort'])) {
			echo '<td class="sortable">
			<a href="' . Router::url(array(
				'controller' => 'contents', 'action' => 'reorder', $model, 'up', $record[$model]['id']
			)) . '">' . __d('brownie', 'Up', true) . '</a>
			<a href="' . Router::url(array(
				'controller' => 'contents', 'action' => 'reorder', $model, 'down', $record[$model]['id']
			)) . '">'.__d('brownie', 'Down', true).'</a>
			</td>';
		}

		echo '<td class="actions"><ul>';
		if ($permissions[$model]['view']) {
			echo '<li class="view">' . $html->link(__d('brownie', 'View', true),
			array('action'=>'view', $model, $record[$model]['id'])) . '</li>';
		}
		if ($permissions[$model]['edit']) {
			echo '<li class="edit">' . $html->link(__d('brownie', 'Edit', true),
			array('action'=>'edit', $model, $record[$model]['id'], 'after_save' => 'index')) . '</li>';
		}
		if ($permissions[$model]['delete']) {
			echo '<li class="delete">' . $html->link(__d('brownie', 'Delete', true),
			array('action'=>'delete', $model, $record[$model]['id']), null,
			sprintf(__d('brownie', 'Are you sure you want to delete # %s?', true), $record[$model]['id'])) . '</li>';
		}
		echo '</ul></td>
		</tr>';
	endforeach;
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