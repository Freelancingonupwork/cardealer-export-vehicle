'use strict';

module.exports = function(grunt) {

	grunt.initConfig({

		// Basic configuration
		pkg: grunt.file.readJSON('package.json'),
		current_time: grunt.template.today("UTC:yyyy-mm-dd HH:MM:sso"),

		checktextdomain: {
			'textdomain-standard': {
				options: {
					text_domain: [ 'cardealer-export-vehicle' ], //Specify allowed domain(s)
					report_missing: true,
					report_variable_domain: true,
					create_report_file: true,
					keywords: [ //List keyword specifications
						'__:1,2d',
						'_e:1,2d',
						'_x:1,2c,3d',
						'esc_html__:1,2d',
						'esc_html_e:1,2d',
						'esc_html_x:1,2c,3d',
						'esc_attr__:1,2d',
						'esc_attr_e:1,2d',
						'esc_attr_x:1,2c,3d',
						'_ex:1,2c,3d',
						'_n:1,2,4d',
						'_nx:1,2,4c,5d',
						'_n_noop:1,2,3d',
						'_nx_noop:1,2,3c,4d',
						'_nc:1,2,4d',
						'_c:1,2d',
						'__ngettext:1,2,4d',
						'__ngettext_noop:1,2,3d'
					]
				},
				files: [
					{
						src: [
							'**/*.php',
							'!**/node_modules/**',
							'!**/vendor/**',
							'!**/lib/**',
						], //all php
						expand: true
					}
				]
			}
		},
		makepot: {
			target: {
				options: {
					cwd: '',                                                        // Directory of files to internationalize.
					domainPath: 'languages/',                                       // Where to save the POT file.
					exclude: [                                                      // List of files or directories to ignore.
						'node_modules/.*',
						'vendor/.*',
						'lib/.*',
					],
					include: [],                                                    // List of files or directories to include.
					mainFile: 'cardealer-export-vehicle.php',                       // Main project file.
					potComments: 'Copyright (C) {{year}} Potenza Global Solutions', // The copyright at the beginning of the POT file.
					potFilename: 'cardealer-export-vehicle.pot',                    // Name of the POT file.
					potHeaders: {
						poedit: true,                                               // Includes common Poedit headers.
						'x-poedit-keywordslist': true,                              // Include a list of all possible gettext functions.
						'Language-Team': '<%= pkg.author %> <%= pkg.homepage %>',
						'Project-Id-Version' : '<%= pkg.title %> <%= pkg.version %>',
						'Last-Translator': '<%= pkg.author %> <%= pkg.homepage %>',
						'PO-Revision-Date': '<%= current_time %>',
						'X-Poedit-SearchPathExcluded-0': '.vscode',
						'X-Poedit-SearchPathExcluded-1': 'vendor',
						'X-Poedit-SearchPathExcluded-2': 'vendor-lib',
						'X-Poedit-SearchPathExcluded-3': 'includes/cpts/cores',
						'X-Poedit-SearchPathExcluded-4': 'node_modules',
					},                                                              // Headers to add to the generated POT file.
					processPot: null,                                               // A callback function for manipulating the POT file.
					type: 'wp-plugin',                                              // Type of project (wp-plugin or wp-theme).
					updateTimestamp: true,                                          // Whether the POT-Creation-Date should be updated without other changes.
					updatePoFiles: false                                            // Whether to update PO files in the same directory as the POT file.
				}
			}
		},
	});

	// load plugins
	grunt.loadNpmTasks( 'grunt-checktextdomain' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );

	grunt.registerTask( 'textdomain', [
		'checktextdomain'
	] );

	// register at least this one task
	grunt.registerTask('default', [
	]);

};
