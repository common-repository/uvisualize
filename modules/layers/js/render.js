define(['require', 'uvisApp', 'uvisSetup', 'modules/layers/js/uvisLayersAPI'], function(require) {

    var $ = jQuery;

    var setup = require('uvisSetup');
    setup.debug && console.info('Init Layers Render');

    var app = require('uvisApp');

    var layersSetup = {
        title: 'Layers',
        id: 'layers',
        url: uvisurlbase + 'modules/layers/templates/layers.html'
    };

    setup.extendModule(layersSetup);

    var uvisLayersAPI = require('modules/layers/js/uvisLayersAPI');

    var uvisLayers = uvisLayersAPI.uvisLayers;
    var uvisLayer = uvisLayersAPI.uvisLayer;

    app.factory('uvisLayersMaker', ['$compile','$timeout','uvisBackend','uvisPlayer', function($compile, $timeout, uvisBackend, uvisPlayer) {
        var uvisData = uvisBackend.uvisData();
        var PL = uvisBackend.playlist();
        var layers;
        var api = {
            layers: function() {
                return layers;
            },
            initLayers: function(){
                uvisData = uvisBackend.uvisData();
                setup.debug && console.log('Initializing Layers');
                options = {
                    axis: uvisData.config.uvis_layers_axis || "z",
                    originX: uvisData.config.uvis_layers_originX,
                    originY: uvisData.config.uvis_layers_originY
                };
                layers = new uvisLayers('uvisLayersContainer', PL, uvisPlayer, options);
                layers.setMode(options);
                layers.makeLayers(PL);
                var colors = {
                    "backgroundColor": uvisData.config.uvis_layers_default_screen_background_color,
                    "popupBackgroundColor": uvisData.config.uvis_layers_default_box_background_color,
                    "popupBorderColor": uvisData.config.uvis_layers_default_box_border_color,
                    "popupFontColor": uvisData.config.uvis_layers_default_box_font_color,
                    "popupFont": uvisData.config.uvis_layers_default_box_font
                };
                layers.addArrows(uvisurlbase + 'modules/layers/images/layersarrow.php?col=' + colors.popupBackgroundColor.substring(1) + "&col2=" + colors.popupBorderColor.substring(1));
                layers.updateColors(colors);

                //uvisPlayer.convertPlayerContainers();
            },
            playlistSelect: function(plid) {
                // MOVE TO ITEM
                layers.showLayer(plid);
            },
            updateFilterDisplay: function() {
                // TODO Update gracefully
                layers.setMode();
            },
            updateDisplayMode: function(options) {
                layers.setMode(options);
            },
            updateItemDisplay: function() {
                // FIXME: UPDATE STUFF GRACEFULLY
                layers.makeLayers(PL);
            },
            updateColors: function() {
                console.log("update colors?");
                layers.updateColors();
            }
        };
        layersSetup.uvisRender = api;
        return api;
    }]);

    app.controller('uvisLayersController', ['$scope', 'uvisBackend', 'uvisLayersMaker', function ($scope, uvisBackend, uvisLayersMaker) {
        var layers = uvisLayersMaker.initLayers();
    }]);

});