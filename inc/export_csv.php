<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Function to export car data to csv file
 *
 * @package Cardealer Helper Library
 */

if ( ! function_exists( 'cdhl_export_cars_to_csv' ) ) {
	/**
	 * Export cars to csv
	 *
	 * @param string $redirect_to .
	 * @param string $doaction .
	 * @param string $post_ids .
	 */
	function cdhl_export_cars_to_csv( $redirect_to, $doaction, $post_ids ) {
		if ( 'export_cars' !== $doaction ) {
			return $redirect_to;
		}
		if ( empty( $post_ids ) ) {
			return $redirect_to;
		}

		// get CarsDetail Array.
		$csv_data = cdhl_get_cars_detail( $post_ids, $redirect_to, $doaction );

		// Write data into csv file.
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=cardealer.csv' );
		$fp = fopen( 'php://output', 'wb' );
		end( $csv_data );
		$lastline = key( $csv_data );
		foreach ( $csv_data as $key => $fields ) {
			end( $fields );
			$last_field = key( $fields );
			foreach ( $fields as $k => $field ) {
				$field = str_replace( "\n", '', $field ); // Remove new line.
				$field = str_replace( "\r", '', $field ); // Remove new line.
				$field = str_replace( '"', "'", $field ); // Remove Quotes.
				if ( ( (string) $k === (string) $last_field ) ) {
					if ( strpos( $field, ',' ) !== false ) {
						fwrite( $fp, '"' . $field . '"' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					} else {
						fwrite( $fp, $field ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					}
				} else {
					if ( strpos( $field, ',' ) !== false ) {
						fwrite( $fp, '"' . $field . '",' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					} else {
						fwrite( $fp, $field . ',' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					}
				}
			}
			if ( $key !== $lastline ) {
				fwrite( $fp, PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
			}
		}
		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		die;
	}
}

if ( ! function_exists( 'cdhl_custom_export_cars_to_csv' ) ) {
	/**
	 * Step 2: handle the custom Bulk Action
	 * Added to solved version compatibility issue for versions < 4.7
	 * Added without using csv library
	 * Based on the post http://wordpress.stackexchange.com/questions/29822/custom-bulk-action
	 */
	function cdhl_custom_export_cars_to_csv() {
		global $typenow;
		$post_type = $typenow;
		if ( 'cars' === $post_type ) {
			// get the action.
			$wp_list_table   = _get_list_table( 'WP_Posts_List_Table' );  // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc.
			$action          = $wp_list_table->current_action();
			$allowed_actions = array( 'export_cars' );
			if ( ! in_array( $action, $allowed_actions, true ) ) {
				return;
			}
			// security check.
			check_admin_referer( 'bulk-posts' );
			// make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'.
			if ( isset( $_REQUEST['post'] ) ) {
				$post_ids = array_map( 'intval', $_REQUEST['post'] );
			}
			if ( empty( $post_ids ) ) {
				return;
			}

			// get CarsDetail Array.
			$redirect_to = wp_get_referer();
			$csv_data    = cdhl_get_cars_detail( $post_ids, $redirect_to, $allowed_actions[0] );

			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=cardealer.csv' );

			$fp = fopen( 'php://output', 'wb' );
			end( $csv_data );
			$lastline = key( $csv_data );
			foreach ( $csv_data as $key => $fields ) {
				end( $fields );
				$last_field = key( $fields );
				foreach ( $fields as $k => $field ) {
					$field = str_replace( "\n", '', $field ); // Remove new line.
					$field = str_replace( "\r", '', $field ); // Remove new line.
					$field = str_replace( '"', "'", $field ); // Remove Quotes.
					if ( ( (string) $k === (string) $last_field ) ) {
						if ( strpos( $field, ',' ) !== false ) {
							fwrite( $fp, '"' . $field . '"' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
						} else {
							fwrite( $fp, $field ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
						}
					} else {
						if ( strpos( $field, ',' ) !== false ) {
							fwrite( $fp, '"' . $field . '",' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
						} else {
							fwrite( $fp, $field . ',' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
						}
					}
				}
				if ( $key !== $lastline ) {
					// Add new line after one record.
					fwrite( $fp, PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite, Add new line after one record.
				}
			}
			fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			die;
		}
	}
}
