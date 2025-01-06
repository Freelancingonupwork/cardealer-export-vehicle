(function($){
	"use strict";
	
	// Check element exists.
	$.fn.exists = function () {
		return this.length > 0;
	};

	jQuery( document ).ready( function($) {
		/*
		* Code for select checkboxes by default on car post type for export thirdparty
		*/

		jQuery('select#bulk-action-selector-top').change( function(){
			if( jQuery(this).val() == 'export_autotrader' || jQuery(this).val() == 'export_car_com' || jQuery(this).val() == 'export_cars' ) {
				if( jQuery('#cb-select-all-1').prop("checked") != true )
					jQuery('#cb-select-all-1').trigger('click');
			}
		});

		if ( 'no' === cdev_js_obj.v47_or_greater ) {
			var bulk_top          = jQuery( '#bulk-action-selector-top' ),
				bulk_bottom       = jQuery( '#bulk-action-selector-bottom' ),
				export_cars       = jQuery( '<option>', cdev_js_obj.export_cars ),
				export_autotrader = jQuery( '<option>', cdev_js_obj.export_autotrader ),
				export_car_com    = jQuery( '<option>', cdev_js_obj.export_car_com );

			bulk_top.append( export_cars, export_autotrader, export_car_com );
			bulk_bottom.append( export_cars.clone(), export_autotrader.clone(), export_car_com.clone() );
		}

		/* Code tobe use for export to third party */
		jQuery(document).on( 'change', '#bulk-action-selector-top, #bulk-action-selector-bottom', function(){
			if ( jQuery(this).val() == 'export_autotrader' || jQuery(this).val() == 'export_car_com' ) {
				jQuery('#ftp_now').css({
					'display': 'block',
					'line-height': '28px'
				});
			} else {
				jQuery('#ftp_now').css( 'display', 'none' );
			}
		});

		/*Code Of Export Log List*/
		if(document.getElementById('export-log')){
			jQuery('#export-log').DataTable({
				processing: true,
				serverSide: true,
				responsive: true,
				'bLengthChange': false,
				'iDisplayLength': 20,
				'bFilter': false,
				"bSort" : false,
				ajax:  jQuery.fn.dataTable.pipeline({
					url: ajaxurl + '?action=get_export_log',
					pages: 4 // number of pages to cache
				})
			});
		}

		/*Code Of Export Log List*/
		if(document.getElementById('export-log')){
			jQuery('#export-log').DataTable({
				destroy: true,
				processing: true,
				serverSide: true,
				responsive: true,
				'bLengthChange': false,
				'iDisplayLength': 20,
				'bFilter': false,
				"bSort" : false,
				ajax:  jQuery.fn.dataTable.pipeline({
					url: ajaxurl + '?action=get_export_log',
					pages: 4 // number of pages to cache
				})
			});
		}

		/*Code Of Export Cars List*/
		if(document.getElementById('export-cars-list')){
			jQuery('#export-cars-list').DataTable({
				destroy: true,
				processing: true,
				serverSide: true,
				responsive: true,
				'bLengthChange': false,
				'iDisplayLength': 20,
				'bFilter': false,
				"bSort" : false,
				ajax:  jQuery.fn.dataTable.pipeline({
					url: ajaxurl + '?action=get_export_car_list',
					pages: 5 // number of pages to cache
				})
			});
		}
	});
})(jQuery);