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
	public function parse_csv_to_post_object_03()
	{
		$post_object = ACSV\Utils::parse_csv_to_post_object( dirname( __FILE__ ) . '/_data/wp/sample.csv' );
		$this->assertSame( 4, count( $post_object ) );
		$this->assertSame( 1, count( $post_object[0]['post_category'] ) );
		$this->assertSame( 2, count( $post_object[0]['tags_input'] ) );
	}

	/**
	* @test
	*/
	public function parse_csv_to_post_object_02()
	{
		add_filter( 'advanced_csv_importer_post_object_keys', function(){
			return array(
				'id' => 'ID',
				'isImported' => 'post_title'
			);
		} );

		$post_object = ACSV\Utils::parse_csv_to_post_object( dirname( __FILE__ ) . '/_data/csv/simple.csv' );
		$this->assertSame( "0", $post_object[1]['post_title'] );
		$this->assertSame( "19", $post_object[2]['ID'] );
	}

	/**
	* @test
	*/
	public function parse_csv_to_post_object_01()
	{
		$post_object = ACSV\Utils::parse_csv_to_post_object( dirname( __FILE__ ) . '/_data/csv/simple.csv' );
		$this->assertSame( "0", $post_object[1]['post_meta']['isImported'] );
		$this->assertSame( "19", $post_object[2]['ID'] );
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
