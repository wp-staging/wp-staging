/**
 * Deployment script for WP Staging
 *
 * What it does:
 *
 * - Copy source files into folder ../../releases/wp-staging-svn/tags/x.x.x/
 * - Copy source files into folder ../../releases/wp-staging-svn/trunk/
 * - Creates zip file ../../releases/wp-staging-svn/x.x.x/wp-staging-pro.zip ready to be uploaded into WordPress
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
            releaseDirTags: '../../releases/wp-staging-svn/tags/<%= pkg.version %>/',
            releaseDirTrunk: '../../releases/wp-staging-svn/trunk/',
            // Files in this folder will be compressed
            zipDir: '../../releases/wp-staging-svn/<%= pkg.version %>/',
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
        // Copy files
        copy: {
            // Copy shared files of pro version into free version for deployment of latest features
            pro: {
                files: [
                    {
                        expand: true,
                        src: [
                            '**',
                            '!Backend/Pro/**',
                            '!readme.txt',
                            '!wp-staging-pro.php',
                            '!Backend/Optimizer/Optimizer.php',
                            '!Backend/Optimizer/wp-staging-optimizer.php',
                            '!Backend/PluginMeta/Meta.php',
                            '!Core/Utils/Hash.php',

                        ],
                        cwd: '../wp-staging-pro/src/',
                        dest: './src',
                    }
                ]
            },
            build: {
                files: [
                    {
                        // Copy to tags folder
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
                            '!vendor/**',
                            '!idea/**',
                            '!codecept-multisite.bat',
                            '!codecept-single.bat',
                            '!codecept-singlesubdir.bat',
                            '!.git/**',
                            '!package-lock.json',
                            '!docker'
                        ],
                        cwd: 'src/',
                        dest: '<%= paths.releaseDirTags %>',
                    }, {
                        // Copy to trunk folder
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
                            '!vendor/**',
                            '!idea/**',
                            '!codecept-multisite.bat',
                            '!codecept-single.bat',
                            '!codecept-singlesubdir.bat',
                            '!.git/**',
                            '!package-lock.json',
                            '!docker'
                        ],
                        cwd: 'src/',
                        dest: '<%= paths.releaseDirTrunk %>',
                    },
                ]
            },
        },
        'string-replace': {
            version: {
                files: {
                    '<%= paths.releaseDirTags %>wp-staging.php': '<%= paths.releaseDirTags %>wp-staging.php',
                    '<%= paths.releaseDirTags %>readme.txt': '<%= paths.releaseDirTags %>readme.txt',
                    '<%= paths.releaseDirTags %>Core/WPStaging.php': '<%= paths.releaseDirTags %>Core/WPStaging.php',
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
                    {src: ['<%= paths.releaseDirTags %>']},
                    {src: ['<%= paths.releaseDirTrunk %>']},
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
        },

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

    // Copy shared files of pro version into free version for deployment of latest features
    grunt.registerTask(
        'buildfree',
        ['copy:pro']
    );
};