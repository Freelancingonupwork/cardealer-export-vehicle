<?php
/**
 * Plugin Name:       Car Dealer - Export Vehicle
 * Plugin URI:        http://www.potenzaglobalsolutions.com/
 * Description:       This plugin contains export vehicle functionality for "Car Dealer" theme.
 * Version:           1.1.0
 * Author:            Potenza Global Solutions
 * Author URI:        http://www.potenzaglobalsolutions.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cardealer-export-vehicle
 * Domain Path:       /languages
 *
 * @package cardealer-export-vehicle
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'CDEV_PATH' ) ) {
	define( 'CDEV_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'CDEV_URL' ) ) {
	define( 'CDEV_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'CDEV_VERSION' ) ) {
	define( 'CDEV_VERSION', '1.1.0' );
}

/**
 * Car log extend cardealer other fetures.
 *
 * @param array $features features.
 */
function cdev_extend_cardealer_other_fetures( $features ) {

	$features['export-vehicle'] = array(
		'title' => esc_html__( 'Export Vehicle', 'cardealer-export-vehicle' ),
		'link'  => admin_url( 'edit.php?post_type=cars' ),
		'desc'  => esc_html__( 'Click this button to open export vehicle with third party export section.', 'cardealer-export-vehicle' ),
	);

	return $features;
}
add_filter( 'cardealer_other_fetures', 'cdev_extend_cardealer_other_fetures' );

/**
 * Car log extend cardealer other fetures.
 *
 * @param array $features features.
 */
function cdev_list_extend_cardealer_other_fetures( $features ) {

	$features['export-vehicle-list'] = array(
		'title' => esc_html__( 'Export Vehicle List', 'cardealer-export-vehicle' ),
		'link'  => admin_url( 'admin.php?page=car-export-list' ),
		'desc'  => esc_html__( 'Click this button to open export vehicle list section.', 'cardealer-export-vehicle' ),
	);

	return $features;
}
add_filter( 'cardealer_other_fetures', 'cdev_list_extend_cardealer_other_fetures' );

/**
 * Car log extend cardealer other fetures.
 *
 * @param array $features features.
 */
function cdev_log_extend_cardealer_other_fetures( $features ) {

	$features['export-vehicle-log'] = array(
		'title' => esc_html__( 'Export Vehicle Log', 'cardealer-export-vehicle' ),
		'link'  => admin_url( 'admin.php?page=log-list' ),
		'desc'  => esc_html__( 'Click this button to open export vehicle log section.', 'cardealer-export-vehicle' ),
	);

	return $features;
}
add_filter( 'cardealer_other_fetures', 'cdev_log_extend_cardealer_other_fetures' );

add_action( 'admin_enqueue_scripts', 'cdev_admin_enqueue_scripts' );
if ( ! function_exists( 'cdev_admin_enqueue_scripts' ) ) {
	/**
	 * Add script and style in wp-admin side
	 */
	function cdev_admin_enqueue_scripts() {
		global $wp_version;
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'cdev_js', trailingslashit( CDEV_URL ) . 'js/export-vehicle' . $suffix . '.js', array( 'jquery' ), CDEV_VERSION, true );
		
		$export_options = cdev_export_options();

		// Localize the script with new data
		$cdev_js_data = array(
			'v47_or_greater' => ( version_compare( $wp_version, '4.7', '>=' ) ) ? 'yes' : 'no',
		);
		
		foreach ( $export_options as $option_k => $option_v ) {
			$cdev_js_data[ $option_k ] = array(
				'value' => $option_k,
				'text'  => $option_v,
			);
		}
		
		wp_localize_script( 'cdev_js', 'cdev_js_obj', $cdev_js_data );
		
		if ( 'cars' === get_post_type() || ( isset( $_GET['page'] ) && 'car-export-list' === $_GET['page'] ) ) { // phpcs:disable WordPress.Security.NonceVerification.Recommended
			wp_enqueue_script( 'cdev_js' );
		}
	}
}

function cdev_export_options() {
	$export_options =  array(
		'export_cars'       => esc_html__( 'Export to CSV', 'cardealer-export-vehicle' ),
		'export_autotrader' => esc_html__( 'Export To AutoTrader.com', 'cardealer-export-vehicle' ),
		'export_car_com'    => esc_html__( 'Export To Cars.com', 'cardealer-export-vehicle' ),
	);
	return $export_options;
}

/**
 * Export car detail option.
 *
 * @param string $opt_name option name.
 */
