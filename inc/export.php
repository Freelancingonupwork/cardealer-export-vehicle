<?php
/**
 * CODE TO EXPORT CAR POST TYPE STARTS
 * Bulk Action WordPress version compibility starts
 *
 * @package Cardealer Helper Library
 */

global $wp_version;

if ( ! function_exists( 'cdhl_add_export_option' ) ) {
	/**
	 * Add export option
	 *
	 * @param string $bulk_actions .
	 */
	function cdhl_add_export_option( $bulk_actions ) {
		$cdhl_export_options = cdev_export_options();

		$bulk_actions = array_merge( $bulk_actions, $cdhl_export_options );

		return $bulk_actions;
	}
}

if ( version_compare( $wp_version, '4.7', '>=' ) ) {
	add_filter( 'bulk_actions-edit-cars', 'cdhl_add_export_option', 50 );
	add_filter( 'handle_bulk_actions-edit-cars', 'cdhl_export_cars_to_csv', 10, 3 );
	add_filter( 'handle_bulk_actions-edit-cars', 'cdhl_export_cars_to_autotrader', 10, 4 );
	add_filter( 'handle_bulk_actions-edit-cars', 'cdhl_export_cars_to_car_com', 10, 4 );
} else {
	add_action( 'load-edit.php', 'cdhl_custom_export_cars_to_csv' );
	add_action( 'load-edit.php', 'cdhl_custom_export_cars_to_autotrader' );
	add_action( 'load-edit.php', 'cdhl_custom_export_cars_to_car_com' );
}

/* Bulk Action WordPress version compibility ends */

if ( ! function_exists( 'cdhl_autotrader_car_properties' ) ) {
	/**
	 * AUTOTRADER_CAR_PROPERTIES
	 *
	 * @param string $car_type .
	 */
	function cdhl_autotrader_car_properties( $car_type = null ) {
			$properties                     = array();
			$properties['dealer_id']        = 'Dealer ID';
			$properties['car_stock_number'] = 'Stock Number';
			$properties['car_year']         = 'Year';
			$properties['car_make']         = 'Make';
			$properties['car_model']        = 'Model';
			$properties['car_trim']         = 'Trim';
			$properties['car_vin_number']   = 'VIN';
			$properties['car_mileage']      = 'Mileage';
		if ( 'new' === $car_type ) {
			$properties['regular_price'] = 'MSRP';
			$properties['sale_price']    = 'Dealer Price';
		} else {
			$properties['regular_price'] = 'Regular Price';
			$properties['sale_price']    = 'Sale Price';
		}
			$properties['car_exterior_color']   = 'Exterior Color';
			$properties['car_interior_color']   = 'Interior Color';
			$properties['car_transmission']     = 'Transmission';
			$properties['images']               = 'Physical Images';
			$properties['vehicle_overview']     = 'Description';
			$properties['car_body_style']       = 'Body Type';
			$properties['car_engine']           = 'Engine Type';
			$properties['drive_type']           = 'Drive Type';
			$properties['car_fuel_type']        = 'Fuel Type';
			$properties['car_features_options'] = 'Options';
			$properties['car_images']           = 'Image URLs';
			$properties['video_link']           = 'Video URL';
			$properties['video_source']         = 'Video Source';
		return $properties;
	}
}

if ( ! function_exists( 'cdhl_autotrader_car_field_length' ) ) {
	/**
	 * Function to get length of each field
	 *
	 * @param string $key .
	 */
	function cdhl_autotrader_car_field_length( $key ) {
			$length                         = array();
			$length['dealer_id']            = 10;
			$length['car_stock_number']     = 30;
			$length['car_year']             = 4;
			$length['car_make']             = 10;
			$length['car_model']            = 10;
			$length['car_trim']             = 30;
			$length['car_vin_number']       = 18;
			$length['car_mileage']          = 8;
			$length['regular_price']        = 8;
			$length['sale_price']           = 8;
			$length['msrp']                 = 8;
			$length['car_exterior_color']   = 30;
			$length['car_interior_color']   = 30;
			$length['car_transmission']     = 50;
			$length['images']               = 250;
			$length['vehicle_overview']     = 2000;
			$length['car_body_style']       = 50;
			$length['car_engine']           = 50;
			$length['drive_type']           = 50;
			$length['car_fuel_type']        = 50;
			$length['car_features_options'] = 4000;
			$length['car_images']           = 4000;
			$length['video_link']           = 250;
			$length['video_source']         = 250;
		return $length[ $key ];
	}
}

