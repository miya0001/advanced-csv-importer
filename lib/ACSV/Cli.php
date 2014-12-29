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
	 * <file>
	 * : The name of the CSV file to import.
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

		$post_objects = Main::get_post_objects( $args[0] );

		if ( is_wp_error( $post_objects ) ) {
			WP_CLI::error( $post_objects->get_error_message() );
		}

		$inserted_posts = Main::insert_posts( $post_objects );
		$this->get_imported_data( $inserted_posts );
	}

	/**
	 * Display importing log.
	 *
	 * ## OPTIONS
	 *
	 * [<id>]
	 * : The ID of the log.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, csv, json, count, ids. Default: table
	 */
	function log( $args, $assoc_args )
	{
		if ( isset( $assoc_args['format'] ) ) {
			$format = $assoc_args['format'];
		} else {
			$format = 'table';
		}

		if ( isset( $args[0] ) && $args[0] ) {
			$ids = History::get_imported_post_ids( $args[0] );
			if ( $ids ) {
				if ( 'ids' === $format ) {
					echo implode( ' ', $ids );
				} else {
					$this->get_imported_data( $ids, $format );
				}
			} else {
				WP_CLI::warning( 'Not found.' );
			}
		} else {
			$history = History::get_history( true );
			WP_CLI\Utils\format_items( 'table', $history, array( 'ID', 'Title', 'Date', 'Success', 'Failure' ) );
		}
	}

	/**
	 * Display importing log.
	 *
	 * @param  array $inserted_posts An array of the post ids
	 * @return none
	 */
	private function get_imported_data( $inserted_posts, $format = 'table' )
	{
		$posts = History::post_ids_to_posts( $inserted_posts );

		WP_CLI\Utils\format_items( $format, $posts, array( 'ID', 'Title', 'Type', 'Status', 'Author', 'Date' ) );

		$fail = History::get_num_fail( $inserted_posts );

		if ( $fail ) {
			WP_CLI::warning( 'Failed to import: ' . $fail );
		}
	}
}
