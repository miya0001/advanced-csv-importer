<?php

namespace ACSV;

class Config {

	private static $post_object_keys = array(
		'ID',
		'post_content',
		'post_name',
		'post_title',
		'post_status',
		'post_type',
		'post_author',
		'ping_status',
		'post_parent',
		'menu_order',
		'to_ping',
		'pinged',
		'post_password',
		'guid',
		'post_content_filtered',
		'post_excerpt',
		'post_date',
		'post_date_gmt',
		'comment_status',
		'post_category',
		'tags_input',
		'tax_input',
		'page_template',
	);

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
	* Returns the is it textfile or not.
	*
	* @param  none
	* @return array Returns post object keys for wp_insert_post() as array.
	* @since  0.1.0
	*/
	public static function get_post_object_keys()
	{
		$keys = self::$post_object_keys;

		$post_object_keys = array();
		foreach ( $keys as $key ) {
			$post_object_keys[ strtolower( $key ) ] = $key;
		}

		return apply_filters( "advanced_csv_importer_post_object_keys", $post_object_keys );
	}
}
