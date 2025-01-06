<?php
/**
 * Inventory list columns
 *
 * @package car-dealer-helper
 */

if ( ! function_exists( 'cdev_cpt_cars_edit_columns' ) ) {
	/**
	 * Edit colums
	 *
	 * @param string $columns .
	 */
	function cdev_cpt_cars_edit_columns( $columns ) {
		$new_fields =
			array_slice(
				$columns,
				0,
				9,
				true
			) +
			array(
				'auto_trader' => esc_html__( 'Auto Trader', 'cardealer-export-vehicle' ),
				'car_com'     => esc_html__( 'Cars.com', 'cardealer-export-vehicle' ),
			) +
			array_slice( $columns, 9, count( $columns ) - 1, true );
		return $new_fields;
	}
	add_filter( 'manage_edit-cars_columns', 'cdev_cpt_cars_edit_columns', 100, 1 );

}

if ( ! function_exists( 'cdev_cpt_cars_custom_columns' ) ) {
	/**
	 * Custom columns
	 *
	 * @param string $column .
	 * @param string $post_id .
	 */
	function cdev_cpt_cars_custom_columns( $column, $post_id ) {
		if ( 'cars' === get_post_type( $post_id ) && 'auto_trader' === $column ) {
			$dealer = get_post_meta( $post_id, 'auto_trader', true );
			if ( isset( $dealer ) && ! empty( $dealer ) && 'yes' === (string) $dealer ) {
				echo esc_html( gmdate( 'm/d/Y H:i:s', strtotime( get_post_meta( $post_id, 'auto_export_date', true ) ) ) );
			} else {
				echo '-';
			}
		}

		if ( 'cars' === get_post_type( $post_id ) && 'car_com' === $column ) {
			$dealer = get_post_meta( $post_id, 'cars_com', true );
			if ( isset( $dealer ) && ! empty( $dealer ) && 'yes' === (string) $dealer ) {
				echo esc_html( gmdate( 'm/d/Y H:i:s', strtotime( get_post_meta( $post_id, 'cars_com_export_date', true ) ) ) );
			} else {
				echo '-';
			}
		}
		return $column;
	}

	add_filter( 'manage_cars_posts_custom_column', 'cdev_cpt_cars_custom_columns', 100, 2 );
}
