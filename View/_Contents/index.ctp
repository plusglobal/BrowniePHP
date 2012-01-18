<?php
echo $this->element(
	'index',
	array('model' => $model, 'records' => $records, 'calledFrom' => 'index')
);