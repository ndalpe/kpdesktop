"use strict";

module.exports = function (grunt) {

    // We need to include the core Moodle grunt file too, otherwise we can't run tasks like "amd".
    require("grunt-load-gruntfile")(grunt);
    grunt.loadGruntfile("../../Gruntfile.js");

    // Load all grunt tasks.
    grunt.loadNpmTasks("grunt-contrib-less");
    grunt.loadNpmTasks("grunt-contrib-watch");
    grunt.loadNpmTasks("grunt-contrib-clean");

    grunt.initConfig({
        watch: {
            // If any .less file changes in directory "less" then run the "less" task.
            files: "scss/*.scss",
            tasks: ["scss"]
        },
        scss: {
            // Production config is also available.
            development: {
                options: {
                    // Specifies directories to scan for @import directives when parsing.
                    // Default value is the directory of the source, which is probably what you want.
                    paths: ["scss/"],
                    compress: true
                },
                files: {
                    "styles.css": "scss/main.scss"
                }
            },
        }
    });
    // The default task (running "grunt" in console).
    grunt.registerTask("default", ["scss"]);
};
