<?php
/*
Plugin Name: Advanced CSV Importer
Version: 0.1.0
Description: PLUGIN DESCRIPTION HERE
Author: YOUR NAME HERE
Author URI: YOUR SITE HERE
Plugin URI: PLUGIN SITE HERE
Text Domain: advanced-csv-importer
Domain Path: /languages
*/

require dirname( __FILE__ ) . '/vendor/autoload.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'csv', '\ACSV\Cli' );
}
