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
		$data = \ACSV\Utils::get_data( dirname( __FILE__ ) . '/_data/simple.csv' );
		$this->assertEquals( 3, count( $data ) );

		$data = \ACSV\Utils::get_data( dirname( __FILE__ ) . '/_data/escaping.csv' );
		$this->assertEquals( "columns with\nnew line", $data[4]['col1'] );


		$data = \ACSV\Utils::get_data( 'file-not-exists' );
		$this->assertTrue( is_wp_error( $data ) );

		$data = \ACSV\Utils::get_data( dirname( __FILE__ ) . '/_data/img.png' );
		$this->assertTrue( is_wp_error( $data ) );
	}
}
