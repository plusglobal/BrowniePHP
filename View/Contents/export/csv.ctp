<?php
foreach ($brwConfig['fields']['export'] as $field) {
	$fieldName = $field;
	if (!empty($brwConfig['fields']['names'][$field])) {
		$fieldName = $brwConfig['fields']['names'][$field];
	} else {
		$tmp = explode('.', $field); $relModel = $tmp[0]; $relField = $tmp[1];
		if (!empty($relatedBrwConfig[$relModel])) {
			$fieldName = __($relatedBrwConfig[$relModel]['names']['singular']) . ' ' .
			$relatedBrwConfig[$relModel]['fields']['names'][$relField];
		}
	}
	echo $fieldName . ',';
}
echo "\n";
foreach ($records as $record) {
	foreach ($brwConfig['fields']['export'] as $field) {
		if (strstr($field, '.')) {
			$tmp = explode('.', $field);
			$value = $record[$tmp[0]][$tmp[1]];
		} else {
			$value = $record[$model][$field];
		}
		echo str_replace(',', ' ', utf8_decode($value)) . ',';
	}
	reset($brwConfig['fields']['export']);
	echo "\n";
}