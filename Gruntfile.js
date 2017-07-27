/* local path for wp-staging git repository
 cd "s:\github\wp-staging"
 * 
 */
module.exports = function (grunt) {

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        paths: {
            // Base destination dir
            base: '../../wordpress-svn/wp-staging/tags/<%= pkg.version %>/',
            basetrunk: '../../wordpress-svn/wp-staging/trunk/',
            basezip: '../../wordpress-svn/wp-staging/'
        },
        // minify js
        uglify: {
            build: {
                files: [
                    {'assets/js/wpstg-admin.min.js': 'assets/js/wpstg-admin.js'},
                    {'assets/js/wpstg.min.js': 'assets/js/wpstg.js'},
                ]
            }
        },
        // Copy to build folder
        copy: {
            build: {
                files: [
                    {
                        // Copy to base foldedr
                        expand: true,
                        src: [
                            '**',
                            '!node_modules/**',
                            '!Gruntfile.js',
                            '!package.json',
                            '!nbproject/**',
                            '!grunt/**',
                            '!wp-staging-pro.php',
                            '!.gitignore',
                            '!CHANGELOG.md',
                            '!CONTRIBUTING.md',
                            '!README.md',
                        ],
                        dest: '<%= paths.base %>'
                    },
                    {
                        // Copy to basetrunk foldedr
                        expand: true,
                        src: [
                            '**',
                            '!node_modules/**',
                            '!Gruntfile.js',
                            '!package.json',
                            '!nbproject/**',
                            '!grunt/**',
                            '!wp-staging-pro.php',
                            '!.gitignore',
                            '!CHANGELOG.md',
                            '!CONTRIBUTING.md',
                            '!README.md',
                        ],
                        dest: '<%= paths.basetrunk %>'
                    }
                ]
            },
        },
        'string-replace': {
            version: {
                files: {
                    '<%= paths.basetrunk %>wp-staging.php': 'wp-staging.php',
                    '<%= paths.base %>readme.txt': 'readme.txt',
                    '<%= paths.base %>wp-staging.php': 'wp-staging.php',
                    '<%= paths.basetrunk %>readme.txt': 'readme.txt',
                },
                options: {
                    replacements: [{
                            pattern: /{{ version }}/g,
                            replacement: '<%= pkg.version %>'
                        }]
                }
            }
        },
        // Clean the build folder
        clean: {
            options: {
                force: true
            },
            build: {
                files: [
                    {src: ['<%= paths.base %>']},
                    {src: ['<%= paths.basetrunk %>']},
                ]

            }
        },
        // Minify CSS files into NAME-OF-FILE.min.css
        cssmin: {
            build: {
                files: [
                    {'assets/css/wpstg-admin.min.css': 'assets/css/wpstg-admin.css'},
                    {'templates/wpstg.min.css': 'templates/wpstg.min.css'},
                ]
            }
        },
        // Compress the build folder into an upload-ready zip file
        compress: {
            build: {
                options: {
                    archive: '<%= paths.basezip %><%= pkg.name %>.zip' //target
                },
                cwd: '<%= paths.basetrunk %>',
                src: ['**/*'],
                expand: true
            }
        }


    });

    // Load all grunt plugins here
    require('load-grunt-tasks')(grunt);

    // Display task timing
    require('time-grunt')(grunt);

    // Build task
    //grunt.registerTask( 'build', [ 'compress:build' ]);
    //grunt.registerTask( 'build', [ 'clean:build', 'uglify:build', 'copy:build', 'string-replace:version', 'compress:build' ]);
    grunt.registerTask(
            'build',
            ['clean:build', 'copy:build', 'string-replace:version', 'compress:build']
            );
};