function cdev_theme_options( $opt_name ) {
	// Export car detail option.
	Redux::setSection(
		$opt_name,
		array(
			'title'            => esc_html__( 'Export Vehicle Detail', 'cardealer-export-vehicle' ),
			'id'               => 'export-cars',
			'subsection'       => true,
			'customizer_width' => '400px',
			'icon'             => 'fas fa-chevron-right',
			'fields'           => array(
				array(
					'id'       => 'export_cars',
					'type'     => 'sorter',
					'title'    => 'Export Vehicles',
					'subtitle' => '(Export to CSV)',
					'desc'     => 'Select attributes to Export. This will be used for CSV export.',
					'options'  => array(
						/**
						 * Filters option of the list of vehicle attributes to be exported in vehicle inventory export functionality.
						 *
						 * @since 1.0
						 * @param array     $attributes Array of vehicle attributes to be exported.
						 * @visible         true
						 */
						'Available attributes' => apply_filters(
							'cdhl_vehicle_export_fields',
							cdhl_vehicle_export_option_fields()
						),
						'Attributes to export' => array(),
					),
				),
				array(
					'id'       => 'auto_trader_starts',
					'type'     => 'section',
					'title'    => esc_html__( 'AutoTrader.com Export Settings', 'cardealer-export-vehicle' ),
					'subtitle' => esc_html__( 'AutoTrader.com details to export vehicle data.', 'cardealer-export-vehicle' ),
					'indent'   => true,
				),
				array(
					'id'       => 'dealer_id',
					'type'     => 'text',
					'title'    => esc_html__( 'Dealer Id', 'cardealer-export-vehicle' ),
					'subtitle' => esc_html__( 'Enter Dealer Id.', 'cardealer-export-vehicle' ),
				),
				array(
					'id'       => 'ftp_host',
					'type'     => 'text',
					'title'    => esc_html__( 'FTP Host', 'cardealer-export-vehicle' ),
					'subtitle' => esc_html__( 'Enter FTP HostName.', 'cardealer-export-vehicle' ),
				),
				array(
					'id'       => 'username',
					'type'     => 'text',
					'title'    => esc_html__( 'FTP Username', 'cardealer-export-vehicle' ),
					'subtitle' => esc_html__( 'Enter FTP Username.', 'cardealer-export-vehicle' ),
				),
				array(
					'id'       => 'password',
					'type'     => 'text',
					'title'    => esc_html__( 'FTP Password', 'cardealer-export-vehicle' ),
					'subtitle' => esc_html__( 'Enter FTP Password.', 'cardealer-export-vehicle' ),
				),
				array(
					'id'       => 'file_location',
					'type'     => 'text',
					'title'    => esc_html__( 'Location', 'cardealer-export-vehicle' ),
					'subtitle' => esc_html__( 'Enter Location for exported file to store on server.', 'cardealer-export-vehicle' ),
				),
				array(
					'id'     => 'auto_trader_end',
					'type'   => 'section',
					'indent' => false,
				),
				array(
					'id'       => 'car_starts',
					'type'     => 'section',
					'title'    => esc_html__( 'Cars.com Export Settings', 'cardealer-export-vehicle' ),
					'subtitle' => esc_html__( 'Cars.com details to export vehicle data.', 'cardealer-export-vehicle' ),
					'indent'   => true,
				),
				array(
					'id'       => 'car_dealer_id',
					'type'     => 'text',
					'title'    => esc_html__( 'Dealer Id', 'cardealer-export-vehicle' ),
					'subtitle' => esc_html__( 'Enter Dealer Id.', 'cardealer-export-vehicle' ),
				),
				array(
					'id'       => 'car_ftp_host',
					'type'     => 'text',
					'title'    => esc_html__( 'FTP Host', 'cardealer-export-vehicle' ),
					'subtitle' => esc_html__( 'Enter FTP HostName.', 'cardealer-export-vehicle' ),
				),
				array(
					'id'       => 'car_username',
					'type'     => 'text',
					'title'    => esc_html__( 'FTP Username', 'cardealer-export-vehicle' ),
					'subtitle' => esc_html__( 'Enter FTP Username.', 'cardealer-export-vehicle' ),
				),
				array(
					'id'       => 'car_password',
					'type'     => 'text',
					'title'    => esc_html__( 'FTP Password', 'cardealer-export-vehicle' ),
					'subtitle' => esc_html__( 'Enter FTP Password.', 'cardealer-export-vehicle' ),
				),
				array(
					'id'       => 'car_file_location',
					'type'     => 'text',
					'title'    => esc_html__( 'Location', 'cardealer-export-vehicle' ),
					'subtitle' => esc_html__( 'Enter Location for exported file to store on server.', 'cardealer-export-vehicle' ),
				),
				array(
					'id'     => 'car_end',
					'type'   => 'section',
					'indent' => false,
				),
			),
		)
	);
}
add_action( 'car_dealer_options_after_vehicle_settings', 'cdev_theme_options' );

require_once trailingslashit( CDEV_PATH ) . 'inc/cpts/export-log.php'; // Export Cars.

if ( is_admin() ) {
	require_once trailingslashit( CDEV_PATH ) . 'inc/inventory-list-column.php';
	require_once trailingslashit( CDEV_PATH ) . 'inc/export.php'; // Export Cars.
	require_once trailingslashit( CDEV_PATH ) . 'inc/export_log/export_log.php'; // Export Log List.
	require_once trailingslashit( CDEV_PATH ) . 'inc/export_log/car_export_list.php';
}
