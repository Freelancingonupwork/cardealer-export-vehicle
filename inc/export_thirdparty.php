<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Function to export car data to autotrader
 *
 * @package Cardealer Helper Library
 */

if ( ! function_exists( 'cdhl_export_cars_to_autotrader' ) ) {
	/**
	 * Export cars to autotrader
	 *
	 * @param string $redirect_to .
	 * @param string $doaction .
	 * @param string $post_ids .
	 */
	function cdhl_export_cars_to_autotrader( $redirect_to, $doaction, $post_ids ) {
		global $car_dealer_options;

		$dealer_id = ( isset( $car_dealer_options['dealer_id'] ) && ! empty( $car_dealer_options['dealer_id'] ) ) ? $car_dealer_options['dealer_id'] : '';

		if ( empty( $dealer_id ) ) {
			return $redirect_to . '&car_notice=11';
		}

		$set_theme_options = 1;

		// Sent to third party or not.
		$send_ftp = ( isset( $_GET['ftp_now'] ) ) ? sanitize_text_field( wp_unslash( $_GET['ftp_now'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification

		if ( 'export_autotrader' !== $doaction ) {
			return $redirect_to;
		}

		if ( empty( $post_ids ) ) {
			return $redirect_to;
		}

		// Check theme options are set for auto_trader or not.
		if (
			empty( $car_dealer_options['ftp_host'] )
			|| empty( $car_dealer_options['username'] )
			|| empty( $car_dealer_options['password'] )
			|| empty( $dealer_id )
		) {
			$set_theme_options = 0;
		}

		// create source directory.
		if ( ! file_exists( ABSPATH . 'wp-content/uploads/cars/exported' ) ) {
			mkdir( ABSPATH . 'wp-content/uploads/cars/exported', 0777, true );
		}
		$source = ABSPATH . 'wp-content/uploads/cars/exported/';

		$file_name = array( 'USED' . $dealer_id . '_' . gmdate( 'mdy' ) . '.txt', 'NEW' . $dealer_id . '_' . gmdate( 'mdy' ) . '.txt' );

		if ( $send_ftp ) {
			// Check theme options are set for auto_trader or not.
			if ( 0 === (int) $set_theme_options ) {
				return $redirect_to . '&car_notice=5';
			}
			// Check ftp module is enable on server.
			if ( ! function_exists( 'ftp_connect' ) ) {
				return $redirect_to . '&car_notice=7';
			}
			$connection    = ftp_connect( $car_dealer_options['ftp_host'] );
			$ftp_user_name = $car_dealer_options['username'];
			$ftp_user_pass = $car_dealer_options['password'];
			$login         = ftp_login( $connection, $ftp_user_name, $ftp_user_pass );
			ftp_pasv( $connection, true );
			if ( ! $connection || ! $login ) {
				return $redirect_to . '&car_notice=4';
			}

			// Code for get location of file to export.
			if ( ! isset( $car_dealer_options['file_location'] ) || empty( $car_dealer_options['file_location'] ) ) {
				$car_dealer_options['file_location'] = '';
			} elseif ( false === (bool) cdhl_ftp_is_dir( $connection, $car_dealer_options['file_location'] ) ) {
				return $redirect_to . '&car_notice=6';
			}
		}

		// get CarsDetail Array.
		$cars_data      = cdhl_get_autrotrade_cars_detail( $post_ids, $redirect_to, $doaction );
		$cnt            = 0;
		$cars_id        = array();
		$numeric_fields = array( 'car_year', 'car_mileage', 'regular_price', 'sale_price' );

		foreach ( $cars_data as $car_type => $data ) {
			if ( empty( $data ) ) {
				continue;
			}
			$fp = fopen( $source . $file_name[ $cnt ], 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
			end( $data );
			$lastline     = key( $data );
			$file_written = 0;
			foreach ( $data as $key => $fields ) {
				end( $fields );
				$last_field = key( $fields );

				// Discard record if required fields are blank.
				$req_field    = array( 'car_vin_number', 'dealer_id', 'car_mileage', 'car_exterior_color' );
				$valid_record = 1;
				foreach ( $req_field as $r_field ) {
					$valid_record = 0;
					if ( isset( $fields[ $r_field ] ) && ! empty( $fields[ $r_field ] ) ) {
						$valid_record = 1;
					}
				}
				if ( 1 !== $valid_record ) {
					continue;
				}
				if ( 'old_car' === $car_type && empty( $fields['sale_price'] ) && empty( $fields['regular_price'] ) ) {
					continue;
				}

				foreach ( $fields as $k => $field ) {
					if ( 'carId' === $k ) {
						$cars_id[0][] = $field; // get car Id.
						continue; // Donot include carid in export file.
					}
					// Check number fields.
					if ( in_array( $k, $numeric_fields, true ) ) {
						if ( ! is_numeric( $field ) ) {
							$field = '';
						}
					}

					/* Code for final price */
					if ( 'old_car' === $car_type ) {
						if ( 'regular_price' === $k ) {
							$field = ( ! isset( $fields['sale_price'] ) || $fields['sale_price'] < 1 ) ? $fields['regular_price'] : $fields['sale_price']; // Values.
						}
						if ( 'sale_price' === $k ) {
							continue;
						}
					}

					$field = str_replace( "\n", '', $field ); // Remove new line.
					$field = str_replace( "\r", '', $field ); // Remove new line.
					$field = str_replace( '"', "'", $field ); // Remove Quotes.

					// will be used to set flag that file contains records, if no records, then file will not be generated.
					$file_written = 1;

					if ( ( (string) $k === (string) $last_field ) ) {
						fwrite( $fp, '"' . $field . '"' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					} else {
						fwrite( $fp, '"' . $field . '",' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					}
				}
				if ( $key !== $lastline ) {
					fwrite( $fp, "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
				}
			}
			fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

			// Remove generated file if no records found / validated.
			if ( 0 === (int) $file_written ) {
				unlink( $source . $file_name[ $cnt ] );
				$cnt++; // increment counter to get and generate next file.
				continue;
			}

			if ( $send_ftp ) {
				$upload[ $cnt ] = ftp_put( $connection, $car_dealer_options['file_location'] . '/' . $file_name[ $cnt ], $source . $file_name[ $cnt ], FTP_BINARY );
			}
			// Create log entry.
			$ftp_status = ( isset( $upload[ $cnt ] ) && 1 === (int) $upload[ $cnt ] ) ? esc_html__( 'True', 'cardealer-export-vehicle' ) : esc_html__( 'False', 'cardealer-export-vehicle' );

			// Code to check file already exist.
			$args     = array(
				'post_type'  => 'pgs_export_log',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array( 'value' => $file_name[ $cnt ] ),
				),
			);
			$my_query = new WP_Query( $args );
			if ( $my_query->have_posts() ) {
				while ( $my_query->have_posts() ) {
					$my_query->the_post();
					$post_id = get_the_ID();
				} // end while.
			} else // If file does not exist then insert.
			{
				$post_id = wp_insert_post(
					array(
						'post_type'    => 'pgs_export_log',
						'post_title'   => esc_html__( 'Auto Trader', 'cardealer-export-vehicle' ),
						'post_content' => '',
					)
				);
			}
			wp_reset_postdata();
			$cars_exported = '';
			if ( isset( $cars_id[0] ) ) {
				$cars_exported = ( ! empty( $cars_id[0] ) ) ? implode( ',', $cars_id[0] ) : '';
			}

			if ( $post_id ) {
				update_field( 'file_name', $file_name[ $cnt ], $post_id );
				update_field( 'export_to', esc_html__( 'Auto Trader', 'cardealer-export-vehicle' ), $post_id );
				update_field( 'ftp_done', $ftp_status, $post_id );
				( $send_ftp ) ? update_field( 'ftp_done_on', gmdate( 'y-m-d H:i:s' ), $post_id ) : update_field( 'ftp_done_on', '-', $post_id );
				update_field( 'created', gmdate( 'y-m-d H:i:s' ), $post_id );
				update_field( 'posts', $cars_exported, $post_id ); // insert car ids into log.
			}
			$cnt++;
		}

		// If no records exported.
		if ( ! isset( $file_written ) || 0 === (int) $file_written ) {
			return $redirect_to . '&car_notice=8';
		}

		if ( $send_ftp ) {
			// add entry into cars post type.
			if ( ! empty( $cars_id[0] ) ) {
				foreach ( $cars_id[0] as $car ) {
					update_field( 'auto_trader', 'yes', $car );
					update_field( 'auto_export_date', gmdate( 'Y/m/d H:i:s' ), $car );
				}
			}
			// Close ftp connection.
			ftp_close( $connection );
		}

		if ( ( ( isset( $upload[0] ) && ! $upload[0] ) || ( isset( $upload[1] ) && ! $upload[1] ) ) && $send_ftp ) {
			return $redirect_to . '&car_notice=3';
		} elseif ( 0 === (int) $set_theme_options ) {
			return 'admin.php?page=log-list&car_notice=9';
		} else {
			return 'admin.php?page=log-list&car_notice=2';
		}
		die;
	}
}

if ( ! function_exists( 'cdhl_custom_export_cars_to_autotrader' ) ) {
	/**
	 * Step 2: handle the custom autotrader
	 * Added to solved version compatibility issue for versions < 4.7
	 * Added without using csv library
	 * Based on the post http://wordpress.stackexchange.com/questions/29822/custom-bulk-action
	 */
	function cdhl_custom_export_cars_to_autotrader() {
		global $typenow, $car_dealer_options;

		$set_theme_options = 1;
		$redirect_to       = wp_get_referer();
		$post_type         = $typenow;

		if ( 'cars' === $post_type ) {
			// Get the action.
			$wp_list_table   = _get_list_table( 'WP_Posts_List_Table' );  // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc.
			$action          = $wp_list_table->current_action();
			$allowed_actions = array( 'export_autotrader' );
			$dealer_id       = ( isset( $car_dealer_options['dealer_id'] ) && ! empty( $car_dealer_options['dealer_id'] ) ) ? $car_dealer_options['dealer_id'] : '';

			if ( ! in_array( $action, $allowed_actions, true ) ) {
				return;
			}

			if ( empty( $dealer_id ) ) {
				return $redirect_to . '&car_notice=11';
			}

			// Send to third party or not.
			$send_ftp = ( isset( $_GET['ftp_now'] ) ) ? sanitize_text_field( wp_unslash( $_GET['ftp_now'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification

			if (
				empty( $car_dealer_options['ftp_host'] )
				|| empty( $car_dealer_options['username'] )
				|| empty( $car_dealer_options['password'] )
				|| empty( $dealer_id )
			) {
				$set_theme_options = 0;
			}

			// Create source directory.
			if ( ! file_exists( ABSPATH . 'wp-content/uploads/cars/exported' ) ) {
				mkdir( ABSPATH . 'wp-content/uploads/cars/exported', 0777, true );
			}
			$source = ABSPATH . 'wp-content/uploads/cars/exported/';

			$file_name = array( 'USED' . $dealer_id . '_' . gmdate( 'mdy' ) . '.txt', 'NEW' . $dealer_id . '_' . gmdate( 'mdy' ) . '.txt' );

			if ( $send_ftp ) {
				// Check theme options are set for auto_trader or not.
				if ( 0 === (int) $set_theme_options ) {
					wp_safe_redirect( $redirect_to . '&car_notice=5' );
					die;
				}

				// Check ftp module is enable on server.
				if ( ! function_exists( 'ftp_connect' ) ) {
					wp_safe_redirect( $redirect_to . '&car_notice=7' );
					die;
				}
				$connection    = ftp_connect( $car_dealer_options['ftp_host'] );
				$ftp_user_name = $car_dealer_options['username'];
				$ftp_user_pass = $car_dealer_options['password'];
				$login         = ftp_login( $connection, $ftp_user_name, $ftp_user_pass );
				ftp_pasv( $connection, true );
				if ( ! $connection || ! $login ) {
					wp_safe_redirect( $redirect_to . '&car_notice=4' );
					die; }

				// Code for get location of file to export.
				if ( ! isset( $car_dealer_options['file_location'] ) || empty( $car_dealer_options['file_location'] ) ) {
					$car_dealer_options['file_location'] = '/';
				} elseif ( false === (bool) cdhl_ftp_is_dir( $connection, $car_dealer_options['file_location'] ) ) {
					wp_safe_redirect( $redirect_to . '&car_notice=6' );
					die;
				}
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
			$cars_data   = cdhl_get_autrotrade_cars_detail( $post_ids, $redirect_to, $allowed_actions[0] );

			$cnt            = 0;
			$cars_id        = array();
			$numeric_fields = array( 'car_year', 'car_mileage', 'regular_price', 'sale_price' );
			foreach ( $cars_data as $car_type => $data ) {
				if ( empty( $data ) ) {
					continue;
				}
				$fp = fopen( $source . $file_name[ $cnt ], 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
				end( $data );
				$lastline     = key( $data );
				$file_written = 0;
				foreach ( $data as $key => $fields ) {
					end( $fields );
					$last_field = key( $fields );

					// Discard record if required fields are blank.
					$req_field    = array( 'car_vin_number', 'dealer_id', 'car_mileage', 'car_exterior_color' );
					$valid_record = 1;
					foreach ( $req_field as $r_field ) {
						if ( ! isset( $fields[ $r_field ] ) || empty( $fields[ $r_field ] ) ) {
							$valid_record = 0;
						}
					}
					if ( 1 !== $valid_record ) {
						continue;
					}
					if ( 'old_car' === $car_type && empty( $fields['sale_price'] ) && empty( $fields['regular_price'] ) ) {
						continue;
					}

					foreach ( $fields as $k => $field ) {
						if ( 'carId' === $k ) {
							$cars_id[0][] = $field; // get car Id.
							continue; // Donot include carid in export file.
						}
						// Check number fields.
						if ( in_array( $k, $numeric_fields, true ) ) {
							if ( ! is_numeric( $field ) ) {
								$field = '';
							}
						}

						/* Code for final price */
						if ( 'old_car' === $car_type ) {
							if ( 'regular_price' === $k ) {
								$field = ( ! isset( $fields['sale_price'] ) || $fields['sale_price'] < 1 ) ? $fields['regular_price'] : $fields['sale_price']; // Values.
							}
							if ( 'sale_price' === $k ) {
								continue;
							}
						}

						$field = str_replace( "\n", '', $field ); // Remove new line.
						$field = str_replace( "\r", '', $field ); // Remove new line.
						$field = str_replace( '"', "'", $field ); // Remove Quotes.
						// will be used to set flag that file contains records, if no records, then file will not be generated.
						$file_written = 1;

						if ( ( (string) $k === (string) $last_field ) ) {
							fwrite( $fp, '"' . $field . '"' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
						} else {
							fwrite( $fp, '"' . $field . '",' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
						}
					}
					if ( $key !== $lastline ) {
						fwrite( $fp, "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					}
				}
				fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

				// Remove generated file if no records found / validated.
				if ( 0 === (int) $file_written ) {
					unlink( $source . $file_name[ $cnt ] );
					$cnt++; // increment counter to get and generate next file.
					continue;
				}

				if ( $send_ftp ) {
					$upload[ $cnt ] = ftp_put( $connection, $car_dealer_options['file_location'] . '/' . $file_name[ $cnt ], $source . $file_name[ $cnt ], FTP_BINARY );
				}
				// Create log entry.
				$ftp_status = ( isset( $upload[ $cnt ] ) && 1 === (int) $upload[ $cnt ] ) ? esc_html__( 'True', 'cardealer-export-vehicle' ) : esc_html__( 'False', 'cardealer-export-vehicle' );

				// Code to check file already exist.
				$args     = array(
					'post_type'  => 'pgs_export_log',
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array( 'value' => $file_name[ $cnt ] ),
					),
				);
				$my_query = new WP_Query( $args );
				if ( $my_query->have_posts() ) {
					while ( $my_query->have_posts() ) {
						$my_query->the_post();
						$post_id = get_the_ID();
					} // end while.
				} else // If file does not exist then insert.
				{
					$post_id = wp_insert_post(
						array(
							'post_type'    => 'pgs_export_log',
							'post_title'   => esc_html__( 'Auto Trader', 'cardealer-export-vehicle' ),
							'post_content' => '',
						)
					);
				}
				wp_reset_postdata();
				$cars_exported = '';
				if ( isset( $cars_id[0] ) ) {
					$cars_exported = ( ! empty( $cars_id[0] ) ) ? implode( ',', $cars_id[0] ) : '';
				}

				if ( $post_id ) {
					update_field( 'file_name', $file_name[ $cnt ], $post_id );
					update_field( 'export_to', esc_html__( 'Auto Trader', 'cardealer-export-vehicle' ), $post_id );
					update_field( 'ftp_done', $ftp_status, $post_id );
					( $send_ftp ) ? update_field( 'ftp_done_on', gmdate( 'y-m-d H:i:s' ), $post_id ) : update_field( 'ftp_done_on', '-', $post_id );
					update_field( 'created', gmdate( 'y-m-d H:i:s' ), $post_id );
					update_field( 'posts', $cars_exported, $post_id ); // insert car ids into log.
				}
				$cnt++;
			}

			// If no records exported.
			if ( ! isset( $file_written ) || 0 === (int) $file_written ) {
				wp_safe_redirect( $redirect_to . '&car_notice=8' );
				die;
			}

			if ( $send_ftp ) {
				// add entry into cars post type.
				if ( ! empty( $cars_id[0] ) ) {
					foreach ( $cars_id[0] as $car ) {
						update_field( 'auto_trader', 'yes', $car );
						update_field( 'auto_export_date', gmdate( 'Y/m/d H:i:s' ), $car );
					}
				}
				// Close ftp connection.
				ftp_close( $connection );
			}

			if ( ( ( isset( $upload[0] ) && ! $upload[0] ) || ( isset( $upload[1] ) && ! $upload[1] ) ) && $send_ftp ) {
				wp_safe_redirect( $redirect_to . '&car_notice=3' );
				die;
			} elseif ( 0 === (int) $set_theme_options ) {
				wp_safe_redirect( $redirect_to . '&car_notice=9' );
				die;
			} else {
				wp_safe_redirect( 'admin.php?page=log-list&car_notice=2' );
				die;
			}
			die;
		}
	}
}

if ( ! function_exists( 'cdhl_get_autrotrade_cars_detail' ) ) {
	/**
	 * Get autotrade cars detail
	 *
	 * @param string $post_ids .
	 * @param string $redirect_to .
	 * @param string $action .
	 */
	function cdhl_get_autrotrade_cars_detail( $post_ids, $redirect_to, $action ) {
		global $car_dealer_options;
		$propertiy_data = cdhl_get_car_properties( $post_ids[0], $action );
		if ( empty( $propertiy_data ) ) {
			wp_redirect( $redirect_to . '&car_notice=1' ); // phpcs:ignore WordPress.Security.SafeRedirect
		}

		$car_labels = array(
			'old_car' => cdhl_autotrader_car_properties(),
			'new_car' => cdhl_autotrader_car_properties( 'new' ),
		);

		$cars_data = array();

		foreach ( $propertiy_data as $car_key => $data ) {
			$attr_heading = array();
			$counter      = 0;
			$car_data     = array();
			foreach ( $post_ids as $car_id ) {
				$car_properties = $data;
				// check car is new or second hand.
				if ( taxonomy_exists( 'car_condition' ) ) {
					$term = wp_get_post_terms( $car_id, 'car_condition' );
					if ( ! empty( $term ) ) {
						if ( ( 'new_car' === $car_key ) && ( false === (bool) strpos( strtolower( $term[0]->slug ), 'new' ) ) ) {
							continue;
						} elseif ( 'old_car' === $car_key ) {
							$slug = $term[0]->slug;
							if ( strpos( strtolower( $slug ), 'used' ) !== false || strpos( strtolower( $slug ), 'certified' ) !== false ) {
								// code goes here.
							} else {
								continue;
							}
						}
					} else {
						continue;
					}
				}
				foreach ( $car_properties as $key => $car ) {
					$row          = array();
					$row['carId'] = $car_id;

					if ( is_array( $car ) && ! empty( $car ) ) {
						foreach ( $car as $k => $car_attr ) {
							if ( taxonomy_exists( $k ) ) { // Texonomy fields.
								$term = wp_get_post_terms( $car_id, $k );
								if ( ! empty( $term ) ) {
									$json       = wp_json_encode( $term ); // Convert Json to Obj.
									$term       = json_decode( $json, true ); // Convert Obj to Array.
									$name_array = array_map(
										function ( $options ) {
											return $options['name'];
										},
										(array) $term
									); // get all name term array.
									$row[ $k ]  = ( true === (bool) cdhl_check_field_length( implode( ',', $name_array ), $k, 'auto_trader' ) ) ? implode( ',', $name_array ) : '';
								} else {
									$row[ $k ] = '';
								}
							} else {
								$attr = get_field( $k, $car_id );
								if ( ! empty( $attr ) ) {
									if ( 'car_images' === $k ) { // Car Image URLs.
										$img = array();
										foreach ( $attr as $imgs ) {
											$img[] = $imgs['url'];
										}
										$row[ $k ] = ( true === (bool) cdhl_check_field_length( implode( ',', $img ), $k, 'auto_trader' ) ) ? implode( ',', $img ) : '';
									} elseif ( 'video_link' === $k || 'msrp' === $k ) {
										$row[ $k ] = '';
									} else { // other fields.
										$field_data = str_replace( '"', '\'', $attr );
										$row[ $k ]  = ( true === (bool) cdhl_check_field_length( $field_data, $k, 'auto_trader' ) ) ? $field_data : '';
									}
								} elseif ( 'dealer_id' === $k ) {
									$row[ $k ] = ( true === (bool) cdhl_check_field_length( $car_dealer_options['dealer_id'], $k, 'auto_trader' ) ) ? $car_dealer_options['dealer_id'] : '';
								} elseif ( 'images' === $k ) { // Physical Images.
									$attr      = get_field( 'car_images', $car_id );
									$row[ $k ] = ''; // Set physical imgs as blank.
								} else {
									$row[ $k ] = '';
								}
							}
						}
					}
					$car_data[] = $row;
					$counter++;
				}
			}
			$cars_data[ $car_key ] = $car_data;
		}
		return $cars_data;
	}
}

if ( ! function_exists( 'cdhl_check_field_length' ) ) {
	/**
	 * Check field length
	 *
	 * @param string $field .
	 * @param string $key .
	 * @param string $export_to .
	 */
	function cdhl_check_field_length( $field, $key, $export_to = null ) {
		$exceed = array();
		$length = ( 'auto_trader' === $export_to ) ? cdhl_autotrader_car_field_length( $key ) : cdhl_car_com_field_length( $key );
		if ( '*' === $length || ( strlen( $field ) <= $length ) ) {
			return true;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'cdhl_export_cars_to_car_com' ) ) {
	/**
	 * Function to export car data to cars.com
	 *
	 * @param string $redirect_to .
	 * @param string $doaction .
	 * @param string $post_ids .
	 */
	function cdhl_export_cars_to_car_com( $redirect_to, $doaction, $post_ids ) {
		global $car_dealer_options;
		$set_theme_options = 1;

		$car_dealer_id = ( isset( $car_dealer_options['car_dealer_id'] ) && ! empty( $car_dealer_options['car_dealer_id'] ) ) ? $car_dealer_options['car_dealer_id'] : '';

		if ( empty( $car_dealer_id ) ) {
			return $redirect_to . '&car_notice=11';
		}

		// Send to third party or not.
		$send_ftp = ( isset( $_GET['ftp_now'] ) ) ? sanitize_text_field( wp_unslash( $_GET['ftp_now'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification

		if ( 'export_car_com' !== $doaction ) {
			return $redirect_to;
		}
		if ( empty( $post_ids ) ) {
			return $redirect_to;
		}
		if (
			empty( $car_dealer_options['car_ftp_host'] )
			|| empty( $car_dealer_options['car_username'] )
			|| empty( $car_dealer_options['car_password'] )
			|| empty( $car_dealer_id )
		) {
			$set_theme_options = 0;
		}

		// create source directory.
		if ( ! file_exists( ABSPATH . 'wp-content/uploads/cars/exported' ) ) {
			mkdir( ABSPATH . 'wp-content/uploads/cars/exported', 0777, true );
		}
		$source = ABSPATH . 'wp-content/uploads/cars/exported/';

		$file_name = array( $car_dealer_options['car_dealer_id'] . '.txt', 'imagelist_' . gmdate( 'Ymdhms' ) . '.txt' );

		if ( $send_ftp ) { // If checkbox is checked for sent file to third party.
			// Check theme options are set for cars.com or not.
			if ( 0 === (int) $set_theme_options ) {
				return $redirect_to . '&car_notice=5';
			}

			// Check ftp module is enable on server.
			if ( ! function_exists( 'ftp_connect' ) ) {
				return $redirect_to . '&car_notice=7';
			}
			// Ftp connection.
			$connection    = ftp_connect( $car_dealer_options['car_ftp_host'] );
			$ftp_user_name = $car_dealer_options['car_username'];
			$ftp_user_pass = $car_dealer_options['car_password'];
			$login         = ftp_login( $connection, $ftp_user_name, $ftp_user_pass );
			ftp_pasv( $connection, true );
			if ( ! $connection || ! $login ) {
				return $redirect_to . '&car_notice=4';
			}

			// Code for get location of file to export.
			if ( ! isset( $car_dealer_options['car_file_location'] ) || empty( $car_dealer_options['car_file_location'] ) ) {
				ftp_mkdir( $connection, $car_dealer_options['car_dealer_id'] );
				$car_dealer_options['car_file_location'] = '/' . $car_dealer_options['car_dealer_id'];
			} elseif ( false === (bool) cdhl_ftp_is_dir( $connection, $car_dealer_options['car_file_location'] ) ) {
				return $redirect_to . '&car_notice=6';
			}

			// Create directory on third party location for datafile.
			if ( false === (bool) cdhl_ftp_is_dir( $connection, $car_dealer_options['car_file_location'] . '/' . $car_dealer_options['car_dealer_id'] ) ) {
				ftp_mkdir( $connection, $car_dealer_options['car_file_location'] . '/' . $car_dealer_options['car_dealer_id'] );
			}
			$location[] = $car_dealer_options['car_file_location'] . '/' . $car_dealer_options['car_dealer_id']; // car detail file.
			$location[] = $car_dealer_options['car_file_location']; // imgs file.
		}

		// get CarsDetail Array.
		$cars_data      = cdhl_get_car_com_detail( $post_ids, $redirect_to, $doaction );
		$cnt            = 0;
		$cars_id        = array();
		$numeric_fields = array( 'car_year', 'sale_price', 'car_mileage' );
		foreach ( $cars_data as $c_key => $data ) {
			$delemeter = ( 'car_detail' === $c_key ) ? '|' : ',';
			$fp        = fopen( $source . $file_name[ $cnt ], 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
			end( $data );
			$lastline = key( $data );
			foreach ( $data as $key => $fields ) {
				end( $fields );
				$last_field = key( $fields );
				// Discard record if required fields are blank.
				if ( 'car_detail' === $c_key ) {
					$req_field    = array( 'car_vin_number', 'car_stock_number', 'car_year', 'car_make', 'car_model', 'car_condition' );
					$valid_record = 1;
					foreach ( $req_field as $r_field ) {
						if ( ! isset( $fields[ $r_field ] ) || empty( $fields[ $r_field ] ) ) {
							$valid_record = 0;
						}
					}
					if ( 1 !== $valid_record ) {
						continue;
					}
				}

				foreach ( $fields as $k => $field ) {
					if ( 'carId' === $k ) {
						$cars_id[0][] = $field; // get car Id.
						continue; // Do not include carid in export file.
					}

					// Check number fields.
					if ( in_array( $k, $numeric_fields, true ) && 'car_detail' === $c_key ) {
						if ( ! is_numeric( $field ) && 0 !== (int) $key ) {
							$field = '';
						}
					}
					if ( 'car_condition' === $k && 'Certified' === $field ) { // set Certified as Used.
						$field = 'Used';
					}
					if ( ( (string) $k === (string) $last_field ) ) {
						fwrite( $fp, $field ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					} else {
						fwrite( $fp, $field . $delemeter ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					}
				}
				if ( $key !== $lastline ) {
					fwrite( $fp, "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
				}
			}
			fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

			if ( $send_ftp ) {
				$upload[ $cnt ] = ftp_put( $connection, $location[ $cnt ] . '/' . $file_name[ $cnt ], $source . $file_name[ $cnt ], FTP_BINARY );
			}
			// Create log entry.
			$ftp_status = ( isset( $upload[ $cnt ] ) && 1 === (int) $upload[ $cnt ] ) ? esc_html__( 'True', 'cardealer-export-vehicle' ) : esc_html__( 'False', 'cardealer-export-vehicle' );

			// Code to check file already exist.
			$args     = array(
				'post_type'  => 'pgs_export_log',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array( 'value' => $file_name[ $cnt ] ),
				),
			);
			$my_query = new WP_Query( $args );
			if ( $my_query->have_posts() ) {
				while ( $my_query->have_posts() ) {
					$my_query->the_post();
					$post_id = get_the_ID();
				} // end while.
			} else // If file does not exist then insert.
			{
				$post_id = wp_insert_post(
					array(
						'post_type'    => 'pgs_export_log',
						'post_title'   => esc_html__( 'Car.com', 'cardealer-export-vehicle' ),
						'post_content' => '',
					)
				);
			}
			wp_reset_postdata();
			$cars_exported = '';
			if ( isset( $cars_id[0] ) ) {
				$cars_exported = ( ! empty( $cars_id[0] ) ) ? implode( ',', $cars_id[0] ) : '';
			}
			if ( $post_id ) {
				update_field( 'file_name', $file_name[ $cnt ], $post_id );
				update_field( 'export_to', esc_html__( 'Cars.com', 'cardealer-export-vehicle' ), $post_id );
				update_field( 'ftp_done', $ftp_status, $post_id );
				( $send_ftp ) ? update_field( 'ftp_done_on', gmdate( 'y-m-d H:i:s' ), $post_id ) : update_field( 'ftp_done_on', '-', $post_id );
				update_field( 'created', gmdate( 'y-m-d H:i:s' ), $post_id );
				update_field( 'posts', $cars_exported, $post_id );
			}
			$cnt++;
		}

		if ( $send_ftp ) {
			// add entry into cars post type.
			if ( ! empty( $cars_id[0] ) ) {
				foreach ( $cars_id[0] as $car ) {
					update_field( 'cars_com', 'yes', $car );
					update_field( 'cars_com_export_date', gmdate( 'Y/m/d H:i:s' ), $car );
				}
			}
			// Close ftp connection.
			ftp_close( $connection );
		}

		if ( ( ! isset( $upload[0] ) || ! isset( $upload[1] ) ) && $send_ftp ) {
			return $redirect_to . '&car_notice=3';
		} elseif ( 0 === (int) $set_theme_options ) {
			return 'admin.php?page=log-list&car_notice=10';
		} else {
			return 'admin.php?page=log-list&car_notice=2';
		}
		die;
	}
}

if ( ! function_exists( 'cdhl_custom_export_cars_to_car_com' ) ) {
	/**
	 * Step 2: handle the custom cars.com
	 * Added to solved version compatibility issue for versions < 4.7
	 * Added without using csv library
	 * Based on the post http://wordpress.stackexchange.com/questions/29822/custom-bulk-action
	 */
	function cdhl_custom_export_cars_to_car_com() {
		global $typenow, $car_dealer_options;
		$set_theme_options = 1;

		$redirect_to = wp_get_referer();
		$post_type   = $typenow;
		if ( 'cars' === $post_type ) {
			// get the action.
			$wp_list_table   = _get_list_table( 'WP_Posts_List_Table' );  // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc.
			$action          = $wp_list_table->current_action();
			$allowed_actions = array( 'export_car_com' );
			if ( ! in_array( $action, $allowed_actions, true ) ) {
				return;
			}

			// Sent to third party or not.
			$send_ftp = ( isset( $_GET['ftp_now'] ) ) ? sanitize_text_field( wp_unslash( $_GET['ftp_now'] ) ) : null;
			if ( empty( $car_dealer_options['car_ftp_host'] ) || empty( $car_dealer_options['car_username'] ) || empty( $car_dealer_options['car_password'] ) || empty( $car_dealer_options['car_dealer_id'] ) ) {
				$set_theme_options = 0;
			}

			$file_name = array( $car_dealer_options['car_dealer_id'] . '.txt', 'imagelist_' . gmdate( 'ymdhms' ) . '.txt' );
			// create source directory.
			if ( ! file_exists( ABSPATH . 'wp-content/uploads/cars/exported' ) ) {
				mkdir( ABSPATH . 'wp-content/uploads/cars/exported', 0777, true );
			}
			$source = ABSPATH . 'wp-content/uploads/cars/exported/';

			if ( $send_ftp ) { // If checkbox is checked for sent file to third party.

				// Check theme options are set for cars.com or not.
				if ( 0 === (int) $set_theme_options ) {
					wp_safe_redirect( 'edit.php?post_type=cars&car_notice=5' );
					die;
				}

				// Check ftp module is enable on server.
				if ( ! function_exists( 'ftp_connect' ) ) {
					wp_safe_redirect( $redirect_to . '&car_notice=7' );
					die;
				}
				// Ftp connection.
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

				// Create directory on third party location for datafile.
				if ( false === (bool) cdhl_ftp_is_dir( $connection, $car_dealer_options['car_file_location'] . '/' . $car_dealer_options['car_dealer_id'] ) ) {
					ftp_mkdir( $connection, $car_dealer_options['car_file_location'] . '/' . $car_dealer_options['car_dealer_id'] );
				}
				$location[] = $car_dealer_options['car_file_location'] . '/' . $car_dealer_options['car_dealer_id']; // car detail file.
				$location[] = $car_dealer_options['car_file_location']; // imgs file.
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
			$cars_data   = cdhl_get_car_com_detail( $post_ids, $redirect_to, $allowed_actions[0] );

			$cnt            = 0;
			$cars_id        = array();
			$numeric_fields = array( 'car_year', 'sale_price', 'car_mileage' );
			foreach ( $cars_data as $c_key => $data ) {
				$delemeter = ( 'car_detail' === $c_key ) ? '|' : ',';
				$fp        = fopen( $source . $file_name[ $cnt ], 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
				end( $data );
				$lastline = key( $data );
				foreach ( $data as $key => $fields ) {
					end( $fields );
					$last_field = key( $fields );
					// Discard record if required fields are blank.
					if ( 'car_detail' === $c_key ) {
						$req_field    = array( 'car_vin_number', 'car_stock_number', 'car_year', 'car_make', 'car_model', 'car_condition' );
						$valid_record = 1;
						foreach ( $req_field as $r_field ) {
							if ( ! isset( $fields[ $r_field ] ) || empty( $fields[ $r_field ] ) ) {
								$valid_record = 0;
							}
						}
						if ( 1 !== $valid_record ) {
							continue;
						}
					}

					foreach ( $fields as $k => $field ) {
						if ( 'carId' === $k ) {
							$cars_id[0][] = $field; // get car Id.
							continue; // Donot include carid in export file.
						}

						// Check number fields.
						if ( in_array( $k, $numeric_fields, true ) && 'car_detail' === $c_key ) {
							if ( ! is_numeric( $field ) && 0 !== (int) $key ) {
								$field = '';
							}
						}
						if ( 'car_condition' === $k && 'Certified' === $field ) { // set Certified as Used.
							$field = 'Used';
						}
						if ( ( (string) $k === (string) $last_field ) ) {
							fwrite( $fp, $field ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
						} else {
							fwrite( $fp, $field . $delemeter ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
						}
					}
					if ( $key !== $lastline ) {
						fwrite( $fp, "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					}
				}
				fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

				if ( $send_ftp ) {
					$upload[ $cnt ] = ftp_put( $connection, $location[ $cnt ] . '/' . $file_name[ $cnt ], $source . $file_name[ $cnt ], FTP_BINARY );
				}
				// Create log entry.
				$ftp_status = ( isset( $upload[ $cnt ] ) && 1 === (int) $upload[ $cnt ] ) ? esc_html__( 'True', 'cardealer-export-vehicle' ) : esc_html__( 'False', 'cardealer-export-vehicle' );

				// Code to check file already exist.
				$args     = array(
					'post_type'  => 'pgs_export_log',
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array( 'value' => $file_name[ $cnt ] ),
					),
				);
				$my_query = new WP_Query( $args );
				if ( $my_query->have_posts() ) {
					while ( $my_query->have_posts() ) {
						$my_query->the_post();
						$post_id = get_the_ID();
					} // end while.
				} else // If file does not exist then insert.
				{
					$post_id = wp_insert_post(
						array(
							'post_type'    => 'pgs_export_log',
							'post_title'   => esc_html__( 'Car.com', 'cardealer-export-vehicle' ),
							'post_content' => '',
						)
					);
				}
				wp_reset_postdata();
				$cars_exported = '';
				if ( isset( $cars_id[0] ) ) {
					$cars_exported = ( ! empty( $cars_id[0] ) ) ? implode( ',', $cars_id[0] ) : '';
				}
				if ( $post_id ) {
					update_field( 'file_name', $file_name[ $cnt ], $post_id );
					update_field( 'export_to', esc_html__( 'Cars.com', 'cardealer-export-vehicle' ), $post_id );
					update_field( 'ftp_done', $ftp_status, $post_id );
					( $send_ftp ) ? update_field( 'ftp_done_on', gmdate( 'y-m-d H:i:s' ), $post_id ) : update_field( 'ftp_done_on', '-', $post_id );
					update_field( 'created', gmdate( 'y-m-d H:i:s' ), $post_id );
					update_field( 'posts', $cars_exported, $post_id );
				}
				$cnt++;
			}

			if ( $send_ftp ) {
				// add entry into cars post type.
				if ( ! empty( $cars_id[0] ) ) {
					foreach ( $cars_id[0] as $car ) {
						update_field( 'cars_com', 'yes', $car );
						update_field( 'cars_com_export_date', gmdate( 'Y/m/d H:i:s' ), $car );
					}
				}
				// Close ftp connection.
				ftp_close( $connection );
			}

			if ( ( ! $upload[0] || ! $upload[1] ) && $send_ftp ) {
				wp_safe_redirect( $redirect_to . '&car_notice=3' );
			} elseif ( 0 === (int) $set_theme_options ) {
				wp_safe_redirect( 'admin.php?page=log-list&car_notice=10' );
			} else {
				wp_safe_redirect( 'admin.php?page=log-list&car_notice=2' );
			}
			die;
		}
	}
}

if ( ! function_exists( 'cdhl_get_car_com_detail' ) ) {
	/**
	 * Get car com detail
	 *
	 * @param string $post_ids .
	 * @param string $redirect_to .
	 * @param string $action .
	 */
	function cdhl_get_car_com_detail( $post_ids, $redirect_to, $action ) {
		global $car_dealer_options;
		$car_properties = cdhl_get_car_properties( $post_ids[0], $action );

		if ( empty( $car_properties ) ) {
			wp_redirect( $redirect_to . '&car_notice=1' ); //phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		}

		$title        = cdhl_car_com_car_properties();
		$attr_heading = array();
		$counter      = 0;
		$row          = array();
		$img_array    = array();
		$car_com_data = array();
		foreach ( $post_ids as $car_id ) {
			foreach ( $car_properties as $key => $car ) {
				$row                 = array();
				$length_limit_exceed = 0; // Flag to check field length exceeded.
				$row['carId']        = $car_id;
				foreach ( $car as $k => $car_attr ) {
					if ( taxonomy_exists( $k ) ) {
						$term = wp_get_post_terms( $car_id, $k );
						if ( ! empty( $term ) ) {
							$json       = wp_json_encode( $term ); // Conver Obj to Array.
							$term       = json_decode( $json, true ); // Conver Obj to Array.
							$name_array = array_map(
								function ( $options ) {
									return $options['name'];
								},
								(array) $term
							); // get all name term array.
							if ( true === (bool) cdhl_check_field_length( implode( ',', $name_array ), $k, 'car_com' ) ) {
								$row[ $k ] = implode( ',', $name_array );
							} else {
								$length_limit_exceed = 1;
							}
						} else {
							$row[ $k ] = '';
						}
					} else {
						$attr     = get_field( $k, $car_id );
						$car_img  = get_field( 'car_images', $car_id );
						$vin_term = wp_get_post_terms( $car_id, 'car_vin_number' );

						if ( ! empty( $car_img ) && ! empty( $vin_term ) ) {
							$vin_number = $vin_term[0]->name;
							foreach ( $car_img as $imgs ) {
								if ( ! empty( $imgs['url'] ) ) {
									$img_array[ $imgs['id'] ] = array(
										'dealer_id'    => $car_dealer_options['car_dealer_id'],
										'vehicle_id'   => $vin_number,
										'version_date' => $imgs['modified'],
										'url'          => $imgs['url'],
									);
								}
							}
						}
						if ( ! empty( $attr ) ) {
							if ( true === (bool) cdhl_check_field_length( $attr, $k, 'car_com' ) ) {
								$row[ $k ] = $attr;
							} else {
								$length_limit_exceed = 1;
							}
						} elseif ( 'dealer_id' === $k ) {
							$dealer_id = $car_dealer_options['car_dealer_id'];
							if ( true === (bool) cdhl_check_field_length( $dealer_id, $k, 'car_com' ) ) {
								$row[ $k ] = $dealer_id;
							} else {
								$length_limit_exceed = 1;
							}
						} elseif ( 'inventory_date' === $k ) {
							$inventory_date = get_the_date( 'm/d/Y', $car_id );
							if ( true === (bool) cdhl_check_field_length( $inventory_date, $k, 'car_com' ) ) {
								$row[ $k ] = $inventory_date;
							} else {
								$length_limit_exceed = 1;
							}
						} else {
							$row[ $k ] = '';
						}
					}
				}
				if ( $counter < 1 ) {
					$car_com_data[] = $title;
				}
				if ( 1 === (int) $length_limit_exceed ) {
					continue;
				} else {
					$car_com_data[] = $row;
				}
				$counter++;
			}
		}
		$car_data_array['car_detail'] = $car_com_data;
		$car_data_array['car_imgs']   = $img_array;
		return $car_data_array;
	}
}

if ( ! function_exists( 'cdhl_ftp_is_dir' ) ) {
	/**
	 * Function to check directory exist on given path
	 *
	 * @param string $ftp .
	 * @param string $dir .
	 */
	function cdhl_ftp_is_dir( $ftp, $dir ) {
		$pushd = ftp_pwd( $ftp );
		if ( false !== $pushd && @ftp_chdir( $ftp, $dir ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			ftp_chdir( $ftp, $pushd );
			return true;
		}
		return false;
	}
}
