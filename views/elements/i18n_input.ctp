<?php
foreach ($langs3chars as $lang) {
	echo $form->input($model . '.' . $field . '.' . $lang, $params);
}
?>