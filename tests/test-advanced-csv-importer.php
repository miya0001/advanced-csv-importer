<?php

use ACSV\Utils;

class AdvancedImporter_Test extends WP_UnitTestCase {

	/**
	 * @test
	 */
	public function parser()
	{
		$data = Utils::csv_parser( dirname( __FILE__ ) . '/_data/csv/escaping.csv' );
		$this->assertTrue( is_array( $data ) );
	}

	/**
	* @test
	*/
	public function insert_posts()
	{
		$post_objects = Utils::parse_csv_to_post_objects( dirname( __FILE__ ) . '/_data/wp/sample.csv' );
		$inserted_posts = Utils::insert_posts( $post_objects );
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
		$post_objects = Utils::parse_csv_to_post_objects( dirname( __FILE__ ) . '/_data/wp/sample.csv' );
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

		$post_objects = Utils::parse_csv_to_post_objects( dirname( __FILE__ ) . '/_data/csv/simple.csv' );
		$this->assertSame( "0", $post_objects[1]['post_title'] );
		$this->assertSame( "19", $post_objects[2]['ID'] );
	}

	/**
	* @test
	*/
	public function parse_csv_to_post_objects_01()
	{
		$post_objects = Utils::parse_csv_to_post_objects( dirname( __FILE__ ) . '/_data/csv/simple.csv' );
		$this->assertSame( "0", $post_objects[1]['post_meta']['isImported'] );
		$this->assertSame( "19", $post_objects[2]['ID'] );
	}

	/**
	 * @test
	 */
	public function csv_parser()
	{
		$data = Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/simple.csv' );
		$this->assertSame( 3, count( $data ) );

		/*
		 * column with escaping, column with new line
		 */
		$data = Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/escaping.csv' );
		$this->assertSame( "columns with\nnew line", $data[4]['col1'] );
		$this->assertSame( "column with \\n \\t \\\\", $data[6]['col1'] );

		/*
		 * utf-8 multibytes and CRLF
		 */
		$data = Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/multibytes-utf8.csv' );
		$this->assertSame( array( '名前' => '太田 三子', '住所' => '福岡市', '年齢' => '50歳' ), $data[2] );

		/*
		 * sjis multibytes and CRLF
		 */
		add_filter( 'advanced_csv_importer_csv_format', function( $format ){
			$format['from_charset'] = 'SJIS-win';
			return $format;
		} );
		$data = Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/multibytes-sjis.csv' );
		$this->assertSame( array( '名前' => '太田 三子', '住所' => '福岡市', '年齢' => '50歳' ), $data[2] );

		/*
		 * should be wp_error when file not found.
		 */
		$data = Utils::csv_to_hash_array( 'file-not-exists' );
		$this->assertTrue( is_wp_error( $data ) );

		/*
		 * should be wp_error when file is not csv.
		 */
		$data = Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/csv/img.png' );
		$this->assertTrue( is_wp_error( $data ) );
	}
}
