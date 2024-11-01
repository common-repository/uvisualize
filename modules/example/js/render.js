// This script file handles the rendering of your module
// if you want to use an external module or anything that works outside of angular,
// you'll need to define it as a dependency here:
//
// require.config({
//    paths: {
//        'ExternalVisulizationTool': 'vendor/path/to/rendermodule'
//    }
//});

// Define dependencies
// uvisSetup stores and serves global settings
// uvisApp is the core app based on angular
// ExternalVisulizationTool could be an external visualization library
// Variables in the anonymous define function work as shorthands for the defined dependiencies (e.g. $, app, setup...)
define(['require', 'jquery', 'uvisApp', 'uvisSetup', /* 'ExternalVisulizationTool' */], function(require, $, app, setup) {

    setup.debug && console.info('Init Example Render');

    // The setup properties for your render module,
    // defines which angular template should be used to wrap the actual visualization
    var exampleSetup = {
        // Visualization title, this is also the label for the main tab.
        title: 'Example',
        // Unique ID this should be set to the modules ID for convenience
        id: 'example',
        // The path to the angular template
        // In case you don't need angular for a 3rd party tool you should
        // still use an angular template as a wrapper
        url: uvisurlbase + 'modules/example/templates/example.html'
    };

    // uvisSetup provides a function to extend Module settings,
    // this must be done to properly register the module to the uvisApp core
    setup.extendModule(exampleSetup);


    // uvisExampleMaker is a factory, basically a singleton that handles events and callbacks
    // for playlist and filter functionality of the visualization core
    // this is also a dependency for the example.js settings controller
    // e.g. to manually trigger updates for 3rd party tools or other non-angular custom code.
    app.factory('uvisExampleMaker', ['$compile','$timeout','uvisBackend','uvisPlayer', function($compile, $timeout, uvisBackend, uvisPlayer) {
        // Expose uvisData to the whole render api forcing a closure for data-consistency
        var uvisData = uvisBackend.uvisData();
        // Same for the Playlist, the Playlist itself exposes several functions and properties
        // that can be either used conveniently in an angular template or pulled manually
        // for custom handling of callbacks and events.
        // Disclaimer: The playlist api is still very beta and  not yet fully documented.
        // Also it currently reflects the need to work with multiple and very different
        // visualization tools. Thus redundant approaches and functions can be found.
        // The playlist code can be found in the core visualizer module js/visualizer.js.
        var PL = uvisBackend.playlist();
        // Dummy for external 3d party tool
        var example;
        // The api is returned by the factory
        // Usually creating an API here should be done to integrate any 3rd party tools
        // or custom visualization that don't rely on angular toa fit the app's need
        // to propagate callbacks and actions.
        var api = {
            // Example on how to expose a third party tool to the settings controller,
            // example would be assign to an instance of e.g. a map in the init function.
            example: function() {
                return example;
            },
            // Init will be called by the uvisExampleController, but basically it's up to
            // you how you want to structure your custom visualization.
            init: function(){
                // When using a third party tool
                // example = new 3rdPartyTool();
                // Get uvisData to access uvisData.config etc. to init your visualization
                uvisData = uvisBackend.uvisData();
                // Setup provides a debug flag
                setup.debug && console.log('Initializing Example');
                api.updateFont();
            },
            // Triggered by the playlist whenever an item has been selected
            playlistSelect: function(plid) {
                setup.debug && console.log('Playlist item selected', plid);
                return;
            },
            // Triggered by the playlist on any filter action
            updateFilterDisplay: function() {
                setup.debug && console.log('Filter setting has been changed');
                return;
            },
            // Triggered by the playlist when Content settings have changed
            updateItemDisplay: function() {
                setup.debug && console.log('Content settings have been changed');
                return;
            },
            // Custom function, assigned by the the settings controller
            updateFont: function(font) {
                setup.debug && console.log('Change font');
                // In this custom example style handling is done via jQuery,
                // for an angular example see the color picker part in the example.html template
                if (font === undefined && uvisData.config) {
                    font = uvisData.config.uvis_example_default_font;
                }
                $("#uvisExample").css("font-family", font);
            },
            // Custom function, assigned by the the settings controller
            updateColors: function() {
                // In this example the style handling is actually done in the template
                setup.debug && console.log('Some colors have been changed');
                return;
            }
        };
        // uvisRender is used by the visualizer module to trigger callbacks:
        exampleSetup.uvisRender = api;
        // return the api object, accessible in other angular modules
        // that have uvisExampleMaker as dependency:
        return api;
    }]);

    // angular controller for the base template of the module
    app.controller('uvisExampleController', ['$scope', '$sce', 'uvisBackend', 'uvisExampleMaker', function ($scope, $sce, uvisBackend, uvisExampleMaker) {
        // Bind uvisData to the $scope
        $scope.uvisData = uvisBackend.uvisData();
        // Use the playlist and its properties in the template scope
        $scope.PL = uvisBackend.playlist();
        // Init the renderer as soon as the base template is called:
        var example = uvisExampleMaker.init();
    }]);

});