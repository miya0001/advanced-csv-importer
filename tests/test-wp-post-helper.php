<?php

use Megumi\WP\Post\Helper;
use WP_UnitTestCase;

class WP_Post_Helper_Test extends WP_UnitTestCase {

	public function testSample()
	{
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	/**
	 * @test
	 */
	public function wp_insert_post()
	{
		$num_categories = 3;
		$categories = $this->factory->category->create_many( $num_categories );
		array_shift( $categories );

		$my_post = array();
		$my_post['post_title'] = 'My post';
		$my_post['post_content'] = 'This is my post.';
		$my_post['post_status'] = 'publish';
		$my_post['post_author'] = 1;
		$my_post['post_category'] = $categories;

		$post_id = wp_insert_post( $my_post );

		$this->assertSame( count( $categories ), count( wp_get_object_terms( $post_id, 'category' ) ) );
	}

	/**
	 * @test
	 */
	public function post_helper_basic()
	{
		$num_categories = 3;
		$categories = $this->factory->category->create_many( $num_categories );
		array_shift( $categories );

		$args = array(
			'post_name'    => 'slug',                  // slug
			'post_author'  => '1',                     // author's ID
			'post_date'    => '2012-11-15 20:00:00',   // post date and time
			'post_type'    => 'post',                  // post type (you can use custom post type)
			'post_status'  => 'publish',               // post status, publish, draft and so on
			'post_title'   => 'title',                 // post title
			'post_content' => 'content',               // post content
			'post_category'=> $categories,             // category IDs in an array
			'post_tags'    => array( 'tag1', 'tag2' ), // post tags in an array
		);

		$helper = new Helper( $args );
		$post_id = $helper->insert();

		$post = get_post( $post_id );

		foreach ( $args as $key => $value) {
			if ( 'post_category' === $key || 'post_tags' === $key ) {
				continue;
			}
			$this->assertSame( $value, $post->$key );
		}

		$this->assertSame( 2, count( get_the_tags( $post_id ) ) );
		$this->assertSame( count( $categories ), count( wp_get_object_terms( $post_id, 'category' ) ) );

		// it should be success to upload and should be attached to the post
		$attachment_id = $helper->add_media(
			'http://placehold.jp/100x100.png',
			'title',
			'description',
			'caption',
			false
		);
		$media = get_attached_media( 'image', $post_id );
		$this->assertSame( $attachment_id, $media[ $attachment_id ]->ID );
	}
}
