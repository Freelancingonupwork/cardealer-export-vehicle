<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Code to add FTP NOW? checkbox on car post type page
 *
 * @package Cardealer Helper Library
 */

add_action( 'admin_head-edit.php', 'cdhl_add_custom_ftp_checkbox' );
if ( ! function_exists( 'cdhl_add_custom_ftp_checkbox' ) ) {
	/**
	 * Add Custom Ftp Checkbox
	 */
	function cdhl_add_custom_ftp_checkbox() {
		global $current_screen;
		if ( 'cars' !== $current_screen->post_type ) {
			return;
		}
		?>
			<script type="text/javascript">
				jQuery(document).ready( function($) {
					jQuery('#doaction').before("<div id='ftp_now' class='alignleft actions' style='display:none'><input type='checkbox' name='ftp_now'>FTP Now?</div>");
				});
			</script>
		<?php
	}
}

add_action( 'admin_menu', 'cdhl_cardealer_export_log_menu' );
if ( ! function_exists( 'cdhl_cardealer_export_log_menu' ) ) {
	/**
	 * Export log menu
	 */
	function cdhl_cardealer_export_log_menu() {
		add_submenu_page(
			'log-list',
			esc_html__( 'Export Log', 'cardealer-export-vehicle' ),
			esc_html__( 'Export Log', 'cardealer-export-vehicle' ),
			'manage_options',
			'log-list',
			'cdhl_get_log_data'
		);
	}
}

