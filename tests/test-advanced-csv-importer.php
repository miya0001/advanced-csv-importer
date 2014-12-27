<?php

class AdvancedImporter_Test extends WP_UnitTestCase {

	/**
	 * Test for the action hook `acsv_get_post_objects`
	 *
	 * @test
	 */
	public function acsv_get_post_objects()
	{
		add_filter( 'acsv_pre_get_post_objects', function( $csv_file ){
			return array( 'foo' => 'bar' );
		} );

		$this->assertSame( array( 'foo' => 'bar' ), \ACSV\Main::get_post_objects( '/path/to/dummy.xml' ) );
	}

	/**
	 * Can we recover WP_Error object after serialize.
	 *
	 * @test
	 */
	public function serialize_test()
	{
		$data = array(
			true,
			true,
			new WP_Error( 'error', 'this is error' ),
		);

		$serialize = serialize( $data );
		$unserialize = unserialize( $serialize );

		$this->assertTrue( is_wp_error( $unserialize[2] ) );
	}

	/**
	 * Set category and tag
	 *
	 * @test
	 */
	public function insert_posts_category_tag()
	{
		$posts = array(
			array(
				'post_author'   => 'admin',
				'post_title'    => 'original post',
				'post_category' => array(
					'fruits',
					'flowers'
				),
			),
			array(
				'post_author' => 'foo', // should be admin
				'post_title'  => 'original post',
				'tags_input'  => array(
					'foo',
					'bar',
				),
			),
		);

		$inserted_posts = \ACSV\Main::insert_posts( $posts );

		$this->assertTrue( in_category( 'fruits', $inserted_posts[0] ) );
		$this->assertTrue( in_category( 'flowers', $inserted_posts[0] ) );

		$this->assertTrue( has_tag( 'foo', $inserted_posts[1] ) );
		$this->assertTrue( has_tag( 'bar', $inserted_posts[1] ) );
	}

	/**
	 * Removing default action from `advanced_csv_importer_after_insert_post`
	 *
	 * @test
	 */
	public function insert_posts_meta_remove_test()
	{
		remove_action(
			'acsv_after_insert_post',
			array( 'ACSV\Defaults\Actions', 'add_post_meta' ),
			10
		);

		$posts = array(
			array(
				'post_author' => 'admin',
				'post_title' => 'original post',
				'post_meta' => array(
					'meta-0-1' => 'value-0-1',
					'meta-0-2' => 'value-0-2',
				),
			),
			array(
				'post_author' => 'foo', // should be admin
				'post_title' => 'original post',
				'post_meta' => array(
					'meta-1-1' => 'value-1-1',
					'meta-1-2' => 'value-1-2',
				),
			),
		);

		$inserted_posts = \ACSV\Main::insert_posts( $posts );

		for ( $i = 0; $i < count( $inserted_posts ); $i++ ) {
			$post = get_post( $inserted_posts[ $i ] );
			$this->assertSame( "", get_post_meta( $post->ID, "meta-" . $i . "-1", true ) );
			$this->assertSame( "", get_post_meta( $post->ID, "meta-" . $i . "-2", true ) );
		}
	}

	/**
	 * Importing and add post meta.
	 *
	 * @test
	 */
	public function insert_posts_meta()
	{
		$posts = array(
			array(
				'post_author' => 'admin',
				'post_title' => 'original post',
				'post_meta' => array(
					'meta-0-1' => 'value-0-1',
					'meta-0-2' => 'value-0-2',
				),
			),
			array(
				'post_author' => 'foo', // should be admin
				'post_title' => 'original post',
				'post_meta' => array(
					'meta-1-1' => 'value-1-1',
					'meta-1-2' => 'value-1-2',
				),
			),
		);

		$inserted_posts = \ACSV\Main::insert_posts( $posts );

		for ( $i = 0; $i < count( $inserted_posts ); $i++ ) {
			$post = get_post( $inserted_posts[ $i ] );
			$this->assertSame( "value-" . $i . "-1", get_post_meta( $post->ID, "meta-" . $i . "-1", true ) );
			$this->assertSame( "value-" . $i . "-2", get_post_meta( $post->ID, "meta-" . $i . "-2", true ) );
		}
	}

