/* local path for wp-staging git repository
cd "s:\github\wp-staging"
 * 
 */
module.exports = function(grunt) {

    // Project configuration.
    grunt.initConfig({
                
        pkg: grunt.file.readJSON( 'package.json' ),
        paths : {
            // Base destination dir
            base : '../../wordpress-svn/staging/tags/<%= pkg.version %>',
            basetrunk : '../../wordpress-svn/staging/svn/trunk/',
            basezip: '../../wordpress-svn/staging/' 
        },

        // Tasks here
        // Bump version numbers
        version: {
            css: {
                options: {
                    prefix: 'Version\\:\\s'
                },
                src: [ 'style.css' ]
            },
            php: {
                options: {
                        prefix: '\@version\\s+'
                },
                src: [ 'functions.php', '<%= pkg.name %>.php' ]
            }
        },
        // minify js
        uglify: {
            build: { 
                files:[
                    {'assets/js/wpstg-admin.min.js' : 'assets/js/wpstg-admin.js'},
                    {'assets/js/wpstg.min.js' : 'assets/js/wpstg.js'},
                ]
            }
        },
        // Copy to build folder
        copy: {
            build: {             
                files: [
                    {expand: true, src: ['**', '!node_modules/**', '!Gruntfile.js', '!package.json', '!nbproject/**', '!grunt/**'],                
                     dest: '<%= paths.base %>'},
                 
                    {expand: true, src: ['**', '!node_modules/**', '!Gruntfile.js', '!package.json', '!nbproject/**', '!grunt/**'],
                    dest: '<%= paths.basetrunk %>'},
                ]                
            },
        },

        // Clean the build folder
        clean: {
            options: { 
                force: true 
            },
            build: {
                files:[
                    {src: ['<%= paths.base %>']},
                    {src: ['<%= paths.basetrunk %>']},
                ]
               
            }
        },
        // Minify CSS files into NAME-OF-FILE.min.css
        cssmin: {
            build: { 
                files:[
                    {'assets/css/wpstg-admin.min.css' : 'assets/css/wpstg-admin.css'},
                    {'templates/wpstg.min.css' : 'templates/wpstg.min.css'},
                ]
            }
        },
        // Compress the build folder into an upload-ready zip file
        compress: {
            build: {
                options: {
                    archive: '<%= paths.basezip %>/<%= pkg.name %>.zip'
                },
                cwd: '<%= paths.base %>',
                src: ['**/*']
                //dest: '../../',
                //expand: true
            }
        }


    });

    // Load all grunt plugins here
    // [...]
    //require('load-grunt-config')(grunt);
    require('load-grunt-tasks')(grunt);
    
    // Display task timing
    require('time-grunt')(grunt);

    // Build task
    //grunt.registerTask( 'build', [ 'compress:build' ]);
    grunt.registerTask( 'build', [ 'clean:build', 'uglify:build', 'cssmin:build', 'copy:build', 'compress:build' ]);
};