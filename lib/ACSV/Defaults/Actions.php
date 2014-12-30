<?php

namespace ACSV\Defaults;

class Actions {

	/**
	 * Register post-type for import.
	 *
	 * @param  none
	 * @return none
	 * @since  0.1.0
	 */
	public static function init()
	{
		// Register default actions to the hooks
		add_action( 'acsv_after_insert_post', array( __CLASS__, 'add_post_meta' ), 10, 2 );
		add_action( 'acsv_after_insert_post', array( __CLASS__, 'set_tags' ), 10, 2 );
	}

	/**
	 * Set tags to the post.
	 *
	 * @param  int   $post_id Post ID.
	 * @param  array $post	Post object.
	 * @return none
	 * @since  0.1.0
	 */
	public static function set_tags( $post_id, $post )
	{
		if ( ! is_wp_error( $post_id ) ) {
			if ( isset( $post['tags_input'] ) ) {
				wp_set_post_tags( $post_id, $post['tags_input'], true );
			}
		}
	}

	/**
	 * Add meta to the post.
	 *
	 * @param  int   $post_id Post ID.
	 * @param  array $post	Post object.
	 * @return none
	 * @since  0.1.0
	 */
	public static function add_post_meta( $post_id, $post )
	{
		if ( ! is_wp_error( $post_id ) ) {
			if ( isset( $post['post_meta'] ) ) {
				foreach ( $post['post_meta'] as $meta_key => $meta_value ) {
					update_post_meta( $post_id, $meta_key, $meta_value );
				}
			}
		}
	}

}
