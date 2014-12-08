<?php

class AdvancedImporter_Test extends WP_UnitTestCase {

	public function testSample() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	/**
	 * @test
	 */
	public function csv_file()
	{
		$csv = new Keboola\Csv\CsvFile( dirname( __FILE__ ) . '/_data/simple.csv' );

		$data = array();
		foreach ( $csv as $row ) {
			$this->assertEquals( 2, count( $row ) ); // 3 columns
			$data[] = $row;
		}

		$this->assertEquals( 4, count( $data ) ); // 3 rows
	}

	/**
	 * @test
	 */
	public function csv_parser()
	{
		$data = \ACSV\Utils::csv_parser( dirname( __FILE__ ) . '/_data/simple.csv' );
		$this->assertEquals( 3, count( $data ) );

		$data = \ACSV\Utils::csv_parser( dirname( __FILE__ ) . '/_data/escaping.csv' );
		$this->assertEquals( "columns with\nnew line", $data[4]['col1'] );


		$data = \ACSV\Utils::csv_parser( 'file-not-exists' );
		$this->assertTrue( is_wp_error( $data ) );

		$data = \ACSV\Utils::csv_parser( dirname( __FILE__ ) . '/_data/img.png' );
		$this->assertTrue( is_wp_error( $data ) );
	}
}
