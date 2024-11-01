require.config({
    paths: {
        'uvisMapRender': 'modules/map/js/render'
    }
});

define(['require', 'uvisSetup', 'uvisApp', 'uvisMapRender'], function(require) {

    var $ = jQuery;

    var app = require('uvisApp');

    // Configuration

    var setup = require('uvisSetup');
    setup.debug && console.info("Init Map");

    var gettext = function(src) { return src; };

    var mapSetup = {
        id: 'map',
        secondaryTabs: [
            {
                title: gettext('Map'),
                url: uvisurlbase + 'modules/map/templates/settings.html',
                id: "maptab"
            }, {
                title: gettext('Content'),
                id: 'itemdisplay',
                url: uvisurlbase + 'modules/visualizer/templates/itemdisplay.html'
            }, {
                title: gettext('Playlist'),
                id: 'playlistsettings',
                url: uvisurlbase + 'modules/visualizer/templates/playlist_settings.html'
            }, {
                title: gettext('Filters'),
                id: 'filters',
                url: uvisurlbase + 'modules/visualizer/templates/filters_settings.html'
            }
        ]
    };

    setup.extendModule(mapSetup);

    app.controller('uvisMapSettingsController', ['$scope', 'gettext', 'uvisBackend', 'uvisMapMaker', function ($scope, gettext, uvisBackend, uvisMapMaker) {
        setup.debug && console.log('uvisMapSettingsController called');
        var map = uvisMapMaker.map();
        var data = uvisBackend.uvisData();

        if (data.config.uvis_map_cluster_markers) {
            data.config.uvis_map_cluster_markers = true;
        } else {
            data.config.uvis_map_cluster_markers = false;
        }

        // Lock marker selection to actual object:
        if (data.config.uvis_map_default_marker_icon && data.config.uvis_map_default_marker_icon.iconID) {
            data.config.uvis_map_default_marker_icon = _.findWhere(data.map_marker_icons, { iconID : data.config.uvis_map_default_marker_icon.iconID });
        } else {
            data.config.uvis_map_default_marker_icon = _.findWhere(data.map_marker_icons, { iconID : "default" });
        }

        $scope.mapFonts = ['Arial', 'Georgia', 'Courier', 'Open Sans', 'Times New Roman'];
        $scope.selectedBasemap = _.findWhere(data.basemaps, { handle: data.config.uvis_map_basemap });
        $scope.onMapSelect = function(){
          data.config.uvis_map_basemap = $scope.selectedBasemap.handle;
          map = uvisMapMaker.initMap($scope);
        };

        $scope.updateMapColor = function() {
          uvisMapMaker.updateColors($scope);
        };

        $scope.updateClusterMarkers = function(){
            map = uvisMapMaker.initMap($scope);
        };

    }]);

});
