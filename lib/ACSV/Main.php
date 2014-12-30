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
	 * Insert posts
	 *
	 * @param  array $post_objects Array of the post objects.
	 * @return array The post id or WP_Error object.
	 * @since  0.1.0
	 */
	public static function insert_posts( $post_objects )
	{
		$inserted_posts = array();

		foreach ( $post_objects as $post ) {
			$post_id = self::insert_post( $post );
			$inserted_posts[] = $post_id;
		}

		History::save_history( $inserted_posts );
		return $inserted_posts;
	}

	/**
	 * Insert post
	 *
	 * @param  array $post_object The post object.
	 * @return mixed The post id or WP_Error object.
	 * @since  0.1.0
	 */
	public static function insert_post( $post )
	{
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
			$field = apply_filters( 'acsv_get_user_by_field', 'login' );
			$u = get_user_by( $field, $post['post_author'] );
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
		foreach ( Defaults\Config::get_post_defaults() as $key => $value ) {
			if ( ! isset( $post[ $key ] ) ) {
				$post[ $key ] = $value;
			}
		}

		$helper = new \Megumi\WP\Post\Helper( $post );
		$post_id = $helper->insert();

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		} else {
			do_action( 'acsv_after_insert_post', $post_id, $post, $helper );
			return $post_id;
		}
	}

	/**
	 * Return the post object as array.
	 *
	 * @param  string $file    Path of the file.
	 * @return array Returns the post object as array.
	 * @since  0.1.0
	 */
	public static function get_post_objects( $csv_file )
	{
		if ( has_filter( 'acsv_pre_get_post_objects' ) ) {
			return apply_filters( 'acsv_pre_get_post_objects', $csv_file );
		}

		$csv = self::csv_to_hash_array( $csv_file );
		if ( is_wp_error( $csv ) ) {
			return $csv;
		}

		$post_object_keys = Defaults\Config::get_post_object_keys();

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
		return apply_filters( 'acsv_after_get_post_objects', $post_objects );
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
		$format = apply_filters( 'acsv_csv_format', array(
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
		$interpreter->unstrict();
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

		return apply_filters( 'acsv_csv_to_hash_array', $hash_array );
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
