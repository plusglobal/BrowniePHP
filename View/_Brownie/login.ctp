<div id="login">
	<div id="login-inside" class="clearfix">
		<h1><?php echo __d('brownie', 'Login'); ?></h1>
		<p>
			<?php
			echo '<stong>' . __d('brownie', 'Welcome') . '</strong>. ';
			echo __d('brownie', 'Please provide your user and password.');
			?>
		</p>
		<?php
		echo $this->Session->flash('auth');
		echo $this->Form->create();
		echo $this->Form->input('BrwUser.email', array('label' => __d('brownie', 'Username')));
		echo $this->Form->input('BrwUser.password', array('label' => __d('brownie', 'Password')));
		echo $this->Form->end(__d('brownie', 'Login'), array('class' => 'submit'));
		?>
	</div>
</div>