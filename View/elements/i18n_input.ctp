<?php
foreach ($langs3chars as $lang) {
	$label = $params['label'] . ' <span class="lang '. $lang . '">' . $this->i18n->humanize($lang) . '</span>';
	$type = $schema[$field]['type'] == 'text' ? 'textarea' : 'text';
	echo $this->Form->input(
		$model . '.' . $field . '.' . $lang,
		array_merge($params, array('label' => $label, 'type' => $type))
	);
}