<div id="login">
	<div id="login-inside" class="clearfix">
		<h1><?php __d('brownie', 'Login'); ?></h1>
		<p>
			<?php
			echo '<stong>' . __d('brownie', 'Welcome', true) . '</strong>. ';
			__d('brownie', 'Please provide your user and password.');
			?>
		</p>
		<?php
		echo $session->flash('auth');
		echo $form->create();
		echo $form->input('BrwUser.email', array('label' => __d('brownie', 'Username', true)));
		echo $form->input('BrwUser.password', array('label' => __d('brownie', 'Password', true)));
		echo $form->end(__d('brownie', 'Login', true), array('class' => 'submit'));
		?>
	</div>
</div>