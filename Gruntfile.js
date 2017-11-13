module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		uglify: {
			options: {
				banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n',
				beautify: true
			},
			compress: {
				files: {
					'admin/js/wordsync-admin.js': ['admin/js/src/**/*.js']
				}
			}
		},
		compass: {
			dist: {
				options: {
					sassDir: 'admin/scss',
					cssDir: 'admin/css',
					environment: 'production',
					outputStyle: 'compressed', //nested expanded compact compressed
					noLineComments: true
				}
			},

			dev: {
				options: {
					sassDir: 'admin/scss',
					cssDir: 'admin/css',
					environment: 'development',
					outputStyle: 'nested', //nested expanded compact compressed
					noLineComments: true,
					sourcemap: true
				}
			}

		},
		watch: {
			css: {
				files: 'admin/scss/**/*.scss',
				tasks: ['compass:dev']
			},
			js:
			{
				files: ['admin/js/src/**/*.js'],
				tasks: ['uglify:compress']
			}
		},
		pot: {
			options: {
				text_domain: 'brave-firepress',
				dest: 'languages/',
				keywords: ['gettext', '__', '_e']
			},
			files:{
				src:  [ 'admin/**/*.php', 'includes/**/*.php' ], //Parse all php files
				expand: true,
			}
		}


	});

	// Load the plugin that provides the "uglify" task.
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-compass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-pot');

	grunt.registerTask('dist', ['pot', 'compass:dist','uglify:compress']);
	//grunt.registerTask('pot', ['pot']);

	// Default task(s).
	grunt.registerTask('default', ['compass:dist', 'uglify:compress','watch']);


};
