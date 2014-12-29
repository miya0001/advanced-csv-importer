<?php

namespace ACSV\Defaults;

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
		'page_template',
	);

	private static $post_defaults = array(
		'post_status' => 'publish',
		'post_type'   => 'post',
		'post_author' => "1",
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
	 * Returns the post object keys.
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
			$post_object_keys[ $key ] = $key;
		}

		/**
		 * Filter the post object keys.
		 *
		 * @param array $post_object_keys The post object keys.
		 */
		return apply_filters( "acsv_post_object_keys", $post_object_keys );
	}

	/**
	 * Returns the post defaults.
	 *
	 * @param  none
	 * @return array Returns the post defaults.
	 * @since  0.1.0
	 */
	public static function get_post_defaults()
	{
		/**
		 * Filter the post defaults.
		 *
		 * @param array $post_defaults The post defaults.
		 */
		return apply_filters( "acsv_post_defaults", self::$post_defaults );
	}
}
