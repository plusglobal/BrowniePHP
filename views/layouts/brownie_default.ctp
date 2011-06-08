<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex,nofollow" />
<?php
echo $html->meta('favicon.ico', Router::url('/favicon.ico'), array('type' => 'icon'));
echo $html->css(Configure::read('brwSettings.css'));
echo $javascript->link(Configure::read('brwSettings.js'));
?>
<script type="text/javascript">
var APP_BASE = '<?php echo Router::url('/') ?>';
var brwMsg = {
	no_checked_for_deletion: '<?php __d('brownie', 'No records checked for deletion') ?>'
};
</script>
<title><?php
__d('brownie', 'Admin panel');
if ($companyName) {
	echo ' - ' . $companyName;
}
?></title>
</head>
<body>
	<div id="container">
		<div id="header">
			<h1>
			<?php echo $html->link($companyName, array('plugin' => 'brownie', 'controller' => 'brownie', 'action' => 'index')) ?>
			</h1>
		</div>
		<?php if (!empty($authUser)) { ?>
		<div id="options-bar">
			<p id="welcome-user"><?php echo sprintf(__d('brownie', 'User: %s', true), $authUser['email']) ?></p>
			<ul>
				<li class="home"><?php echo $html->link(__d('brownie', 'Home', true),
				array('controller' => 'brownie', 'action' => 'index')) ?></li>
				<?php if($sitesModel = Configure::read('multiSitesModel') and $currentSite = Configure::read('currentSite.id')): ?>
				<li class="site"><?php echo $html->link(__d('brownie', 'Site', true),
				array('controller' => 'contents', 'action' => 'view', $sitesModel, $currentSite)) ?></li>
				<?php endif ?>

				<?php //parche provisorio para que un usuario no cambie el valor root
				if(Configure::read('Auth.BrwUser.root')): ?>
				<li class="users"><?php echo $html->link(__d('brownie', 'Users', true),
				array('controller' => 'contents', 'action' => 'index', 'BrwUser')) ?></li>
				<?php endif ?>


				<?php /*<li class="groups"><?php echo $html->link(__d('brownie', 'Users groups', true),
				array('controller' => 'contents', 'action' => 'index', 'BrwGroup')) ?></li>*/ ?>
				<li class="logout"><?php echo $html->link(__d('brownie', 'Logout', true),
				array('controller' => 'brownie', 'action' => 'logout')) ?></li>
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