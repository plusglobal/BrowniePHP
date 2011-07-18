<?php
foreach ($records as $record) {
	foreach (array_shift($record) as $value) {
		echo '"' . utf8_decode($value) . '";';
	}
	echo "\n";
}