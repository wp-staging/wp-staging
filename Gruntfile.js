/**
 * Deployment script for WP Staging Pro
 * 
 * What it does:
 * 
 * - Copy source files into folder ../../releases/wp-staging-pro/x.x.x/
 * - Creates zip file ../../releases/wp-staging-pro/x.x.x/wp-staging-pro.zip ready to be uploaded into WordPress
 * - String replace of {{version}} in source code files with version number
 * 
 * Makes use of the grunt taskrunner based on npm
 * Install grunt https://gruntjs.com/installing-grunt
 * 
 * How to use:
 * 
 * 1. Change the version number in package.json
 * 2. Run 'npm install' (Initially one time)
 * 3. Run 'grunt build'
 */
module.exports = function (grunt) {

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        paths: {
            // Destination of tagged releases
            releaseDir: '../../releases/wp-staging-pro/<%= pkg.version %>/wp-staging-pro/',
            // Files in this folder will be compressed
            zipDir: '../../releases/wp-staging-pro/<%= pkg.version %>/',
        },

        // Copy to build folder
        copy: {
            build: {
                files: [
                    {
                        // Copy to base folder
                        expand: true,
                        src: [
                            '**',
                            '!node_modules/**',
                            '!Gruntfile.js',
                            '!package.json',
                            '!nbproject/**',
                            '!grunt/**',
                            '!.gitignore',
                            '!CHANGELOG.md',
                            '!CONTRIBUTING.md',
                            '!README.md',
                            '!selenium-server-standalone-3.141.5.jar',
                            '!chromedriver.exe',
                            '!codecept.phar',
                            '!composer.json',
                            '!composer.lock',
                            '!codeception.yml',
                            '!codecept.bat',
                            '!selenium.bat',
                            '!run-test.bat',
                            '!tests/**',
                            '!idea/**',
                            '!codecept-multisite.bat',
                            '!codecept-single.bat',
                            '!codecept-singlesubdir.bat',
                            '!.git/**',
                            '!package-lock.json',
                            '!docker'
                        ],
                        cwd: 'src/',
                        dest: '<%= paths.releaseDir %>'
                    },
                ]
            },
        },
        'string-replace': {
            version: {
                files: {                    
                    '<%= paths.releaseDir %>wp-staging-pro.php': '<%= paths.releaseDir %>wp-staging-pro.php',
                    '<%= paths.releaseDir %>readme.txt': '<%= paths.releaseDir %>readme.txt',
                    '<%= paths.releaseDir %>Core/WPStaging.php': '<%= paths.releaseDir %>Core/WPStaging.php',
                },
                options: {
                    replacements: [{
                            pattern: /{{version}}/g,
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
                    {src: ['<%= paths.releaseDir %>']},
                    {src: ['<%= paths.zipDir %>']},
                ]

            }
        },
        // Compress the build folder into an upload-ready zip file
        compress: {
            build: {
                options: {
                    archive: '<%= paths.zipDir %><%= pkg.name %>.zip' //target
                },
                cwd: '<%= paths.zipDir %>',
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
    grunt.registerTask(
            'build',
            ['clean:build', 'copy:build', 'string-replace:version', 'compress:build']
            );
};