<?php

class AdvancedImporter_Test extends WP_UnitTestCase {

	/**
	 * @test
	 */
	public function parser()
	{
		$data = \ACSV\Utils::csv_parser( dirname( __FILE__ ) . '/_data/escaping.csv' );
		$this->assertTrue( is_array( $data ) );
	}

	/**
	 * @test
	 */
	public function csv_parser()
	{
		$data = \ACSV\Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/simple.csv' );
		$this->assertSame( 3, count( $data ) );

		/*
		 * column with escaping, column with new line
		 */
		$data = \ACSV\Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/escaping.csv' );
		$this->assertSame( "columns with\nnew line", $data[4]['col1'] );
		$this->assertSame( "column with \\n \\t \\\\", $data[6]['col1'] );

		/*
		 * utf-8 multibytes and CRLF
		 */
		$data = \ACSV\Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/multibytes-utf8.csv' );
		$this->assertSame( array( '名前' => '太田 三子', '住所' => '福岡市', '年齢' => '50歳' ), $data[2] );

		/*
		 * sjis multibytes and CRLF
		 */
		add_filter( 'advanced_csv_importer_csv_format', function( $format ){
			$format['from_charset'] = 'SJIS-win';
			return $format;
		} );
		$data = \ACSV\Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/multibytes-sjis.csv' );
		$this->assertSame( array( '名前' => '太田 三子', '住所' => '福岡市', '年齢' => '50歳' ), $data[2] );

		/*
		 * should be wp_error when file not found.
		 */
		$data = \ACSV\Utils::csv_to_hash_array( 'file-not-exists' );
		$this->assertTrue( is_wp_error( $data ) );

		/*
		 * should be wp_error when file is not csv.
		 */
		$data = \ACSV\Utils::csv_to_hash_array( dirname( __FILE__ ) . '/_data/img.png' );
		$this->assertTrue( is_wp_error( $data ) );
	}
}