	/**
	 * Importing author and title.
	 *
	 * @test
	 */
	public function insert_posts_author()
	{
		$posts = array(
			array(
				'post_author' => 'admin',
				'post_title' => 'original post',
			),
			array(
				'post_author' => 'foo', // should be admin
				'post_title' => 'original post',
			),
			array(
				'post_author' => 'bar', // should be admin
				'post_title' => 'original post',
			),
		);

		$inserted_posts = \ACSV\Main::insert_posts( $posts );
		for ( $i = 0; $i < count( $inserted_posts ); $i++ ) {
			$post = get_post( $inserted_posts[ $i ] );
			$this->assertSame( "1", $post->post_author, $posts[ $i ]['post_author'] . ' should be 1.' );
		}

		$logs = \ACSV\Main::get_history();
		$this->assertEquals( 1, count( $logs ) );

		$log_name = \ACSV\Main::get_log_name( $inserted_posts );
		$log = \ACSV\Main::get_imported_post_ids( $log_name );
		$this->assertEquals( 3, count( $log ) );
	}

	/**
	 * @test
	 */
	public function insert_posts_page()
	{
		$post_objects = \ACSV\Main::get_post_objects( dirname( __FILE__ ) . '/_data/wp/pages.csv' );
		$inserted_posts = \ACSV\Main::insert_posts( $post_objects );

		foreach ( $inserted_posts as $pid ) {
			$post = get_post( $pid );
			$this->assertSame( 'page', get_post_type( $post ) );
		}

		$this->assertSame( 0, get_post( $inserted_posts[0] )->menu_order );
		$this->assertSame( 1, get_post( $inserted_posts[1] )->menu_order );
		$this->assertSame( 3, get_post( $inserted_posts[2] )->menu_order );
		$this->assertSame( 2, get_post( $inserted_posts[3] )->menu_order );
	}

	/**
	 * @test
	 */
	public function insert_posts_03()
	{
		$posts = array(
			array(
				'post_title' => 'original post'
			)
		);

		$original_posts = \ACSV\Main::insert_posts( $posts );
		$this->assertSame( 'original post', get_post( $original_posts[0] )->post_title );

		$posts = array(
			array(
				'ID'         => $original_posts[0],
				'post_title' => 'updated post'
			)
		);

		$updated_posts = \ACSV\Main::insert_posts( $posts );
		$this->assertSame( 'updated post', get_post( $updated_posts[0] )->post_title );
		$this->assertSame( $original_posts[0], $updated_posts[0] );
	}

	/**
	* @test
	*/
	public function insert_posts_02()
	{
		$num_posts = 5;

		$post_statuses = array(
			'publish',
			'pending',
			'draft',
		);

		$users = $this->factory->user->create_many( 10 );
		$ping_statuses = array( 'closed', 'open' );
		$categories = $this->factory->category->create_many( 10 );

		$posts = array();
		for ( $i = 0; $i < $num_posts; $i++) {
			$posts[] = array(
				'post_content'    => 'post-content-' . $i,
				'post_title'      => 'post-title-' . $i,
				'post_status'     => $post_statuses[ rand( 0, count( $post_statuses ) - 1 ) ],
				'post_type'       => 'post',
				'post_author'     => rand( 1, 10 ),
				'ping_status'     => $ping_statuses[ rand( 0, count( $ping_statuses ) - 1 ) ],
				'post_excerpt'    => 'post-excerpt-' . $i,
				'post_date'       => '2014-01-01 00:10:25',
				'comment_status'  => $ping_statuses[ rand( 0, count( $ping_statuses ) - 1 ) ],
				'post_category'   => array( $categories[ rand( 0, count( $categories ) - 1 ) ] ),
				'post_meta' => array(
					'meta_key_' . $i => 'meta_value_' . $i
				)
			);
		};

		$inserted_posts = \ACSV\Main::insert_posts( $posts );

		for ( $i = 0; $i < $num_posts; $i++ ) {
			foreach ( $posts[ $i ] as $key => $value ) {
				if ( 'post_meta' === $key ) {
					$this->assertEquals(
						'meta_value_' . $i,
						get_post_meta( $inserted_posts[ $i ], 'meta_key_' . $i, true )
					);
				} else {
					$this->assertEquals( $value, get_post( $inserted_posts[ $i ] )->$key );
				}
			}
		}
	}

