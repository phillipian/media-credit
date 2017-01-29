'use strict';
module.exports = function( grunt ) {

	// load all tasks
	require( 'load-grunt-tasks' )( grunt, { scope: 'devDependencies' } );

	grunt.initConfig({
		pkg: grunt.file.readJSON( 'package.json' ),
//		phpunit: {
//		classes: {
//		options: {
//		testsuite: 'media-credit',
//		}
//		},
//		options: {
//		colors: true,
//		configuration: 'phpunit.xml',
//		}
//		}
		clean: {
			build: [ "build/*" ]//,
		},
		wp_readme_to_markdown: {
			readme: {
				files: {
					'README.md': 'readme.txt',
				},
			},
			options: {
				screenshot_url: 'wp-assets/{screenshot}.png',
			}
		},
		copy: {
			build: {
				files: [
					{ expand: true, nonull: true, src: ['readme.txt','*.php'], dest: 'build/' },
					{ expand: true, nonull: true, src: ['admin/**','public/**','includes/**', '!**/scss/**'], dest: 'build/' },
				],
			}
		},

		wp_deploy: {
			options: {
				plugin_slug: 'media-credit',
				// svn_user: 'your-wp-repo-username',
				build_dir: 'build', //relative path to your build directory
				assets_dir: 'wp-assets', //relative path to your assets directory (optional).
				max_buffer: 1024 * 1024
			},
			release: {
				// nothing
			},
			trunk: {
				options: {
					deploy_trunk: true,
					deploy_assets: true,
					deploy_tag: false,
				}
			},
			assets: {
				options: {
					deploy_assets: true,
					deploy_trunk: false,
					deploy_tag: false,
				}
			}
		},

		jshint: {
			files: [
				'admin/js/**/*.js',
				'public/js/**/*.js',
				'!**/*.min.js'
			],
			options: {
				expr: true,
				globals: {
					jQuery: true,
					console: true,
					module: true,
					document: true
				},
			}
		},

		jscs: {
			src: [
				'admin/js/**/*.js',
				'public/js/**/*.js',
				'!**/*.min.js'
			],
			options: {
			}
		},

		phpcs: {
			plugin: {
				src: ['includes/**/*.php', 'admin/**/*.php', 'public/**/*.php']
			},
			options: {
				bin: 'phpcs -p -s -v -n ',
				standard: './codesniffer.ruleset.xml'
			}
		},

		delegate: {
			sass: {
				src: [ '<%= sass.dev.files.src %>**/*.scss' ],
				dest: '<%= sass.dev.files.dest %>'
			}
		},

		sass: {
			dist: {
				options: {
					style: 'compressed',
					sourcemap: 'none',
					compass: true
				},
				files: [ {
					expand: true,
					cwd: 'admin/scss',
					src: [ '**/*.scss' ],
					dest: 'build/admin/css',
					ext: '.min.css'
				},
				{
					expand: true,
					cwd: 'public/scss',
					src: [ '**/*.scss' ],
					dest: 'build/public/css',
					ext: '.min.css'
				} ]
			},
			dev: {
				options: {
					style: 'expanded',
					sourcemap: 'none',
					compass: true
				},
				files: [ {
					expand: true,
					cwd: 'admin/scss',
					src: [ '**/*.scss' ],
					dest: 'admin/css',
					ext: '.css'
				},
				{
					expand: true,
					cwd: 'public/scss',
					src: [ '**/*.scss' ],
					dest: 'public/css',
					ext: '.css'
				} ]
			}
		},

		// uglify targets are dynamically generated by the minify task
		uglify: {
			options: {
				banner: '/*! <%= pkg.name %> <%= ugtargets[grunt.task.current.target].filename %> <%= grunt.template.today("yyyy-mm-dd h:MM:ss TT") %> */\n',
				report: 'min',
			},
		},

		minify: {
			dist: {
				files: grunt.file.expandMapping( [ 'admin/js/**/*.js', '!admin/js/**/*min.js', 'public/js/**/*.js', '!public/js/**/*min.js' ], '', {
					rename: function(destBase, destPath) {
						return destBase+destPath.replace('.js', '.min.js');
					}
				})
			},
		},
	});

	grunt.registerTask( 'default', [
			'wp_readme_to_markdown',
			'newer:jscs',
			'newer:jshint',
			'newer:phpcs',
			'newer:delegate:sass:dev'
	] );

	grunt.registerTask( 'build', [
			'wp_readme_to_markdown',
			'clean:build',
			'newer:delegate:sass:build',
			'newer:minify',
			'copy:build'
	] );

	// delegate stuff
	grunt.registerTask( 'delegate', function() {
		grunt.task.run( this.args.join( ':' ) );
	} );

	// dynamically generate uglify targets
	grunt.registerMultiTask('minify', function () {
		this.files.forEach(function (file) {
			var path = file.src[0],
			target = path.match(/([^.]*)\.js/)[1];

			// store some information about this file in config
			grunt.config('ugtargets.' + target, {
				path: path,
				filename: path.split('/').pop()
			});

			// create and run an uglify target for this file
			grunt.config('uglify.' + target + '.files', [{
				src: [path],
				dest: path.replace(/^(.*)\.js$/, '$1.min.js')
			}]);
			grunt.task.run('uglify:' + target);
		});
	});

	grunt.registerTask('deploy', [
			'phpcs',
			'jshint',
			'jscs',
			'build',
			'wp_deploy:release'
	] );

	grunt.registerTask('trunk', [
			'wp_readme_to_markdown',
			'phpcs',
			'jscs',
			'build',
			'wp_deploy:trunk'
	] );

	grunt.registerTask('assets', [
			'clean:build',
			'copy',
			'wp_deploy:assets'
	] );

};