if ( ! function_exists( 'cdhl_car_com_car_properties' ) ) {
	/**
	 * CAR.COM_CAR_PROPERTIES
	 */
	function cdhl_car_com_car_properties() {
			$properties                          = array();
			$properties['dealer_id']             = 'Dealer ID';
			$properties['car_condition']         = 'Type';
			$properties['car_stock_number']      = 'Stock Number';
			$properties['car_vin_number']        = 'VIN';
			$properties['car_year']              = 'Year';
			$properties['car_make']              = 'Make';
			$properties['car_model']             = 'Model';
			$properties['car_body_style']        = 'Body';
			$properties['car_trim']              = 'Trim-Style';
			$properties['car_exterior_color']    = 'Ext Color';
			$properties['car_interior_color']    = 'Int Color';
			$properties['engine_cylinders']      = 'Engine Cylinders';
			$properties['engine_discplacement']  = 'Engine Discplacement';
			$properties['car_engine']            = 'Engine Description';
			$properties['car_transmission']      = 'Transmission';
			$properties['car_mileage']           = 'Miles';
			$properties['sale_price']            = 'Selling Price';
			$properties['regular_price']         = 'MSRP';
			$properties['invoice_price']         = 'Invoice Price';
			$properties['car_drivetrain']        = 'Drive Train';
			$properties['dealer_tagline']        = 'Dealer Tagline';
			$properties['car_features_options']  = 'Vehicle Value Added Options';
			$properties['option_packages_codes'] = 'Option Packages Codes';
			$properties['inventory_date']        = 'Inventory Date';
			$properties['model_code']            = 'Model Code';
		return $properties;
	}
}

if ( ! function_exists( 'cdhl_car_com_field_length' ) ) {
	/**
	 * Function to get field length
	 *
	 * @param string $key .
	 */
	function cdhl_car_com_field_length( $key ) {
			$length                          = array();
			$length['dealer_id']             = 20;
			$length['car_condition']         = 20;
			$length['car_stock_number']      = 20;
			$length['car_vin_number']        = 20;
			$length['car_year']              = 4;
			$length['car_make']              = 35;
			$length['car_model']             = 50;
			$length['car_body_style']        = 50;
			$length['car_trim']              = 50;
			$length['car_exterior_color']    = 50;
			$length['car_interior_color']    = 35;
			$length['engine_cylinders']      = '*';
			$length['engine_discplacement']  = '*';
			$length['car_engine']            = 50;
			$length['car_transmission']      = 50;
			$length['car_mileage']           = 10;
			$length['sale_price']            = 10;
			$length['regular_price']         = 10;
			$length['invoice_price']         = 10;
			$length['car_drivetrain']        = 50;
			$length['dealer_tagline']        = 2000;
			$length['car_features_options']  = 2000;
			$length['option_packages_codes'] = 4000;
			$length['inventory_date']        = 10;
			$length['model_code']            = 20;
		return $length[ $key ];
	}
}
if ( ! function_exists( 'cdhl_get_car_properties' ) ) {
	/**
	 * Get properties of Vehicles
	 *
	 * @param string $car_id .
	 * @param string $action .
	 */
	function cdhl_get_car_properties( $car_id, $action ) {
		$car_attributes = array();
		$car_property   = array();
		global $car_dealer_options;

		if ( 'export_cars' === $action ) {
			unset( $car_dealer_options['export_cars']['Attributes to export']['placebo'] );
			$car_attributes = $car_dealer_options['export_cars']['Attributes to export'];
			if ( empty( $car_attributes ) ) {
				return;
			}
			foreach ( $car_attributes as $key => $car_att ) {
				$car_property[ $car_id ][ $key ] = get_field_object( $key, $car_id );
			}
		} elseif ( 'export_autotrader' === $action ) {
			// New Car.
			$new_car_attributes = cdhl_autotrader_car_properties( 'new' );
			$new_car_property   = array();

			if ( empty( $new_car_attributes ) ) {
				return;
			}

			$blank_attr = array( 'drive_type', 'video_link', 'video_source', 'dealer_id', 'images' );
			foreach ( $new_car_attributes as $key => $car_att ) {
				if ( in_array( $key, $blank_attr, true ) ) {
					$new_car_property[ $car_id ][ $key ] = '';
				} else {
					$new_car_property[ $car_id ][ $key ] = get_field_object( $key, $car_id );
				}
			}

			// Old Car.
			$old_car_attributes = cdhl_autotrader_car_properties();
			$old_car_property   = array();

			if ( empty( $old_car_attributes ) ) {
				return;
			}

			$blank_attr = array( 'drive_type', 'video_link', 'video_source', 'dealer_id', 'images' );

			foreach ( $old_car_attributes as $key => $car_att ) {
				if ( in_array( $key, $blank_attr, true ) ) {
					$old_car_property[ $car_id ][ $key ] = '';
				} else {
					$old_car_property[ $car_id ][ $key ] = get_field_object( $key, $car_id );
				}
			}

			// Merge Two CarType Propery.
			$car_property['old_car'] = $old_car_property;
			$car_property['new_car'] = $new_car_property;
		} elseif ( 'export_car_com' === $action ) {
			// New Car.
			$car_attributes = cdhl_car_com_car_properties();
			$car_property   = array();

			if ( empty( $car_attributes ) ) {
				return;
			}

			$blank_attr = array( 'dealer_id', 'engine_cylinders', 'engine_discplacement', 'invoice_price', 'option_packages_codes', 'inventory_date', 'model_code', 'dealer_tagline' );
			foreach ( $car_attributes as $key => $car_att ) {
				if ( in_array( $key, $blank_attr, true ) ) {
					$car_property[ $car_id ][ $key ] = '';
				} else {
					$car_property[ $car_id ][ $key ] = get_field_object( $key, $car_id );
				}
			}
		}

		return $car_property;
	}
}

