<?php

namespace ACSV;

use Goodby\CSV\Import\Standard\Lexer;
use Goodby\CSV\Import\Standard\Interpreter;
use Goodby\CSV\Import\Standard\LexerConfig;

use \WP_Error;

class Main {

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
			'supports'           => array( 'title', 'author', 'custom-fields' )
		);

		register_post_type( 'acsv-log', $args );

		add_action( 'advanced_csv_importer_after_insert_post', array( 'ACSV\Main', 'after_insert_post' ), 10, 2 );
	}

	/**
	 * Fire after insert post.
	 *
	 * @param  int   $post_id Post ID.
	 * @param  array $post    Post object.
	 * @return none
	 * @since  0.1.0
	 */
	public static function after_insert_post( $post_id, $post )
	{
		if ( ! is_wp_error( $post_id ) ) {
			if ( isset( $post['post_meta'] ) ) {
				foreach ( $post['post_meta'] as $meta_key => $meta_value ) {
					update_post_meta( $post_id, $meta_key, $meta_value );
				}
			}
		}
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
		}
	}

	/**
	 * Get import log.
	 *
	 * @param  none
	 * @return array Import log.
	 * @since  0.1.0
	 */
	public static function get_history( $cli = false )
	{
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
	 * Insert posts
	 *
	 * @param  string $post_objects    Path of the file.
	 * @return mixed  True or WP_Eroor object.
	 * @since  0.1.0
	 */
	public static function insert_posts( $post_objects )
	{
		$inserted_posts = array();

		foreach ( $post_objects as $post ) {

			// unset all empty fields
			foreach ( $post as $key => $value ) {
				if ( ! is_array( $value ) && ! strlen( $value ) ) {
					unset( $post[ $key ] );
				} elseif ( is_array( $value ) && ! count( $value ) ) {
					unset( $post[ $key ] );
				}
			}

			// insert and set category
			if ( isset( $post['post_category'] ) && is_array( $post['post_category'] )
						&& count( $post['post_category'] ) ) {
				$post['post_category'] = wp_create_categories( $post['post_category'] );
			}

			if ( isset( $post['post_date'] ) && $post['post_date'] ) {
				$post['post_date'] = date( "Y-m-d H:i:s", strtotime( $post['post_date'] ) );
			}

			// setup author
			if ( isset( $post['post_author'] ) && ! intval( $post['post_author'] ) ) {
				$u = get_user_by( 'login', $post['post_author'] );
				if ( $u ) {
					$post['post_author'] = $u->ID;
				} else {
					unset( $post['post_author'] );
				}
			}

			// setup post ID
			if ( isset( $post['ID'] ) && ! intval( $post['ID'] ) ) {
				unset( $post['ID'] );
			}

			// set default to the post.
			foreach ( Config::get_post_defaults() as $key => $value ) {
				if ( ! isset( $post[ $key ] ) ) {
					$post[ $key ] = $value;
				}
			}

			$helper = new \Megumi\WP\Post\Helper( $post );
			$post_id = $helper->insert();

			do_action( 'advanced_csv_importer_after_insert_post', $post_id, $post );

			$inserted_posts[] = $post_id;
		}

		self::save_history( $inserted_posts );
		return $inserted_posts;
	}

	/**
	 * Return the post object as array.
	 *
	 * @param  string $file    Path of the file.
	 * @return array Returns the post object as array.
	 * @since  0.1.0
	 */
	public static function parse_csv_to_post_objects( $csv_file )
	{
		$csv = self::csv_to_hash_array( $csv_file );
		if ( is_wp_error( $csv ) ) {
			return $csv;
		}

		$post_object_keys = Config::get_post_object_keys();

		$post_objects = array();
		foreach ( $csv as $row ) {
			$post = array();
			$post['post_meta'] = array();
			foreach ( $row as $col => $value ) {
				if ( isset( $post_object_keys[ $col ] ) && $post_object_keys[ $col ] ) {
					if ( $post_object_keys[ $col ] === 'post_category' || $post_object_keys[ $col ] === 'tags_input' ) {
						$post[ $post_object_keys[ $col ] ] = self::array_trim( preg_split( "/,/", $value ) );
					} else {
						$post[ $post_object_keys[ $col ] ] = trim( $value );
					}
				} else {
					$post['post_meta'][ $col ] = trim( $value );
				}
			}
			$post_objects[] = $post;
		}

		/**
		 * Filter the post_object for import.
		 *
		 * @param array $post_object The post object.
		 */
		return apply_filters( 'advanced_csv_importer_post_objects', $post_objects );
	}

	/**
	 * CSV parser
	 *
	 * @param  string $file    Path of the file.
	 * @return array Returns the array from csv.
	 * @since  0.1.0
	 */
	public static function csv_parser( $csv_file )
	{
		if ( ! is_file( $csv_file ) ) {
			return new WP_Error( 'error', 'The CSV file is not found.' );
		} elseif ( ! self::is_textfile( $csv_file ) ) {
			return new WP_Error( 'error', 'The file is not CSV.' );
		}

		$csv = array();

		/**
		 * Filter the CSV setting for the csv parser.
		 *
		 * @param array settings for the csv parser.
		 */
		$format = apply_filters( 'advanced_csv_importer_csv_format', array(
			'from_charset' => 'UTF-8',
			'to_charset'   => 'UTF-8',
			'delimiter'    => ',',
			'enclosure'    => '"',
			'escape'       => '\\',
		) );

		$config = new LexerConfig();
		$config->setFromCharset( $format['from_charset'] );
		$config->setToCharset( $format['to_charset'] );
		$config->setDelimiter( $format['delimiter'] );
		$config->setEnclosure( $format['enclosure'] );
		$config->setEscape( $format['escape'] );

		$lexer = new Lexer( $config );

		$interpreter = new Interpreter();
		$interpreter->addObserver( function( array $row ) use ( &$csv ) {
			$csv[] = $row;
		} );

		$lexer->parse( $csv_file, $interpreter );

		return $csv;
	}

	/**
	 * Returns the array from csv.
	 *
	 * @param  string $file Path of the file.
	 * @return array Returns the array from csv.
	 * @since  0.1.0
	 */
	public static function csv_to_hash_array( $csv_file )
	{
		$csv = self::csv_parser( $csv_file );

		if ( is_wp_error( $csv ) ) {
			return $csv;
		}

		$hash_array = array();
		$keys = array();

		foreach ( $csv as $row ) {
			if ( ! $keys ) {
				$keys = $row;
			} else {
				$cols = array();
				for ( $i = 0; $i < count( $keys ); $i++ ) {
					if ( isset( $row[ $i ] ) ) {
						$cols[ $keys[ $i ] ] = $row[ $i ];
					} else {
						$cols[ $keys[ $i ] ] = '';
					}

				}
				$hash_array[] = $cols;
			}
		}

		return apply_filters( "advanced_csv_importer_csv_to_hash_array", $hash_array );
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

	/**
	 * Returns the is it textfile or not.
	 *
	 * @param  string $file Path of the file.
	 * @return bool Returns true if file is binary
	 * @since  0.1.0
	 */
	private static function is_textfile( $file )
	{
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$type = finfo_file( $finfo, $file );
		if ( 'text/plain' === $type ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Return the trimed strings as array.
	 *
	 * @param  string $strings Path of the file.
	 * @return array Return the trimed strings as array.
	 * @since  0.1.0
	 */
	private static function array_trim( $strings )
	{
		$array = array();
		foreach ( $strings as $str ) {
			$str = trim( $str );
			if ( $str ) {
				$array[] = $str;
			}
		}

		return $array;
	}
}
