require.config({
    paths: {
        'uvisTimelineRender': 'modules/timeline/js/render'
    }
});

define(['require','uvisSetup','uvisApp','uvisTimelineRender'], function(require) {

    var $ = jQuery;

    var app = require('uvisApp');

    var setup = require('uvisSetup');
    setup.debug && console.info("Init Timeline");

    var gettext = function(src) { return src; };

    var timelineSetup = {
        title: gettext('Timeline'),
        id: 'timeline',
        secondaryTabs: [
            {
                title: gettext('Timeline'),
                url: uvisurlbase + 'modules/timeline/templates/settings.html',
                id: "timelinetab"
            }, {
                title: gettext('Content'),
                id: 'itemdisplay',
                url: uvisurlbase + 'modules/visualizer/templates/itemdisplay.html'
            }, {
                title: gettext('Filters'),
                id: 'filters',
                url: uvisurlbase + 'modules/visualizer/templates/filters_settings.html'
            }
        ]
    };

    setup.extendModule(timelineSetup);

    app.controller('uvisTimelineSettingsController', ['$scope', 'gettext', 'uvisBackend', 'uvisTimelineMaker', function ($scope, gettext, uvisBackend, uvisTimelineMaker) {
        setup.debug && console.log('uvisTimelineSettingsController called');
        $scope.uvisData = uvisBackend.uvisData();

        if ($scope.uvisData.config.uvis_timeline_display === 1) {
            $scope.uvisData.config.uvis_timeline_display = true;
        }


        $scope.timelineDirection = [ 'begin', 'end' ];
        //FIXME: get from global available timefields!
        $scope.timelineOrder = [ 'post_date', 'post_modified' ];
        // Databinding to uvisData singleton
        $scope.$watch("uvisData.config.uvis_timeline_direction", function(oldval, newval){
            if (oldval !== newval) {
                uvisTimelineMaker.initTimeline();
            }
        });
        $scope.changeSettings = function(){
            uvisTimelineMaker.initTimeline();
        };
    }]);

});
