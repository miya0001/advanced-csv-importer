<?php

namespace ACSV;

use ACSV;

use \WP_CLI;
use \WP_CLI_Command;

/**
* Import posts or pages from a CSV file.
*/
class Cli extends WP_CLI_Command {

	function __construct()
	{
		parent::__construct();
	}


	/**
	 * Import posts or pages from a CSV file.
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : The name of the CSV file to import. If '-', then reads from STDIN.
     *
	 * [--charset=<charset>]
	 * : Character set of the CSV file. Defaults to UTF-8.
	 *
	 */
	function import( $args, $assoc_args )
	{
		if ( isset( $assoc_args['charset'] ) && $assoc_args['charset'] ) {
			add_filter( 'advanced_csv_importer_csv_format', function( $format ) use ( $assoc_args ) {
				$format['from_charset'] = strtoupper( $assoc_args['charset'] );
				return $format;
			} );
		}

		$post_objects = Utils::parse_csv_to_post_objects( $args[0] );

		if ( is_wp_error( $post_objects ) ) {
			WP_CLI::error( $post_objects->get_error_message() );
		}

		$inserted_posts = Utils::insert_posts( $post_objects );

		$posts = array();
		$error = array();
		foreach ( $inserted_posts as $post_id ) {
			if ( is_wp_error( $post_id ) ) {
				$error[] = $post_id;
				continue;
			}
			$posts[] = array(
				'ID' => $post_id,
				'post_title' => get_post( $post_id )->post_title,
			);
		}

		WP_CLI\Utils\format_items( 'table', $posts, array( 'ID', 'post_title'  ) );

		if ( count( $error ) ) {
			WP_CLI::line( 'Failed to import: ' . count( $error ) . '/' . count( $inserted_posts ) );
		}
	}
}
