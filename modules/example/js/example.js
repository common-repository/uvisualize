// This script file configures settings and behaviour in the admin interface

// Register your render module as a dependency
require.config({
    paths: {
        'uvisExampleRender': 'modules/example/js/render'
    }
});

// Define dependencies
// uvisSetup stores and serves global settings
// uvisApp is the core app based on angular
// uvisExampleRender is the render module defined above
define(['require', 'uvisSetup', 'uvisApp', 'uvisExampleRender'], function(require) {

    // jQuery is exposed in the wordpress admin interface by default,
    // as an alternative jquery is defined as a dependency with fallback in require.js
    // in the plugin root: e.g. var $ = require('jquery');
    var $ = jQuery;

    // Shorthand to access the app
    var app = require('uvisApp');

    // Shorthand to access setup
    var setup = require('uvisSetup');

    // Setup also provides a debug flag
    setup.debug && console.info('Init Example');

    // gettext is used by the grunt task "extract-messages",
    // basically a dummy wrapper to get your messages for i18n translation
    var gettext = function(src) { return src; };

    // The setup properties for your module
    var exampleSetup = {
        // Visualization settings title, this is also the label for the secondary tab.
        title: gettext('Example'),
        // A unique ID, must not be: "visualizer", "map", "layers", "timeline" or any other existing plugin module name.
        id: 'example',
        // Secondary tabs are rendered as a floating popup in admin interface.
        // You can also define your own in the module, you'll just need some template
        // and an angular controller defined below.
        secondaryTabs: [
            // Main tab with settings for the module, also see below "uvisExampleSettingsController"
            {
                title: gettext('Example'),
                url: uvisurlbase + 'modules/example/templates/settings.html',
                id: 'exampletab'
            }, {
            // Content Tab for item property settings, part of the visualizer core
                title: gettext('Content'),
                id: 'itemdisplay',
                url: uvisurlbase + 'modules/visualizer/templates/itemdisplay.html'
            }, {
            // Tab for playlist settings, part of the visualizer core
                title: uvis_playlist_post_type_name_singular,
                id: 'playlistsettings',
                url: uvisurlbase + 'modules/visualizer/templates/playlist_settings.html',
            },
            // Settings Tab for filter configuration as provided by the visualizer core
            {
                title: gettext('Filters'),
                id: 'filters',
                url: uvisurlbase + 'modules/visualizer/templates/filters_settings.html'
            }
        ]
    };

    // uvisSetup provides a function to extend Module settings,
    // this must be done to properly register the module to the uvisApp core
    setup.extendModule(exampleSetup);

    // this is the angular controller for the Module's Main Tab
    // You'll need some basic Angular knowledge, what $scope is for and how to validate data etc.
    // You can store your visualization's individual settings as defined in your basic WP-module setup,
    // e.g. in example.php. Properties then could be accessed by the uvisData.config object:
    // uvisData.config.uvis_my_fancy_visualization_setting = true, etc...
    app.controller('uvisExampleSettingsController', ['$scope', 'gettext', 'uvisBackend','uvisExampleMaker', function ($scope, gettext, uvisBackend, uvisExampleMaker) {
        // The controller could also access an 3rd party rendermodule to handle callbacks etc.
        // This is just a dummy though:
        var example = uvisExampleMaker.example();
        // Always bind uvisData to the $scope, to easily watch for changes and apply settings without the need
        // to update the template/dom by yourself
        var data = $scope.uvisData = uvisBackend.uvisData();
        // You can also predefine several settings here etc.
        $scope.fonts = ['Arial', 'Helvetica', 'Georgia', 'Courier', 'Open Sans'];
        // If you need any jQuery widgets like the color picker (we've already included one, see the example template)
        // you should write an angular directive and not include any jquery code here.
        $scope.updateColors = uvisExampleMaker.updateColors;
        $scope.changeFont = uvisExampleMaker.updateFont;
    }]);
});