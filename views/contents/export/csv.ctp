<?php
foreach ($records as $record) {
	echo join(',', $record[$model]) . "\n";
}