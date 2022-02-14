<?php
/*
 * Plugin Name: Rusty Inc. Org Chart
 * Plugin URI: https://automattic.com/work-with-us/
 * Description: Simple UI to help making sense of a leading canine organization
 * Version: 0.1
 * Author: Engineering Hiring @ Automattic
 */

if ( file_exists(  __DIR__ . '/class-rusty-inc-org-chart-plugin.php' ) ) {
	require_once __DIR__ . '/class-rusty-inc-org-chart-plugin.php';
} else {
	echo  'File '. __DIR__ . '/class-rusty-inc-org-chart-plugin.php doesn\' exist';
	return;
}

if ( file_exists(  __DIR__ . '/class-rusty-inc-org-chart-sharing.php' ) ) {
	require_once __DIR__ . '/class-rusty-inc-org-chart-sharing.php';
} else {
	echo  'File '. __DIR__ . '/class-rusty-inc-org-chart-sharing.php doesn\' exist';
	return;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/class-rusty-inc-org-chart-cli.php';
	WP_CLI::add_command( 'rusty', 'Rusty_Inc_Org_Chart_CLI' );
} else {
	check_access();	
}

function check_access() { 
	$sharing = new Rusty_Inc_Org_Chart_Sharing();
	if ( ! is_admin(  ) &&  ! isset( $_GET['tree'] ) && ! str_contains( $_SERVER['REQUEST_URI'], '/wordpress/wp-login.php' ) && ! str_contains( $_SERVER['REQUEST_URI'], '/tests/test.html' )) {
		die( 'You shall not pass!');
	} else if ( ! is_admin(  ) && ( isset( $_GET['tree'] ) &&  $_GET['tree'] !== $sharing->key() ) ) {
		die( 'Hey, wrong key to access the plugin!');
	}
}


( new Rusty_Inc_Org_Chart_Plugin() )->add_init_action();
