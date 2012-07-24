<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex,nofollow" />
<?php
echo $this->Html->meta('favicon.ico', Router::url('/favicon.ico'), array('type' => 'icon'));
echo $this->Html->css(Configure::read('brwSettings.css'));
echo $this->Html->script(Configure::read('brwSettings.js'));
?>
<script type="text/javascript">
var APP_BASE = '<?php echo Router::url('/') ?>';
var SESSION_ID = '<?php //echo $this->Session->id() ?>';
var BRW_AUTH_USER = <?php echo json_encode(AuthComponent::user()); ?>;
var brwMsg = {
	no_checked_for_deletion: '<?php echo __d('brownie', 'No records checked for deletion') ?>',
	select: '<?php echo __d('brownie', 'Select') ?>',
	unselect: '<?php echo __d('brownie', 'Unselect') ?>',
	done: '<?php echo __d('brownie', 'Done') ?>',
	show_advanced: '<?php echo __d('brownie', 'Show advanced filters') ?>',
	hide_advanced: '<?php echo __d('brownie', 'Hide advanced filters') ?>',
	hide_menu: '<?php echo __d('brownie', 'Hide menu') ?>',
	show_menu: '<?php echo __d('brownie', 'Show menu') ?>',
};
</script>
<style type="text/css">
<?php if ($brwHideMenu): ?>
#menu{display: none;}
<?php endif; ?>
</style>
<title><?php
echo __d('brownie', 'Admin panel');
if ($companyName) {
	echo ' - ' . $companyName;
}
?></title>
</head>
<body>
<div id="container">
	<div id="header">
		<h1>
		<?php echo $this->Html->link($companyName, array('plugin' => 'brownie', 'controller' => 'brownie', 'action' => 'index', 'brw' => false)) ?>
		</h1>
	</div>
	<?php if (AuthComponent::user('id')) { ?>
	<div id="options-bar">
		<p id="toggle-menu">
			<?php
			$title = $brwHideMenu ? __d('brownie', 'Show menu') : __d('brownie', 'Hide menu');
			echo $this->Html->link(
				$title,
				array('controller' => 'brownie', 'action' => 'toggle_menu', 'plugin' => 'brownie', 'brw' => false),
				array('title' => $title, 'class' => $brwHideMenu ? 'toggle-hidden' : '')
			);
			?>
		</p>
		<p id="welcome-user"><?php echo __d('brownie', 'User: %s', AuthComponent::user('email')) ?></p>
		<ul>
			<li class="home"><?php echo $this->Html->link(__d('brownie', 'Home'),
			array('controller' => 'brownie', 'action' => 'index', 'plugin' => 'brownie', 'brw' => false)) ?></li>
			<li class="users"><?php
			$url = array('controller' => 'contents', 'action' => 'index', 'plugin' => 'brownie', 'brw' => false, AuthComponent::user('model'));
			$anchorText = __d('brownie', 'Users');
			if (AuthComponent::user('model') != 'BrwUser') {
				$url['action'] = 'view';
				$url[] = AuthComponent::user('id');
				$anchorText = __d('brownie', 'User');
			}
			echo $this->Html->link($anchorText, $url);
			?></li>
			<li class="logout"><?php echo $this->Html->link(__d('brownie', 'Logout'),
			array('controller' => 'brownie', 'action' => 'logout', 'plugin' => 'brownie', 'brw' => false)) ?></li>
		</ul>
	</div>
	<div id="menu"><?php echo $this->element('menu') ?></div>
	<div id="content">
		<?php
		echo $this->Session->flash();
		echo $content_for_layout;
		?>
	</div>
<?php
} else {
	echo $this->Session->flash();
	echo $content_for_layout;
} ?>
</div>
<div id="footer">&nbsp;</div>
</body>
</html>