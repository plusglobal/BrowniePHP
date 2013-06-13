<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex,nofollow" />
<?php
echo $this->Html->meta('favicon.ico', Router::url('/favicon.ico'), array('type' => 'icon'));
?>
<title><?php echo __d('brownie', 'Admin panel');
if ($companyName) {
	echo ' - ' . $companyName;
}
?></title>
</head>
<body>
<?php echo $content_for_layout; ?>
</body>
</html>