if ( ! function_exists( 'cdhl_get_log_data' ) ) {
	/**
	 * Get log data
	 */
	function cdhl_get_log_data() {
		// Datatable Style.
		wp_register_style( 'export_log_dataTable_css', trailingslashit( CDEV_URL ) . 'css/export_log/jquery.dataTables.min.css', array(), '1.10.2' );
		wp_enqueue_style( 'export_log_dataTable_css' );

		// Datatable Script.
		wp_register_script( 'export_log_dataTable', trailingslashit( CDEV_URL ) . 'js/export_log/jquery.dataTables.min.js', array(), true, '1.10.2' );
		wp_register_script( 'export_log_dataTable_custom', trailingslashit( CDEV_URL ) . 'js/export_log/custom_dataTables.js', array(), true, '1.10.2' );
		wp_register_script( 'jquery-1.12.4', trailingslashit( CDEV_URL ) . 'js/export_log/jquery-1.12.4.js', array(), true, '1.12.4' );

		wp_enqueue_script( 'export_log_dataTable' );
		wp_enqueue_script( 'export_log_dataTable_custom' );
		wp_enqueue_script( 'cdev-export-vehicle' );
		?>
		<div class="table_area">
			<div class="wrap">
				<div class="msg"></div>
				<h2><?php esc_html_e( 'Vehicles Export Log List', 'cardealer-export-vehicle' ); ?></h2>
				<table id="export-log" class="display" width="100%" cellspacing="0">
					<thead>
						<tr>
							<th><?php esc_html_e( 'File Name', 'cardealer-export-vehicle' ); ?></th>
							<th><?php esc_html_e( 'Export To', 'cardealer-export-vehicle' ); ?></th>
							<th><?php esc_html_e( 'FTP Done', 'cardealer-export-vehicle' ); ?></th>
							<th><?php esc_html_e( 'FTP Done On', 'cardealer-export-vehicle' ); ?></th>
							<th><?php esc_html_e( 'Created', 'cardealer-export-vehicle' ); ?></th>
							<th><?php esc_html_e( 'Action', 'cardealer-export-vehicle' ); ?></th>
						</tr>
					</thead>
				</table>
			</div>
		</div>
		<?php
	}
}
add_action( 'wp_ajax_nopriv_get_export_log', 'cdhl_get_export_log' );
add_action( 'wp_ajax_get_export_log', 'cdhl_get_export_log' );
if ( ! function_exists( 'cdhl_get_export_log' ) ) {
	/**
	 * Get export log
	 */
	function cdhl_get_export_log() {
		$result        = array();
		$records_total = 101;

		// Check search.
		$search = isset( $_REQUEST['search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$start  = isset( $_REQUEST['start'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['start'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$length = isset( $_REQUEST['length'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['length'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$term   = isset( $search['value'] ) ? $search['value'] : '';

		if ( empty( $term ) ) {
			$term = '';
		}

		$order  = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$column = isset( $order[0]['column'] ) ? $order[0]['column'] : '';

		if ( empty( $column ) ) {
			$orderby = 'created';
		}

		$mode = ( ! empty( $order[0]['dir'] ) ) ? $order[0]['dir'] : 'desc';

		if ( 1 === (int) $column ) {
			$orderby = 'file_name';
		} elseif ( 2 === (int) $column ) {
			$orderby = 'export_to';
		} elseif ( 3 === (int) $column ) {
			$orderby = 'ftp_done';
		} elseif ( 4 === (int) $column ) {
			$orderby = 'ftp_done_on';
		} elseif ( 5 === (int) $column ) {
			$orderby = 'created';
		}

		$result['recordsTotal']    = cdhl_log_count( $term );
		$result['recordsFiltered'] = cdhl_log_count( $term );
		$datas                     = cdhl_search_log( $term, $start, $length, $orderby, $mode );
		$data                      = array();
		$i                         = 0;
		if ( $datas ) {
			foreach ( $datas as $d ) {
				$up_dir_url   = wp_upload_dir();
				$data[ $i ][] = "<a href='" . $up_dir_url['baseurl'] . '/cars/exported/' . $d['file_name'] . "' download >" . $d['file_name'] . '</a>';
				$data[ $i ][] = $d['export_to'];
				$data[ $i ][] = $d['ftp_done'];
				$data[ $i ][] = $d['ftp_done_on'];
				$data[ $i ][] = $d['created'];
				$data[ $i ][] = ( 'True' === (string) $d['ftp_done'] ) ? '<a href="?log_id=' . $d['log_id'] . '" class="btn btn-info">' . esc_html__( 'Resend FTP', 'cardealer-export-vehicle' ) . '</a>' : '<a href="?log_id=' . $d['log_id'] . '" class="btn btn-primary">' . esc_html__( 'Send FTP', 'cardealer-export-vehicle' ) . '</a>';
				$i++;
			}
		}
		$result['data'] = $data;
		echo wp_json_encode( $result ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		die;

	}
}
if ( ! function_exists( 'cdhl_log_count' ) ) {
	/**
	 * Log count
	 *
	 * @param string $term .
	 */
	function cdhl_log_count( $term ) {
		global $wpdb;
		$sql             = "SELECT count(DISTINCT pm.post_id) FROM $wpdb->postmeta pm JOIN $wpdb->posts p ON (p.ID = pm.post_id) WHERE p.post_type = 'pgs_export_log'";
		$result['total'] = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		return $result['total'];
	}
}
if ( ! function_exists( 'cdhl_search_log' ) ) {
	/**
	 * Search log
	 *
	 * @param string $term .
	 * @param int    $start .
	 * @param int    $length .
	 * @param string $orderby .
	 * @param string $mode .
	 */
	function cdhl_search_log( $term, $start = 0, $length = 50, $orderby = 'created', $mode = 'asc' ) {
		if ( 'created' === $orderby ) {
			$args = array(
				'post_type'      => 'pgs_export_log',
				'orderby'        => 'meta_value',
				'meta_key'       => $orderby,
				'order'          => $mode,
				'posts_per_page' => $length,
			);
		} else {
			$args = array(
				'post_type'      => 'pgs_export_log',
				'posts_per_page' => $length,
			);
		}
		$log_query = new WP_Query( $args );
		$result    = array();
		$cnt       = 0;
		while ( $log_query->have_posts() ) :
			$log_query->the_post();
			$up_dir_url                    = wp_upload_dir();
			$result[ $cnt ]['log_id']      = get_the_ID();
			$result[ $cnt ]['file_name']   = get_post_meta( get_the_ID(), 'file_name', true );
			$result[ $cnt ]['export_to']   = get_post_meta( get_the_ID(), 'export_to', true );
			$result[ $cnt ]['ftp_done']    = get_post_meta( get_the_ID(), 'ftp_done', true );
			$result[ $cnt ]['ftp_done_on'] = get_post_meta( get_the_ID(), 'ftp_done_on', true );
			$result[ $cnt ]['created']     = get_post_meta( get_the_ID(), 'created', true );
			$cnt++;
		endwhile;
		return $result;
	}
}

global $wp_version;
$log_id = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
if ( isset( $log_id ) && ! empty( $log_id ) ) {
	if ( $wp_version >= 4.7 ) {
		add_action( 'admin_init', 'cdhl_send_file' );
	} else {
		add_action( 'admin_init', 'cdhl_send_file_custom' );
	}
}

if ( ! function_exists( 'cdhl_send_file' ) ) {
	/**
	 * Send File
	 */
	function cdhl_send_file() {
		global $car_dealer_options;
		$log_id      = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$third_party = get_post_meta( $log_id, 'export_to', true );
		$filename    = get_post_meta( $log_id, 'file_name', true );
		$post_ids    = get_post_meta( $log_id, 'posts', true );
		$post_ids    = explode( ',', $post_ids );

		$redirect_to = wp_get_referer();
		$source      = ABSPATH . 'wp-content/uploads/cars/exported/';

		// Check ftp module is enable on server.
		if ( ! function_exists( 'ftp_connect' ) ) {
			wp_safe_redirect( $redirect_to . '&car_notice=7' );
			die;
		}
		if ( 'Auto Trader' === $third_party ) {
			$connection    = ftp_connect( $car_dealer_options['ftp_host'] );
			$ftp_user_name = $car_dealer_options['username'];
			$ftp_user_pass = $car_dealer_options['password'];
			$login         = ftp_login( $connection, $ftp_user_name, $ftp_user_pass );
			ftp_pasv( $connection, true );
			if ( ! $connection || ! $login ) {
				wp_safe_redirect( $redirect_to . '&car_notice=4' );
				die;}

			// Code for get location of file to export.
			if ( ! isset( $car_dealer_options['file_location'] ) || empty( $car_dealer_options['file_location'] ) ) {
				$car_dealer_options['file_location'] = '';
			} elseif ( false === (bool) cdhl_ftp_is_dir( $connection, $car_dealer_options['file_location'] ) ) {
				wp_safe_redirect( $redirect_to . '&car_notice=6' );
				die;
			}

			$file     = basename( $source . $filename, '.txt' );
			$location = $car_dealer_options['file_location']; // imgs file.
			if ( substr_count( $file, '_' ) > 1 ) {
				$file = substr( $file, 0, strpos( $file, '_', strpos( $file, '_' ) + 1 ) );
			}

			// Send file.
			$dest_file = $file . '.txt';
			$upload    = ftp_put( $connection, $location . '/' . $dest_file, $source . $filename, FTP_BINARY );

			ftp_close( $connection );
			if ( ! $upload ) {
				wp_safe_redirect( $redirect_to . '&car_notice=3' );
				die;
			} else {
				$new_file = $file . '_' . time() . '.txt';
				$log_id   = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

				if ( update_post_meta( $log_id, 'file_name', $new_file, $filename ) ) {
					update_post_meta( $log_id, 'ftp_done', 'True' );
					update_post_meta( $log_id, 'ftp_done_on', gmdate( 'y-m-d H:i:s' ) );
					rename( $source . $filename, $source . $new_file );
				}

				if ( ! empty( $post_ids ) ) {
					foreach ( $post_ids as $post ) {
						update_post_meta( $post, 'auto_trader', 'yes' );
						update_post_meta( $post, 'auto_export_date', gmdate( 'Y-m-d H:i:s' ) );
					}
				}
				wp_safe_redirect( $redirect_to . '&car_notice=2' );
				die;
			}
		} else {
			$connection    = ftp_connect( $car_dealer_options['car_ftp_host'] );
			$ftp_user_name = $car_dealer_options['car_username'];
			$ftp_user_pass = $car_dealer_options['car_password'];
			$login         = ftp_login( $connection, $ftp_user_name, $ftp_user_pass );

			ftp_pasv( $connection, true );
			if ( ! $connection || ! $login ) {
				wp_safe_redirect( $redirect_to . '&car_notice=4' );
				die;}

			// Code for get location of file to export.
			if ( ! isset( $car_dealer_options['car_file_location'] ) || empty( $car_dealer_options['car_file_location'] ) ) {
				ftp_mkdir( $connection, $car_dealer_options['car_dealer_id'] );
				$car_dealer_options['car_file_location'] = '/' . $car_dealer_options['car_dealer_id'];
			} elseif ( false === (bool) cdhl_ftp_is_dir( $connection, $car_dealer_options['car_file_location'] ) ) {
				wp_safe_redirect( $redirect_to . '&car_notice=6' );
				die;
			}

			$file = basename( $source . $filename, '.txt' );
			if ( strpos( $filename, $car_dealer_options['car_dealer_id'] ) !== false ) {
				// Create directory on third party location for datafile.
				if ( false === (bool) cdhl_ftp_is_dir( $connection, $car_dealer_options['car_file_location'] . '/' . $car_dealer_options['car_dealer_id'] ) ) {
					ftp_mkdir( $connection, $car_dealer_options['car_file_location'] . '/' . $car_dealer_options['car_dealer_id'] );
				}
				$location = $car_dealer_options['car_file_location'] . '/' . $car_dealer_options['car_dealer_id']; // data file.
				if ( substr_count( $file, '_' ) > 0 ) {
					$file = substr( $file, 0, strpos( $file, '_', strpos( $file, '_' ) ) );
				}
			} else {
				$location = $car_dealer_options['car_file_location']; // imgs file.
				if ( substr_count( $file, '_' ) > 1 ) {
					$file = substr( $file, 0, strpos( $file, '_', strpos( $file, '_' ) + 1 ) );
				}
			}
			// Send file.
			$dest_file = $file . '.txt';
			$upload    = ftp_put( $connection, $location . '/' . $dest_file, $source . $filename, FTP_BINARY );
			ftp_close( $connection );
			if ( ! $upload ) {
				wp_safe_redirect( $redirect_to . '&car_notice=3' );
				die;
			} else {
				$new_file = $file . '_' . time() . '.txt';
				$log_id   = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				if ( update_post_meta( $log_id, 'file_name', $new_file, $filename ) ) {
					update_post_meta( $log_id, 'ftp_done', 'True' );
					update_post_meta( $log_id, 'ftp_done_on', gmdate( 'y-m-d H:i:s' ) );
					rename( $source . $filename, $source . $new_file );
				}
				if ( ! empty( $post_ids ) ) {
					foreach ( $post_ids as $post ) {
						update_post_meta( $post, 'cars_com', 'yes' );
						update_post_meta( $post, 'cars_com_export_date', gmdate( 'Y-m-d H:i:s' ) );
					}
				}
				wp_safe_redirect( $redirect_to . '&car_notice=2' );
				die;
			}
		}
	}
}
if ( ! function_exists( 'cdhl_send_file_custom' ) ) {
	/**
	 * Send File Custom
	 */
	function cdhl_send_file_custom() {
		global $car_dealer_options;
		$log_id      = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$third_party = get_post_meta( $log_id, 'export_to', true );
		$filename    = get_post_meta( $log_id, 'file_name', true );
		$post_ids    = get_post_meta( $log_id, 'posts', true );
		$post_ids    = explode( ',', $post_ids );

		$redirect_to = wp_get_referer();
		$source      = ABSPATH . 'wp-content/uploads/cars/exported/';

		// Check ftp module is enable on server.
		if ( ! function_exists( 'ftp_connect' ) ) {
			wp_safe_redirect( $redirect_to . '&car_notice=7' );
			die;
		}
		if ( 'Auto Trader' === $third_party ) {
			$connection = ftp_connect( $car_dealer_options['ftp_host'] );

			$ftp_user_name = $car_dealer_options['username'];
			$ftp_user_pass = $car_dealer_options['password'];
			$login         = ftp_login( $connection, $ftp_user_name, $ftp_user_pass );
			ftp_pasv( $connection, true );
			if ( ! $connection || ! $login ) {
				wp_safe_redirect( $redirect_to . '&car_notice=4' );
				die; }

			// Code for get location of file to export.
			if ( ! isset( $car_dealer_options['file_location'] ) || empty( $car_dealer_options['file_location'] ) ) {
				$car_dealer_options['file_location'] = '';
			} elseif ( false === (bool) cdhl_ftp_is_dir( $connection, $car_dealer_options['file_location'] ) ) {
				wp_safe_redirect( $redirect_to . '&car_notice=6' );
				die;
			}

			$file     = basename( $source . $filename, '.txt' );
			$location = $car_dealer_options['file_location']; // imgs file.
			if ( substr_count( $file, '_' ) > 1 ) {
				$file = substr( $file, 0, strpos( $file, '_', strpos( $file, '_' ) + 1 ) );
			}

			// Send file.
			$dest_file = $file . '.txt';
			$upload    = ftp_put( $connection, $location . '/' . $dest_file, $source . $filename, FTP_BINARY );

			ftp_close( $connection );
			if ( ! $upload ) {
				wp_safe_redirect( $redirect_to . '&car_notice=3' );
				die;
			} else {
				$new_file = $file . '_' . time() . '.txt';
				$log_id   = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				if ( update_post_meta( $log_id, 'file_name', $new_file, $filename ) ) {
					update_post_meta( $log_id, 'ftp_done', 'True' );
					update_post_meta( $log_id, 'ftp_done_on', gmdate( 'y-m-d H:i:s' ) );
					rename( $source . $filename, $source . $new_file );
				}

				if ( ! empty( $post_ids ) ) {
					foreach ( $post_ids as $post ) {
						update_post_meta( $post, 'auto_trader', 'yes' );
						update_post_meta( $post, 'auto_export_date', gmdate( 'Y-m-d H:i:s' ) );
					}
				}
				wp_safe_redirect( $redirect_to . '&car_notice=2' );
				die;
			}
		} else {
			$connection    = ftp_connect( $car_dealer_options['car_ftp_host'] );
			$ftp_user_name = $car_dealer_options['car_username'];
			$ftp_user_pass = $car_dealer_options['car_password'];
			$login         = ftp_login( $connection, $ftp_user_name, $ftp_user_pass );
			ftp_pasv( $connection, true );
			if ( ! $connection || ! $login ) {
				wp_safe_redirect( $redirect_to . '&car_notice=4' );
				die; }

			// Code for get location of file to export.
			if ( ! isset( $car_dealer_options['car_file_location'] ) || empty( $car_dealer_options['car_file_location'] ) ) {
				ftp_mkdir( $connection, $car_dealer_options['car_dealer_id'] );
				$car_dealer_options['car_file_location'] = '/' . $car_dealer_options['car_dealer_id'];
			} elseif ( false === (bool) cdhl_ftp_is_dir( $connection, $car_dealer_options['car_file_location'] ) ) {
				wp_safe_redirect( $redirect_to . '&car_notice=6' );
				die;
			}

			$file = basename( $source . $filename, '.txt' );
			if ( strpos( $filename, $car_dealer_options['car_dealer_id'] ) !== false ) {
				// Create directory on third party location for datafile.
				if ( false === (bool) cdhl_ftp_is_dir( $connection, $car_dealer_options['car_file_location'] . '/' . $car_dealer_options['car_dealer_id'] ) ) {
					ftp_mkdir( $connection, $car_dealer_options['car_file_location'] . '/' . $car_dealer_options['car_dealer_id'] );
				}
				$location = $car_dealer_options['car_file_location'] . '/' . $car_dealer_options['car_dealer_id']; // data file.
				if ( substr_count( $file, '_' ) > 0 ) {
					$file = substr( $file, 0, strpos( $file, '_', strpos( $file, '_' ) ) );
				}
			} else {
				$location = $car_dealer_options['car_file_location']; // imgs file.
				if ( substr_count( $file, '_' ) > 1 ) {
					$file = substr( $file, 0, strpos( $file, '_', strpos( $file, '_' ) + 1 ) );
				}
			}
			// Send file.
			$dest_file = $file . '.txt';
			$upload    = ftp_put( $connection, $location . '/' . $dest_file, $source . $filename, FTP_BINARY );
			ftp_close( $connection );
			if ( ! $upload ) {
				wp_safe_redirect( $redirect_to . '&car_notice=3' );
				die;
			} else {
				$new_file = $file . '_' . time() . '.txt';
				$log_id   = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				if ( update_post_meta( $log_id, 'file_name', $new_file, $filename ) ) {
					update_post_meta( $log_id, 'ftp_done', 'True' );
					update_post_meta( $log_id, 'ftp_done_on', gmdate( 'y-m-d H:i:s' ) );
					rename( $source . $filename, $source . $new_file );
				}
				if ( ! empty( $post_ids ) ) {
					foreach ( $post_ids as $post ) {
						update_post_meta( $post, 'cars_com', 'yes' );
						update_post_meta( $post, 'cars_com_export_date', gmdate( 'Y-m-d H:i:s' ) );
					}
				}
				wp_safe_redirect( $redirect_to . '&car_notice=2' );
				die;
			}
		}
	}
}
