<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex,nofollow" />
<?php echo $html->meta('favicon.ico',
	$html->url('/favicon.ico'),
    array('type' => 'icon')
);?>
<?php
echo $html->css('/brownie/css/brownie');
echo $html->css('/brownie/css/thickbox');
echo $javascript->link('/brownie/js/jquery');
echo $javascript->link('/brownie/js/jquery.selso');
echo $javascript->link('/brownie/js/jquery.comboselect');
echo $javascript->link('/brownie/js/thickbox');
echo $javascript->link('/brownie/js/brownie');
echo $scripts_for_layout;
?>
<title><?php echo $title_for_layout; ?></title>
</head>
<body>
	<div id="container">
		<div id="header">
			<h1>
			<?php echo $html->link($companyName, array('plugin' => 'brownie', 'controller' => 'brownie', 'action' => 'index'));?>
			</h1>
		</div>
		<?php
		if(!empty($authUser)){
			echo '
			<div id="options-bar">
				<p>' . sprintf(__d('brownie', 'Welcome %s', true), $authUser['username']) . '</p>
				<ul>
					<li class="home">'.$html->link(__d('brownie', 'Home', true),
					array('controller' => 'brownie', 'action' => 'index')) . '</li>';

					if($isUserRoot) {
						echo '
						<li class="users">' . $html->link(__d('brownie', 'Users', true),
						array('controller' => 'contents', 'action' => 'index', 'BrwUser')) . '</li>
						<li class="groups">' . $html->link(__d('brownie', 'Users groups', true),
						array('controller' => 'contents', 'action' => 'index', 'BrwGroup')) . '</li>';
					}

					echo'
					<li class="logout">'.$html->link(__d('brownie', 'Logout', true),
					array('controller' => 'users', 'action' => 'logout')).'</li>
				</ul>
			</div>
			<div id="menu">'. $this->element('menu') . '</div>
			<div id="content">';
			$session->flash();
			echo $content_for_layout;
			echo '</div>';
		} else {
			$session->flash();
			echo $content_for_layout;
		}
		?>
	</div>
	<div id="footer">&nbsp;</div>
</body>
</html>