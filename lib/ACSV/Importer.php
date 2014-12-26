<?php

namespace ACSV;

class Importer extends \WP_Importer {

	private $action = 'admin.php?import=advanced-csv-importer';

	public function dispatch()
	{
		$this->header();

		if ( empty( $_GET['step'] ) ) {
			$step = 1;
		} else {
			$step = (int) $_GET['step'];
		}

		switch ($step) {
			case 1:
				$this->step1();
				break;
			case 2:
				check_admin_referer( 'import-upload' );
				set_time_limit( 0 );
				$result = $this->step2();
				if ( is_wp_error( $result ) ) {
					echo $result->get_error_message();
				} else {
					$posts = Main::post_ids_to_posts( $result );
					echo '<p>All Done!</p>';
					echo '<form method="post">';
					echo '<table class="wp-list-table widefat fixed posts">';
					echo '<thead><tr style="color: #dedede;">';
					echo '<th scope="col" class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all-1" /></th><th scope="col">Title</th><th scope="col">Type</th><th scope="col">Status</th><th scope="col">Date</th>';
					echo '</tr></thead>';
					foreach ( $posts as $p ) {
						printf(
							'<tr><th scope="row" class="check-column"><input type="checkbox" name="acsv-import-id" value="%s" /></th><td class="post-title page-title column-title">%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
							$p['ID'],
							$p['Title'],
							$p['Type'],
							$p['Status'],
							$p['Date']
						);
					}
					echo '</table>';
					echo '</form>';
				}
				break;
		}

		$this->footer();
	}

	/**
	 * The form of the second step.
	 *
	 * @param  none
	 * @return none
	 */
	private function step2()
	{
		$file = wp_import_handle_upload();

		if ( isset( $file['error'] ) ) {
			return new WP_Error( 'Error', esc_html( $file['error'] ) );
		} else if ( ! file_exists( $file['file'] ) ) {
			return new WP_Error( 'Error', sprintf( __( 'The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.', 'advanced-csv-importer' ), esc_html( $file['file'] ) ) );
		}

		$csv_file = get_attached_file( $file['id'] );
		$post_objects = Main::parse_csv_to_post_objects( $csv_file );

		if ( is_wp_error( $post_objects ) ) {
			echo '<p><strong>'.__( 'Failed to open file.', 'advanced-csv-importer' ).'</strong></p>';
			wp_import_cleanup( $file['id'] );
			return $post_objects;
		} else {
			$inserted_posts = Main::insert_posts( $post_objects );
			wp_import_cleanup( $file['id'] );
			return $inserted_posts;
		}
	}

	/**
	 * The form of the first step.
	 *
	 * @param  none
	 * @return none
	 */
	private function step1()
	{
		$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size = size_format( $bytes );
		$upload_dir = wp_upload_dir();

		echo '<div class="narrow">';

		if ( ! empty( $upload_dir['error'] ) ) {
			?>
			<div class="error">
				<p><?php _e( 'Before you can upload your import file, you will need to fix the following error:', 'advanced-csv-importer' ); ?></p>
				<p><strong><?php echo $upload_dir['error']; ?></strong></p>
			</div>
			<?php
			return;
		}

		?>
		<form enctype="multipart/form-data" id="import-upload-form" method="post" class="wp-upload-form" action="<?php echo esc_url( wp_nonce_url( $this->get_action() . '&step=2', 'import-upload' ) ); ?>">
			<p>
				<label for="upload"><?php _e( 'Choose a .csv file from your computer.', 'advanced-csv-importer' ); ?></label><br />(<?php printf( __('Maximum size: %s' ), $size ); ?>)
			</p>
			<p>
				<input type="file" id="upload" name="import" size="25" />
				<input type="hidden" name="action" value="save" />
				<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
			</p>
			<?php submit_button( __('Upload file and import'), 'advanced-csv-importer' ); ?>
		</form>
		<?php

		echo '</div>';
	}

	/**
	 * Open the div for upload interface.
	 *
	 * @param  none
	 * @return none
	 */
	private function header()
	{
		echo '<div class="wrap">';
		screen_icon();
		echo '<h2>' . __( 'Advanced CSV Importer', 'advanced-csv-importer' ) . '</h2>';
		$updates = get_plugin_updates();
		$basename = plugin_basename( __FILE__ );
		if ( isset( $updates[ $basename ] ) ) {
			$update = $updates[ $basename ];
			echo '<div class="error"><p><strong>';
			printf( __( 'A new version of this importer is available. Please update to version %s to ensure compatibility with newer export files.', 'advanced-csv-importer' ), $update->update->new_version );
			echo '</strong></p></div>';
		}
	}

	/**
	 * Close the div for upload interface.
	 *
	 * @param  none
	 * @return none
	 */
	private function footer()
	{
		echo '</div>';
	}

	/**
	 * Returns the uri for upload
	 *
	 * @param  none
	 * @return The uri for upload.
	 */
	private function get_action()
	{
		return $this->action;
	}
}
