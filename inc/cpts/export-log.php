<?php
/**
 * Add CPT
 *
 * @author  TeamWP @Potenza Global Solutions
 * @package car-dealer-helper
 */

add_action( 'init', 'cdev_export_log' );
if ( ! function_exists( 'cdev_export_log' ) ) {
	/**
	 * Export log
	 */
	function cdev_export_log() {
		$args = array(
			'labels'              => array(
				'name'          => esc_html__( 'Export Log', 'cardealer-export-vehicle' ),
				'singular_name' => esc_html__( 'Export Log', 'cardealer-export-vehicle' ),
			),
			'capability_type'     => 'post',
			'capabilities'        => array(
				'create_posts' => 'do_not_allow', // false < WP 4.5, credit @Ewout.
			),
			'map_meta_cap'        => true,
			'public'              => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => false,
			'has_archive'         => true,
			'rewrite'             => array( 'slug' => 'export_log' ),
			'supports'            => array(),
		);
		register_post_type( 'pgs_export_log', $args );
	}
}
