require.config({
    paths: {
        'uvisLayersRender': 'modules/layers/js/render'
    }
});

define(['require', 'uvisSetup', 'uvisApp', 'uvisLayersRender'], function(require) {

    var $ = jQuery;

    var app = require('uvisApp');

    var setup = require('uvisSetup');
    setup.debug && console.info('Init Layers');

    var gettext = function(src) { return src; };

    var layersSetup = {
        title: gettext('Layers'),
        id: 'layers',
        secondaryTabs: [
            {
                title: gettext('Layers'),
                url: uvisurlbase + 'modules/layers/templates/settings.html',
                id: 'layerstab'
            }, {
                title: gettext('Content'),
                id: 'itemdisplay',
                url: uvisurlbase + 'modules/visualizer/templates/itemdisplay.html'
            }, {
                title: gettext('Playlist'),
                id: 'playlistsettings',
                url: uvisurlbase + 'modules/visualizer/templates/playlist_settings.html',
            },
            {
                title: gettext('Filters'),
                id: 'filters',
                url: uvisurlbase + 'modules/visualizer/templates/filters_settings.html'
            }
        ]
    };

    setup.extendModule(layersSetup);

    app.controller('uvisLayersSettingsController', ['$scope', 'gettext', 'uvisBackend','uvisLayersMaker', function ($scope, gettext, uvisBackend, uvisLayersMaker) {
        var layers = uvisLayersMaker.layers();
        var data = uvisBackend.uvisData();
        data.config.uvis_layers_axis = data.config.uvis_layers_axis || "z";
        if (data.config.uvis_layers_originX == undefined) {
            console.error(data.config.uvis_layers_originX);
            data.config.uvis_layers_originX = 100;
            data.config.uvis_layers_originY = 0;
        }
        $scope.uvisData = data;
        $scope.mapFonts = ['Arial', 'Helvetica', 'Georgia', 'Courier', 'Open Sans'];
        $scope.layersAxis = {
                "z": "3D",
                "x": gettext("Horizontal"),
                "y": gettext("Vertical")
        };
        //$scope.selectedBasemap = _.findWhere(data.basemaps, { handle: data.config.uvis_map_basemap });

        $scope.changeRenderMode = function() {
            layers = uvisLayersMaker.layers();
            uvisLayersMaker.layers().setMode({
                originX:data.config.uvis_layers_originX,
                originY:data.config.uvis_layers_originY,
                axis:data.config.uvis_layers_axis
            });
            // FIXME: prevent rerendering in setMode!!!
            //layers.makeLayers();
        };
        $scope.updateLayersColor = function() {
            layers = uvisLayersMaker.layers();
            var colors = {
                "backgroundColor": $scope.uvisData.config.uvis_layers_default_screen_background_color,
                "popupBackgroundColor": $scope.uvisData.config.uvis_layers_default_box_background_color,
                "popupBorderColor": $scope.uvisData.config.uvis_layers_default_box_border_color,
                "popupFontColor": $scope.uvisData.config.uvis_layers_default_box_font_color,
                "popupFont": $scope.uvisData.config.uvis_layers_default_box_font
            };
            layers.updateColors(colors);
            layers.setArrowsImage(uvisurlbase + 'modules/layers/images/layersarrow.php?col=' + colors.popupBackgroundColor.substring(1) + "&col2=" + colors.popupBorderColor.substring(1));
        };
    }]);

});