add_action( 'admin_notices', 'cdhl_empty_properties_notice' );
if ( ! function_exists( 'cdhl_empty_properties_notice' ) ) {
	/**
	 * Display Error msg if attributes are not set from admin side theme settings
	 */
	function cdhl_empty_properties_notice() {
		if ( isset( $_GET['car_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$class = 'notice is-dismissible';

			switch ( $_GET['car_notice'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				case 1:
					$class  .= ' notice-error';
					$message = esc_html__( 'Error! Please set attributes to export from admin in theme settings.', 'cardealer-export-vehicle' );
					break;
				case 2:
					$class  .= ' updated notice-success';
					$message = esc_html__( 'Success! Vehicle data successfully exported!', 'cardealer-export-vehicle' );
					break;
				case 3:
					$class  .= ' notice-error';
					$message = esc_html__( 'Error! Failed to export file.', 'cardealer-export-vehicle' );
					break;
				case 4:
					$class  .= ' notice-error';
					$message = esc_html__( 'Error! FTP Connection attempt failed!', 'cardealer-export-vehicle' );
					break;
				case 5:
					$class  .= ' notice-error';
					$message = esc_html__( 'Error! FTP Details and Dealer Id fields should not be empty in theme option.', 'cardealer-export-vehicle' );
					break;
				case 6:
					$class  .= ' notice-error';
					$message = esc_html__( 'Error! Please provide correct path to send exported file.', 'cardealer-export-vehicle' );
					break;
				case 7:
					$class  .= ' notice-error';
					$message = esc_html__( 'Error! Sounds like your PHP was not installed with "--enable-ftp" or that the ftp module is disabled in your php.ini. Please ask your hosting provider to enable ftp module on your server.', 'cardealer-export-vehicle' );
					break;
				case 8:
					$class  .= ' notice-info';
					$message = esc_html__( 'Alert! No records exported, this is because of no record satisfied desired rules of selected third party export.', 'cardealer-export-vehicle' );
					break;
				case 9:
					$class  .= ' notice-info';
					$message = esc_html__( 'Alert! Inventory successfully exported and export file successfully generated. But you can not send exported file to Auto Trader as you do not have set theme option settings for Auto Trader.', 'cardealer-export-vehicle' );
					break;
				case 10:
					$class  .= ' notice-info';
					$message = esc_html__( 'Alert! Inventory successfully exported and export file successfully generated. But you can not send exported file to Cars.Com as you do not have set theme option settings for Cars.Com.', 'cardealer-export-vehicle' );
					break;
				case 11:
					$class  .= ' notice-error';
					$message = esc_html__( 'Error! Dealer ID field should not be empty in the theme options.', 'cardealer-export-vehicle' );
					break;
			}

			$btn_type   = 'button';
			$btn_class  = 'notice-dismiss';
			$span_class = 'screen-reader-text';
			$btn_msg    = esc_html__( 'Dismiss this notice.', 'cardealer-export-vehicle' );
			?>
			<div class="<?php echo esc_attr( $class ); ?>"><p><?php echo esc_html( $message ); ?></p><button type="<?php echo esc_attr( $btn_type ); ?>" class="<?php echo esc_attr( $btn_class ); ?>"><span class="<?php echo esc_attr( $span_class ); ?>"><?php echo esc_html( $btn_msg ); ?></span></button></div>
			<?php
		}
	}
}

if ( ! function_exists( 'cdhl_get_cars_detail' ) ) {
	/**
	 * Get cars detail
	 *
	 * @param string $post_ids .
	 * @param string $redirect_to .
	 * @param string $action .
	 */
	function cdhl_get_cars_detail( $post_ids, $redirect_to, $action ) {
		$car_properties = cdhl_get_car_properties( $post_ids[0], $action );
		if ( empty( $car_properties ) ) {
			wp_safe_redirect( $redirect_to . '&car_notice=1' );
			die;
		}

		$attr_heading = array();
		$counter      = 0;
		$row          = array();
		$csv_data     = array();
		foreach ( $post_ids as $car_id ) {
			foreach ( $car_properties as $key => $car ) {
				$row = array();
				foreach ( $car as $k => $car_attr ) {
					if ( $counter < 1 ) {
						if ( ! empty( $car_attr['label'] ) ) {
							$attr_heading[ $k ] = $car_attr['label'];
						} else {
							$tax_obj = get_taxonomy( $k );
							if ( ! is_wp_error( $tax_obj ) && isset( $tax_obj->labels->singular_name ) ) {
								$attr_heading[ $k ] = $tax_obj->labels->singular_name;
							} else {
								$attr_heading[ $k ] = ucwords( str_replace( '_', ' ', $k ) );
							}
						}
						$title = array();
						foreach ( $attr_heading as $mykey => $heading ) {
							$title[] = $heading;
						}
					}
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
							$row[]      = implode( ',', $name_array );
						} else {
							$row[] = '';
						}
					} else {
						$attr = get_field( $k, $car_id );
						if ( ! empty( $attr ) ) {
							if ( 'car_images' === $k ) {
								$img = array();
								foreach ( $attr as $imgs ) {
									$img[] = $imgs['url'];
								}
								$row[] = implode( ',', $img );
							} elseif ( 'pdf_file' === $k ) {
								$row[] = isset( $attr['url'] ) ? $attr['url'] : wp_get_attachment_url( $attr );
							} elseif ( 'vehicle_location' === $k ) {
								$row[] = $attr['address'];
							} else {
								$row[] = $attr;
							}
						} else {
							if ( 'vehicle_title' === $k ) {
								$row[] = get_the_title( $car_id );
							} else {
								$row[] = '';
							}
						}
					}
				}
				if ( $counter < 1 ) {
					$csv_data[] = $title;
				}
				$csv_data[] = $row;
				$counter++;
			}
		}
		return $csv_data;
	}
}

require_once trailingslashit( CDEV_PATH ) . 'inc/export_csv.php'; // csv export.
require_once trailingslashit( CDEV_PATH ) . 'inc/export_thirdparty.php'; // third party export.
/******* CODE TO EXPORT CAR POST TYPE END ******** */
?>
