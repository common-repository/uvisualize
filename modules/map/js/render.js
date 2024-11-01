require.config({
    paths: {
        'leaflet': 'vendor/leaflet-dev/leaflet',
        'leaflet-oms': 'vendor/leaflet-oms/oms.min'
    },
    shim: {
        'leaflet-oms': {
            deps: ['leaflet']
        }
    }
});

define(['require', 'uvisSetup', 'uvisApp', 'leaflet', 'leaflet-oms'], function(require) {

    var $ = jQuery;
    var L = require('leaflet');

    var app = require('uvisApp');

    // Configuration

    var setup = require('uvisSetup');
    setup.debug && console.info("Init Map Render");

    var mapSetup = {
        title: 'Map',
        id: 'map',
        url: uvisurlbase + 'modules/map/templates/map.html',
    };

    setup.extendModule(mapSetup);

    var uvisMapTemplate = uvisurlbase + 'modules/map/templates/map.html';

    app.factory('uvisMapMaker', ['$compile','$timeout','uvisBackend','uvisPlayer', function($compile, $timeout, uvisBackend, uvisPlayer) {
        var uvisData = uvisBackend.uvisData();
        var PL = uvisBackend.playlist();
        var map;
        var markers;
        var markersOMS;
        var group;
        var api = {
            map: function() {
                return map;
            },
            initBindings: function(graceful) {
                var data = uvisData = uvisBackend.uvisData();

                // reset bindings when not graceful
                if (!graceful) {
                    PL.moduleBindings = {};
                }
                _.each(PL.getItems(), function(item){
                    // Item has geodata?
                    var hasgeo = false;
                    if (item.geo_latitude !== undefined && item.geo_longitude !== undefined) {
                       hasgeo = true;
                    } else {
                      if (item.geotag_latitude !== undefined && item.geotag_longitude !== undefined) {
                        item.geo_latitude = item.geotag_latitude;
                        item.geo_longitude = item.geotag_longitude;
                        hasgeo = true;
                      }
                      if (item._wp_geo_latitude !== undefined && item._wp_geo_longitude !== undefined ) {
                        item.geo_latitude = item._wp_geo_latitude;
                        item.geo_longitude = item._wp_geo_longitude;
                        hasgeo = true;
                      }
                    }
                    // FIXME: for some reasons, some non/geo items have 0,0?
                    if (item.geo_latitude === 0 && item.geo_longitude === 0) {
                       hasgeo = false;
                    }
                    // Fallback to defaults
                    // FIXME: get this from itemConf (bind to uvisData)
                    var m = {};
                    m.ID = item.ID;
                    m.icon = ( item.uvis_map_marker_icon ) ? item.uvis_map_marker_icon : data.config.uvis_map_default_marker_icon;
                    m.mcol = ( item.uvis_map_marker_color > 0 ) ? item.uvis_map_marker_color : data.config.uvis_map_default_marker_color;
                    m.fcol = ( item.uvis_map_box_font_color > 0 ) ? item.uvis_map_box_font_color : data.config.uvis_map_default_font_color;
                    m.backcol = ( item.uvis_map_box_background_color > 0 ) ? item.uvis_map_box_background_color : data.config.uvis_map_default_box_background_color;
                    m.bordercol = ( item.uvis_map_box_border_color > 0 ) ? item.uvis_map_box_border_color : data.config.uvis_map_default_box_border_color;
                    m.font = data.config.uvis_map_default_font + ',Arial,Sans-serif';

                    if (PL.moduleBindings[item.ID] === undefined) {
                        PL.moduleBindings[item.ID] = {};
                    }
                    var bindings = PL.moduleBindings[item.ID];
                    if (!graceful) {
                        bindings.mapPopup = L.popup({
                            maxWidth: "700",
                            minWidth: "400",
                            maxHeight: "600"
                        });
                    }
                    var popContainer = $('<div class="uvisMapPopupWrap"></div>');
                    // FIXME: redundant move to colors Function
                    popContainer.css( "border", "1px solid " + m.bordercol );
                    popContainer.css( "font-family", m.font );
                    popContainer.css( "background", m.backcol );
                    popContainer.css( "color", m.fcol );
                    var permalink = (PL.getPermalink(item) != "" && PL.getPermalink(item) !== undefined) ? '<div class="uvisMarkerPermalink">' + PL.getPermalink(item) + '</div>' : '';
                    popContainer.append($('<h4 class="uvisMarkerTitle"></h4>').text(PL.getTitle(item)).append(permalink));
                    var content = (PL.getDate(item) != undefined) ? '<span class="uvisMarkerDate">' + PL.getDate(item) + '</span>' + ( PL.getContent(item) || '' ) : ( PL.getContent(item) || '' );
                    popContainer.append($('<div class="uvisMarkerComment"></div>').html(content));
                    popContainer.append($(uvisPlayer.makePlayerContainers(item)));
                    bindings.mapPopup.setContent(popContainer[0].outerHTML);
                    // put bindings in popupobject to get colors in popupopen event
                    bindings.mapPopup.uvisM = m;
                    if (graceful) {
                        bindings.mapPopup.update();
                        api.applyColors(m);
                        uvisPlayer.convertPlayerContainers();
                    } else if (hasgeo) {
                        // FIXME: find better method to show/hide marker layers on change
                        var uvisIcon = L.icon({
                            iconUrl: uvisurlbase + 'modules/map/images/' + m.icon.iconID + '.php?col=' + m.mcol.substring(1),
                            shadowUrl: '',
                            iconSize:     eval(m.icon.iconSize), // size of the icon
                            shadowSize:   [0, 0], // size of the shadow
                            iconAnchor:   eval(m.icon.iconAnchor), // point of the icon which will correspond to marker's location
                            shadowAnchor: [0, 0],  // the same for the shadow
                            popupAnchor:  eval(m.icon.popupAnchor) // point from which the popup should open relative to the iconAnchor
                        });
                        bindings.mapMarker = L.marker([item.geo_latitude, item.geo_longitude], { icon: uvisIcon });
                        // Inject reference to popup in marker
                        bindings.mapMarker.uvisPopup = bindings.mapPopup;
                        bindings.mapMarker.bindPopup(bindings.mapPopup);
                        markersOMS.addMarker(bindings.mapMarker);
                        bindings.mapMarker.addTo(markers);
                    } else {
                        bindings.nogeo = true;
                    }
                });
                // end each

                map.addLayer(markers);
                if (!data.config.uvis_map_cluster_markers) {
                    markersOMS.clearMarkers();
                }

            },
            initMap: function() {
                var data = uvisData = uvisBackend.uvisData();
                var basemap;
                if (map !== undefined) {
                    map.remove();
                }
                // TODO: make initmap a service
                basemap = _.findWhere(data.basemaps, { handle: data.config.uvis_map_basemap });
                if (basemap === undefined) {
                    basemap = _.first(data.basemaps);
                }
                data.config.uvis_map_pos = data.config.uvis_map_pos || [22, 72];
                data.config.uvis_map_zoom = data.config.uvis_map_zoom || basemap.maxZoom || 2;
                setup.debug && console.log('Initializing Map');
                var tileLayer = L.tileLayer(basemap.url, {
                        attribution: basemap.description,
                        subdomains: basemap.subdomains || "",
                        maxZoom: basemap.maxZoom || 18
                });
                map = L.map('uvisMap',{
                    center: data.config.uvis_map_pos,
                    zoom: data.config.uvis_map_zoom,
                    zoomAnimation: true,
                    layers: [tileLayer]
                });
                markersOMS = new OverlappingMarkerSpiderfier(map, {
                    "keepSpiderfied": true,
                    "circleSpiralSwitchover": 9,
                    "nearbyDistance": 20
                });
                markers = new L.LayerGroup();
                api.initBindings();
                map.on("popupclose", function(){
                    $(".uvisWrap .leaflet-tile-pane").css({
                        "opacity":"1",
                        "background":"transparent"
                    });
                    $(".uvisWrap .leaflet-container").css({
                      "background-color": "#ddd"
                    });
                });
                map.off('moveend').on('moveend', function(){
                    $timeout(function() {
                        uvisData.config.uvis_map_pos = [map.getCenter().lat, map.getCenter().lng];
                    }, 0);
                });
                map.off('zoomend').on('zoomend', function(){
                    markersOMS.unspiderfy();
                    $timeout(function() {
                        uvisData.config.uvis_map_zoom = map.getZoom();
                    }, 0);
                });
                map.off('popupopen').on('popupopen', function(e){
                    api.applyColors(e.popup.uvisM);
                    window.setTimeout(uvisPlayer.convertPlayerContainers,0);
                });
                if (uvisData.config.uvis_map_autocenter &&  markersOMS.getMarkers().length > 0) {
                    group = new L.featureGroup(markersOMS.getMarkers());
                    map.fitBounds(group.getBounds());
                }
                if (setup.debug) {
                    window.uvisMap = map;
                }
                return map;
            },
            applyColors: function(uvisM) {
                $(".leaflet-popup-content-wrapper, .leaflet-popup-tip").css({
                    "background-color": uvisM.backcol,
                    "background": uvisM.backcol,
                    "color": uvisM.fcol,
                    "border-color": uvisM.bordercol
                });
            },
            panTo: function(plid) {
                mbind = PL.moduleBindings[plid];
                if (mbind.mapMarker !== undefined) {
                    map.panTo(mbind.mapMarker.getLatLng());
                    mbind.mapMarker.fire("click");
                    mbind.mapMarker.openPopup();
                } else if (mbind.mapMarker === undefined) {
                    markersOMS.unspiderfy();
                    mbind.mapPopup.options.className = "nogeo";
                    mbind.mapPopup.setLatLng(map.getCenter()).openOn(map);
                    $(".uvisWrap .leaflet-tile-pane").css({
                        "opacity":"0.5"
                    });
                    $(".uvisWrap .leaflet-container").css({
                      "background-color":"#000"
                    });
                }
            },
            playlistSelect: function(plid) {
                api.panTo(plid);
            },
            updateFilterDisplay: function() {
                // TODO: Update markers gracefully
                this.initMap();
            },
            updateColors: function() {
                var data = uvisData = uvisBackend.uvisData();
                // Update colors gracefully
                _.each(PL.getItems(), function(item){
                    // FIXME: redundant with initBindings
                    var m = {};
                    m.ID = item.ID;
                    m.icon = ( item.uvis_map_marker_icon ) ? item.uvis_map_marker_icon : data.config.uvis_map_default_marker_icon;
                    m.mcol = ( item.uvis_map_marker_color > 0 ) ? item.uvis_map_marker_color : data.config.uvis_map_default_marker_color;
                    m.fcol = ( item.uvis_map_box_font_color > 0 ) ? item.uvis_map_box_font_color : data.config.uvis_map_default_font_color;
                    m.backcol = ( item.uvis_map_box_background_color > 0 ) ? item.uvis_map_box_background_color : data.config.uvis_map_default_box_background_color;
                    m.bordercol = ( item.uvis_map_box_border_color > 0 ) ? item.uvis_map_box_border_color : data.config.uvis_map_default_box_border_color;
                    m.font = data.config.uvis_map_default_font + ',Arial,Sans-serif';
                    var bindings = PL.moduleBindings[item.ID];
                    var popContainer = $('<div class="uvisMapPopupWrap"></div>');
                    popContainer.css( "background-color", m.backcol );
                    popContainer.css( "color", m.fcol );
                    popContainer.css( "font-family", m.font );
                    popContainer.css( "border", "1px solid " + m.bordercol );
                    $('.leaflet-popup-content-wrapper').css( "background-color", m.backcol );
                    var permalink = (PL.getPermalink(item) != "" && PL.getPermalink(item) !== undefined) ? '<div class="uvisMarkerPermalink">' + PL.getPermalink(item) + '</div>' : '';
                    popContainer.append($('<h4 class="uvisMarkerTitle"></h4>').text(PL.getTitle(item)).append(permalink));
	                  var content = (PL.getDate(item) !== undefined) ? '<span class="uvisMarkerDate">' + PL.getDate(item) + '</span>' + ( PL.getContent(item) || '' ) : ( PL.getContent(item) || '' );
	                  popContainer.append($('<div class="uvisMarkerComment"></div>').html(content));
                    if (data.config.uvis_attachment_display) {
                        popContainer.append($(uvisPlayer.makePlayerContainers(item)));
                    }
                    bindings.mapPopup.setContent(popContainer[0].outerHTML);
                    bindings.mapPopup.uvisM = m;
                    bindings.mapPopup.update();
                    if (bindings.mapPopup._isOpen) {
                        api.applyColors(m);
                    }
                    if (bindings.mapMarker) {
                        var uvisIcon = L.icon({
                            iconUrl: uvisurlbase + 'modules/map/images/' + m.icon.iconID + '.php?col=' + m.mcol.substring(1),
                            shadowUrl: '',
                            iconSize:     eval(m.icon.iconSize), // size of the icon
                            shadowSize:   [0, 0], // size of the shadow
                            iconAnchor:   eval(m.icon.iconAnchor), // point of the icon which will correspond to marker's location
                            shadowAnchor: [0, 0],  // the same for the shadow
                            popupAnchor:  eval(m.icon.popupAnchor) // point from which the popup should open relative to the iconAnchor
                        });
                        bindings.mapMarker.setIcon(uvisIcon);
                    }
                });
                // end each
            },
            updateItemDisplay: function() {
                // FIXME: could be more gracefully
                api.initBindings(true);
            }
        };
        mapSetup.uvisRender = api;
        return api;
    }]);

    app.controller('uvisMapController', ['$scope', 'uvisBackend', 'uvisMapMaker', function ($scope, uvisBackend, uvisMapMaker) {
        var data = uvisBackend.uvisData();
        var map = uvisMapMaker.initMap($scope);
    }]);

});