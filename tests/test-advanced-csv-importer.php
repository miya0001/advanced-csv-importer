<?php

class AdvancedImporter_Test extends WP_UnitTestCase {

	/**
	 * @test
	 */
	public function parser()
	{
		$data = \ACSV\Utils::csv_parser( dirname( __FILE__ ) . '/_data/csv/escaping.csv' );
		$this->assertTrue( is_array( $data ) );
	}

	/**
	* @test
	*/
	public function insert_posts_02()
	{
		$num_posts = 10;

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
				'post_author'     => rand( 0, 9 ),
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

		$inserted_posts = \ACSV\Utils::insert_posts( $posts );

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
		$post_objects = \ACSV\Utils::parse_csv_to_post_objects( dirname( __FILE__ ) . '/_data/wp/sample.csv' );
		$inserted_posts = \ACSV\Utils::insert_posts( $post_objects );
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
	public function parse_csv_to_post_objects_03()
	{
		$post_objects = \ACSV\Utils::parse_csv_to_post_objects( dirname( __FILE__ ) . '/_data/wp/sample.csv' );
		$this->assertSame( 4, count( $post_objects ) );
		$this->assertSame( 1, count( $post_objects[0]['post_category'] ) );
		$this->assertSame( 2, count( $post_objects[0]['tags_input'] ) );
	}

	/**
	* @test
	*/
	public function parse_csv_to_post_objects_02()
	{
		add_filter( 'advanced_csv_importer_post_object_keys', function(){
			return array(
				'id' => 'ID',
				'isImported' => 'post_title'
			);
		} );

		$post_objects = \ACSV\Utils::parse_csv_to_post_objects( dirname( __FILE__ ) . '/_data/csv/simple.csv' );
		$this->assertSame( "0", $post_objects[1]['post_title'] );
		$this->assertSame( "19", $post_objects[2]['ID'] );
	}

	/**
	* @test
	*/
	public function parse_csv_to_post_objects_01()
	{
		$post_objects = \ACSV\Utils::parse_csv_to_post_objects( dirname( __FILE__ ) . '/_data/csv/simple.csv' );
		$this->assertSame( "0", $post_objects[1]['post_meta']['isImported'] );
		$this->assertSame( "19", $post_objects[2]['ID'] );
	}

	/**
	 * @test
	 */
	public function csv_parser()
	{
		$data = \ACSV\Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/simple.csv' );
		$this->assertSame( 3, count( $data ) );

		/*
		 * column with escaping, column with new line
		 */
		$data = \ACSV\Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/escaping.csv' );
		$this->assertSame( "columns with\nnew line", $data[4]['col1'] );
		$this->assertSame( "column with \\n \\t \\\\", $data[6]['col1'] );

		/*
		 * utf-8 multibytes and CRLF
		 */
		$data = \ACSV\Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/multibytes-utf8.csv' );
		$this->assertSame( array( '名前' => '太田 三子', '住所' => '福岡市', '年齢' => '50歳' ), $data[2] );

		/*
		 * sjis multibytes and CRLF
		 */
		add_filter( 'advanced_csv_importer_csv_format', function( $format ){
			$format['from_charset'] = 'SJIS-win';
			return $format;
		} );
		$data = \ACSV\Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/multibytes-sjis.csv' );
		$this->assertSame( array( '名前' => '太田 三子', '住所' => '福岡市', '年齢' => '50歳' ), $data[2] );

		/*
		 * should be wp_error when file not found.
		 */
		$data = \ACSV\Utils::csv_to_hash_array( 'file-not-exists' );
		$this->assertTrue( is_wp_error( $data ) );

		/*
		 * should be wp_error when file is not csv.
		 */
		$data = \ACSV\Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/img.png' );
		$this->assertTrue( is_wp_error( $data ) );
	}
}