	/**
	* @test
	*/
	public function insert_posts_01()
	{
		$post_objects = \ACSV\Main::get_post_objects( dirname( __FILE__ ) . '/_data/wp/sample.csv' );
		$inserted_posts = \ACSV\Main::insert_posts( $post_objects );
		$this->assertSame( 4, count( $inserted_posts ) );

		for ( $i = 0; $i < count( $post_objects ); $i++ ) {
			if ( is_wp_error( $inserted_posts[ $i ] ) ) {
				continue;
			}

			$post = $post_objects[ $i ];
			$p = get_post( $inserted_posts[ $i ] );

			if ( $post['post_name'] ) {
				$this->assertSame( $p->post_name, strtolower( $post['post_name'] ) );
			}
			$this->assertSame( $p->post_title, $post['post_title'] );
			$this->assertSame( $p->post_content, $post['post_content'] );
		}
	}

	/**
	* @test
	*/
	public function get_post_objects_03()
	{
		$post_objects = \ACSV\Main::get_post_objects( dirname( __FILE__ ) . '/_data/wp/sample.csv' );
		$this->assertSame( 4, count( $post_objects ) );
		$this->assertSame( 1, count( $post_objects[0]['post_category'] ) );
		$this->assertSame( 2, count( $post_objects[0]['tags_input'] ) );
	}

	/**
	* @test
	*/
	public function get_post_objects_02()
	{
		add_filter( 'acsv_post_object_keys', function(){
			return array(
				'ID' => 'ID',
				'isImported' => 'post_title'
			);
		} );

		$post_objects = \ACSV\Main::get_post_objects( dirname( __FILE__ ) . '/_data/csv/simple.csv' );
		$this->assertSame( "0", $post_objects[1]['post_title'] );
		$this->assertSame( "19", $post_objects[2]['ID'] );
	}

	/**
	* @test
	*/
	public function get_post_objects_01()
	{
		$post_objects = \ACSV\Main::get_post_objects( dirname( __FILE__ ) . '/_data/csv/simple.csv' );
		$this->assertSame( "0", $post_objects[1]['post_meta']['isImported'] );
		$this->assertSame( "19", $post_objects[2]['ID'] );
	}

	/**
	 * @test
	 */
	public function csv_parser()
	{
		$data = \ACSV\Main::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/simple.csv' );
		$this->assertSame( 3, count( $data ) );

		/*
		 * column with escaping, column with new line
		 */
		$data = \ACSV\Main::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/escaping.csv' );
		$this->assertSame( "columns with\nnew line", $data[4]['col1'] );
		$this->assertSame( "column with \\n \\t \\\\", $data[6]['col1'] );

		/*
		 * utf-8 multibytes and CRLF
		 */
		$data = \ACSV\Main::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/multibytes-utf8.csv' );
		$this->assertSame( array( '名前' => '太田 三子', '住所' => '福岡市', '年齢' => '50歳' ), $data[2] );

		/*
		 * sjis multibytes and CRLF
		 */
		add_filter( 'acsv_csv_format', function( $format ){
			$format['from_charset'] = 'SJIS-win';
			return $format;
		} );
		$data = \ACSV\Main::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/multibytes-sjis.csv' );
		$this->assertSame( array( '名前' => '太田 三子', '住所' => '福岡市', '年齢' => '50歳' ), $data[2] );

		/*
		 * should be wp_error when file not found.
		 */
		$data = \ACSV\Main::csv_to_hash_array( 'file-not-exists' );
		$this->assertTrue( is_wp_error( $data ) );

		/*
		 * should be wp_error when file is not csv.
		 */
		$data = \ACSV\Main::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/img.png' );
		$this->assertTrue( is_wp_error( $data ) );
	}

	/**
	 * @test
	 */
	public function parser()
	{
		$data = \ACSV\Main::csv_parser( dirname( __FILE__ ) . '/_data/csv/escaping.csv' );
		$this->assertTrue( is_array( $data ) );
	}

}
