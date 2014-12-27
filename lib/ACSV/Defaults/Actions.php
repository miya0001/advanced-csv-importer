<?php

namespace ACSV\Defaults;

class Actions {

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
