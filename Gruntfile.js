'use strict';
const sass = require('node-sass');

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
					{ expand: true, nonull: true, src: ['readme.txt', 'CHANGELOG.md','*.php'], dest: 'build/' },
					{ expand: true, nonull: true, src: ['admin/**','public/**','includes/**', '!**/scss/**'], dest: 'build/' },
				],
			}
		},

		wp_deploy: {
			options: {
				plugin_slug: 'media-credit',
				svn_url: "https://plugins.svn.wordpress.org/{plugin-slug}/",
				// svn_user: 'your-wp-repo-username',
				build_dir: 'build', //relative path to your build directory
				assets_dir: 'wp-assets', //relative path to your assets directory (optional).
				max_buffer: 1024 * 1024
			},
			release: {
				// nothing
				deploy_trunk: true,
				deploy_tag: true,
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
				reporter: require('jshint-stylish'),
				jshintrc: true,
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
				bin: 'vendor/bin/phpcs -p -s -v -n ',
				standard: './phpcs.xml'
			}
		},

		sass: {
			options: {
				implementation: sass,
			},
			dist: {
				options: {
					outputStyle: 'compressed',
					sourceComments: false,
					sourcemap: 'none',
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
					outputStyle: 'expanded',
					sourceComments: false,
					sourceMapEmbed: true,
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

		postcss: {
			options: {
				map: true, // inline sourcemaps.
				processors: [
					require('pixrem')(), // add fallbacks for rem units
					require('autoprefixer')() // add vendor prefixes
				]
			},
			dev: {
				files: [ {
					expand: true,
					cwd: 'admin/css',
					src: [ '**/*.css' ],
					dest: 'admin/css',
					ext: '.css'
				},
				{
					expand: true,
					cwd: 'public/css',
					src: [ '**/*.css' ],
					dest: 'public/css',
					ext: '.css'
				} ]
			},
			dist: {
				files: [ {
					expand: true,
					cwd: 'build/admin/css',
					src: [ '**/*.css' ],
					dest: 'build/admin/css',
					ext: '.css'
				},
				{
					expand: true,
					cwd: 'build/public/css',
					src: [ '**/*.css' ],
					dest: 'build/public/css',
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
				expand: true,
				//dest: 'build/',
				files: grunt.file.expandMapping( [ 'admin/js/**/*.js', '!admin/js/**/*min.js', 'public/js/**/*.js', '!public/js/**/*min.js' ], 'build/', {
					rename: function(destBase, destPath) {
						return destBase + destPath.replace('.js', '.min.js');
					}
				})
			},
		},
	});

	grunt.registerTask( 'default', [
			'newer:wp_readme_to_markdown',
			'newer:jscs',
			'newer:jshint',
			'newer:phpcs',
			'newer:sass:dev',
			'newer:postcss:dev'
	] );

	grunt.registerTask( 'build', [
			'newer:wp_readme_to_markdown',
			'clean:build',
			'newer:sass:dist',
			'newer:postcss:dist',
			'newer:minify',
			'copy:build'
	] );

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
				dest: path.replace(/^(.*)\.js$/, 'build/$1.min.js')
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
