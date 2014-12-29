<?php

namespace ACSV;

use \WP_Error;

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
				if ( isset( $_GET['import-id'] ) ) {
					$inserted_posts = History::get_imported_post_ids( $_GET['import-id'] );
				} else {
					check_admin_referer( 'acsv-import-upload' );
					set_time_limit( 0 );
					$inserted_posts = $this->step2();
				}
				if ( is_wp_error( $inserted_posts ) ) {
					echo '<p>' . $inserted_posts->get_error_message() . '</p>';
				} else {
					self::delete_form( $inserted_posts );
				}
				break;
			case 3:
				check_admin_referer( 'acsv-import-delete' );
				if ( isset( $_POST['acsv-import-id'] ) && count( $_POST['acsv-import-id'] ) ) {
					foreach ( $_POST['acsv-import-id'] as $post_id ) {
						if ( intval( $post_id ) ) {
							wp_delete_post( $post_id, false );
						}
					}
					echo '<p>';
					if ( count( $_POST['acsv-import-id'] ) === 1 ) {
						echo '1 post moved to the Trash.';
					} else {
						echo count( $_POST['acsv-import-id'] ) . ' posts moved to the Trash.';
					}
					echo '</p>';
				} else {
					echo '<p>Nothing to do.</p>';
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
		$post_objects = Main::get_post_objects( $csv_file );

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
		$bytes = apply_filters( 'acsv_import_upload_size_limit', wp_max_upload_size() );
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
		<form enctype="multipart/form-data" id="import-upload-form" method="post" class="wp-upload-form" action="<?php echo esc_url( add_query_arg( array( 'step' => 2 ), $this->get_action() ) ); ?>">
			<p>
				<label for="upload"><?php _e( 'Choose a .csv file from your computer.', 'advanced-csv-importer' ); ?></label><br />(<?php printf( __('Maximum size: %s' ), $size ); ?>)
			</p>
			<p>
				<input type="file" id="upload" name="import" size="25" />
				<input type="hidden" name="action" value="save" />
				<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
				<?php wp_nonce_field( 'acsv-import-upload' ); ?>
			</p>
			<?php submit_button( __('Upload file and import'), 'advanced-csv-importer' ); ?>
		</form>
		<h3>History</h3>
		<?php

		$history = History::get_history();
		?>
		<table class="wp-list-table widefat fixed posts">
			<thead><tr style="color: #dedede;">
				<th scope="col" style="width: 15%;">ID</th>
				<th scope="col" style="">Title</th>
				<th scope="col" style="">Date</th>
				<th scope="col" style="width: 15%;">Success</th>
				<th scope="col" style="width: 15%;">Failure</th>
			</tr></thead>
		<?php foreach ( $history as $log ) : ?>
			<tr style="color: #dedede;">
				<td><a href="<?php echo add_query_arg( array( 'step' => 2, 'import-id' => $log['ID'] ), admin_url( 'admin.php?import=advanced-csv-importer' ) ); ?>"><?php echo esc_html( $log['ID'] ); ?></a></td>
				<td><?php echo esc_html( $log['Title'] ); ?></td>
				<td><?php echo esc_html( $log['Date'] ); ?></td>
				<td><?php echo esc_html( $log['Success'] ); ?></td>
				<td><?php echo esc_html( $log['Failure'] ); ?></td>
			</tr>
		<?php endforeach; ?>
		</table>
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

	private function delete_form( $inserted_posts )
	{
		$posts   = History::post_ids_to_posts( $inserted_posts );
		if ( ! $posts ) {
			echo '<p>Posts were already deleted.</p>';
			return;
		}

		$success = History::get_num_success( $inserted_posts );
		$fail    = History::get_num_fail( $inserted_posts );

		echo '<p>';
		if ( $success === 1 ) {
			echo $success . ' post imported. ';
		} else {
			echo $success . ' posts imported. ';
		}
		if ( $fail === 1 ) {
			echo $fail . ' post failed to import. ';
		} else {
			echo $fail . ' posts failed to import. ';
		}
		echo '</p>';

		echo '<form method="post" action="' . esc_url( add_query_arg( array( 'step' => 3 ), $this->get_action() ) ) . '">';
		wp_nonce_field( 'acsv-import-delete' );
		echo '<table class="wp-list-table widefat fixed posts">';
		echo '<thead><tr style="color: #dedede;">';
		echo '<th scope="col" class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all-1" /></th><th scope="col">Title</th><th scope="col">Type</th><th scope="col">Status</th><th scope="col">Author</th><th scope="col">Date</th>';
		echo '</tr></thead>';
		foreach ( $posts as $p ) {
			printf(
				'<tr><th scope="row" class="check-column"><input type="checkbox" name="acsv-import-id[]" value="%d" /></th><td class="post-title page-title column-title"><a href="post.php?post=%d&action=edit" target="_blank">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
				intval( $p['ID'] ),
				intval( $p['ID'] ),
				esc_html( $p['Title'] ),
				esc_html( $p['Type'] ),
				esc_html( $p['Status'] ),
				esc_html( $p['Author'] ),
				esc_html( $p['Date'] )
			);
		}
		echo '</table>';
		echo '<p style="text-align: right;"><input type="submit" name="submit" id="submit" class="button advanced-csv-importer" value="Move to Trash" /></p>';
		echo '</form>';
	}
}
