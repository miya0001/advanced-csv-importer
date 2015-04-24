<?php
/*
Plugin Name: Advanced CSV Importer
Version: 0.1.6
Description: Import posts, pages, custom fields, categories, tags and more from a CSV file.
Author: Takayuki Miyauchi
Author URI: https://github.com/miya0001/
Plugin URI: https://github.com/miya0001/advanced-csv-importer
Text Domain: advanced-csv-importer
Domain Path: /languages
*/

require_once dirname( __FILE__ ) . '/vendor/autoload.php';

add_action( 'init', array( 'ACSV\Defaults\Actions', 'init' ) );
add_action( 'init', array( 'ACSV\History', 'init' ) );

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	WP_CLI::add_command( 'csv', 'ACSV\Cli' );

} elseif ( is_admin() && defined('WP_LOAD_IMPORTERS') ) {

	require_once ABSPATH . 'wp-admin/includes/import.php';

	if ( ! class_exists( 'WP_Importer' ) ) {
		$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
		if ( file_exists( $class_wp_importer ) ) {
			require_once $class_wp_importer;
		}
	}

	$advanced_csv_importer = new ACSV\Importer();
	register_importer(
		'advanced-csv-importer',
		__('Advanced CSV Importer', 'advanced-csv-importer'),
		__('Import posts, categories, tags, custom fields from simple csv file.', 'advanced-csv-importer'),
		array (
			$advanced_csv_importer,
			'dispatch'
		)
	);

}
