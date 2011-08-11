<?php
foreach ($brwConfig['fields']['export'] as $field) {
	$fieldName = !empty($brwConfig['fields']['names'][$field]) ? $brwConfig['fields']['names'][$field] : $field;
	echo '"' . $fieldName . '";';
}
echo "\n";
foreach ($records as $record) {
	foreach ($brwConfig['fields']['export'] as $field) {
		if (strstr($field, '.')) {
			$tmp = explode('.', $field);
			echo '"' . $record[$tmp[0]][$tmp[1]] . '";';
		} else {
			echo '"' . $record[$model][$field] . '";';
		}
	}
	reset($brwConfig['fields']['export']);
	echo "\n";
}