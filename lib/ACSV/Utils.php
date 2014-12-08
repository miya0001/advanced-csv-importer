<?php

namespace ACSV;

use \ACSV\Utils as Utils;

use Goodby\CSV\Import\Standard\Lexer;
use Goodby\CSV\Import\Standard\Interpreter;
use Goodby\CSV\Import\Standard\LexerConfig;

use WP_Error;

class Utils {

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
	 * CSV parser
	 *
	 * @param  string $file    Path of the file.
	 * @param  string $charset Charset of the file.
	 * @return array Returns the array from csv.
	 * @since  0.1.0
	 */
	private static function parser( $csv_file, $charset = 'UTF-8' )
	{
		if ( ! is_file( $csv_file ) ) {
			return new WP_Error( 'error', 'The CSV file is not found.' );
		} elseif ( ! self::is_textfile( $csv_file ) ) {
			return new WP_Error( 'error', 'The file is not CSV.' );
		}

		$csv = array();

		$config = new LexerConfig();
		$config->setFromCharset( $charset );

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
	public static function csv_parser( $csv_file, $charset = 'UTF-8' )
	{
		$csv = self::parser( $csv_file, $charset );

		if ( is_wp_error( $csv ) ) {
			return $csv;
		}

		$data = array();
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
				$data[] = $cols;
			}
		}

		return $data;
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
}
