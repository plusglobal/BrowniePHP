<?php
Router::connect('/download/*', array('plugin' => 'brownie', 'controller' => 'downloads', 'action' => 'get'));
Router::connect('/img/*', array('plugin' => 'brownie', 'controller' => 'thumbs', 'action' => 'view'));
Router::connect('/thumbs/*', array('plugin' => 'brownie', 'controller' => 'thumbs', 'action' => 'generate'));
Router::connect('/admin', array('plugin' => 'brownie', 'controller' => 'brownie', 'action' => 'login'));
Router::connect('/admin/:controller/:action/*', array('plugin' => 'brownie'));

Configure::write('Routing.prefixes', array('brw'));
