<?php

namespace ACSV;

use \WP_Error;

class History {

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {}

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @staticvar Singleton $instance The *Singleton* instances of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function getInstance()
	{
		static $instance = null;
		if (null === $instance) {
			$instance = new static();
		}
		return $instance;
	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone()
	{
	}

	/**
	 * Register post-type for import.
	 *
	 * @param  none
	 * @return none
	 * @since  0.1.0
	 */
	public static function init()
	{
		$args = array(
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'author', 'custom-fields' ),
			'can_export'         => false,
		);

		register_post_type( 'acsv-log', $args );
	}

	/**
	 * Save histoy in the post-type `acsv-log`.
	 *
	 * @param  array $inserted_posts Inserted IDs.
	 * @return string ID.
	 * @since  0.1.0
	 */
	public static function save_history( $inserted_posts )
	{
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$from = 'WP-CLI';
		} else {
			$from = 'admin screen';
		}

		$uid = get_current_user_id();

		$post = array(
			'post_author' => $uid,
			'post_title'  => 'Imported from ' . $from . '.',
			'post_type'   => 'acsv-log',
			'post_status' => 'publish',
			'post_name'   => self::get_log_name( $inserted_posts ),
		);

		$helper = new \Megumi\WP\Post\Helper( $post );
		$post_id = $helper->insert();

		update_post_meta( $post_id, '_import_log', serialize( $inserted_posts ) );
	}

	/**
	 * Returns the posts from post ids.
	 *
	 * @param  array $inserted_posts Inserted IDs.
	 * @return array Post objects.
	 * @since  0.1.0
	 */
	public static function post_ids_to_posts( $post_ids )
	{
		$posts = array();

		foreach ( $post_ids as $post_id ) {
			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}

			$posts[] = array(
				'ID' => $post_id,
				'Title' => $post->post_title,
				'Type' => $post->post_type,
				'Status' => $post->post_status,
				'Author' => get_user_by( 'id', $post->post_author )->user_login,
				'Date' => date_i18n( 'Y-m-d H:i:s', strtotime( $post->post_date ), true ),
			);
		}

		return $posts;
	}

	/**
	 * Get the log id
	 *
	 * @param  array $inserted_posts Inserted IDs.
	 * @return string ID.
	 * @since  0.1.0
	 */
	public static function get_log_name( $inserted_posts )
	{
		return substr( sha1( json_encode( $inserted_posts ) ), 0, 8 );
	}

	/**
	 * Get the log id
	 *
	 * @param  array $inserted_posts Inserted IDs.
	 * @return string ID.
	 * @since  0.1.0
	 */
	public static function get_imported_post_ids( $log_name )
	{
		$post = get_page_by_path( $log_name, OBJECT, 'acsv-log' );

		if ( $post ) {
			$log = unserialize( get_post_meta( $post->ID, '_import_log', true ) );
			return $log;
		} else {
			return new WP_Error( 'Error', 'Not found.' );
		}
	}

	/**
	 * Get import log.
	 *
	 * @param  none
	 * @return array Import log.
	 * @since  0.1.0
	 */
	public static function get_history()
	{
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$cli = true;
		} else {
			$cli = false;
		}

		$args = array(
			'post_type'   => 'acsv-log',
			'post_status' => 'publish',
		);

		$posts = get_posts( $args );

		$logs = array();
		foreach ( $posts as $log ) {
			$imported = self::get_imported_post_ids( $log->post_name );

			if ( $cli ) {
				$success = sprintf( '% 7d', self::get_num_success( $imported ) );
				$failure = sprintf( '% 7d', self::get_num_fail( $imported ) );
			} else {
				$success = self::get_num_success( $imported );
				$failure = self::get_num_fail( $imported );
			}

			$logs[] = array(
				'ID'    => $log->post_name,
				'Title'   => $log->post_title,
				'Date'    => $log->post_date,
				'Success' => $success,
				'Failure' => $failure,
			);
		}

		return $logs;
	}

	/**
	 * Return the number of success of impoted.
	 *
	 * @param  array $imported Imported post ids.
	 * @return int   Return the number of success.
	 * @since  0.1.0
	 */
	public static function get_num_success( $imported )
	{
		$array = array();
		foreach ( $imported as $id ) {
			if ( ! is_wp_error( $id ) ) {
				$array[] = $id;
			}
		}

		return count( $array );
	}

	/**
	 * Return the number of fail of impoted.
	 *
	 * @param  array $imported Failed post ids.
	 * @return int   Return the number of fail.
	 * @since  0.1.0
	 */
	public static function get_num_fail( $imported )
	{
		$array = array();
		foreach ( $imported as $id ) {
			if ( is_wp_error( $id ) ) {
				$array[] = $id;
			}
		}

		return count( $array );
	}
}
