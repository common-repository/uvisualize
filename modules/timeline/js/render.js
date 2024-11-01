require.config({
    paths: {
        'timelinejs': 'vendor/timeline/js/timeline-min',
        'timelinejspatch': 'modules/timeline/js/patch'
    },
    shim: {
        'timelinejspatch': {
            deps: ['timelinejs']
        }
    }
});


define(['require','uvisApp','uvisSetup', 'timelinejspatch'], function(require) {

    var $ = jQuery;

    var app = require('uvisApp');

    var setup = require('uvisSetup');
    setup.debug && console.info("Init Timeline Render");

    var timelineSetup = {
        title: 'Timeline',
        id: 'timeline',
        url: uvisurlbase + 'modules/timeline/templates/timeline.html'
    };


    // FIXME: TimelineJS breaks injected HTML, so we break timelinejs
    VMM.Util.properQuotes = function(str) { return str; }

    setup.extendModule(timelineSetup);

    timelineSetup.render = app.factory('uvisTimelineMaker', ['$scope', 'uvisBackend', function ($scope, uvisBackend) {
        setup.debug && console.log('uvisTimelineMaker Factory Init');

        // Databinding to uvisData singleton
        var data = uvisBackend.uvisData();

    }]);

    app.factory('uvisTimelineMaker', ['$compile','uvisBackend','uvisPlayer', 'gettextCatalog', function($compile, uvisBackend, uvisPlayer, gettextCatalog) {
        var lang = gettextCatalog.getCurrentLanguage();
        $.getScript(uvisurlbase + 'vendor/timeline/js/locale/'+lang+'.js').fail(function(){
            console.error("Failed loading of Timelinejs Locale, reverting to 'en'");
            $.getScript(uvisurlbase + 'vendor/timeline/js/locale/en.js');
        });

        var uvisData = uvisBackend.uvisData();
        var PL = uvisBackend.playlist();
        var timeline;
        var api = {
            timeline: function() {
                return timeline;
            },
            dateStrFmt: function(wpdate){
                // convert date format on string level
                return wpdate.slice(0,4) + "," + wpdate.slice(5,7) + "," + wpdate.slice(8,10);
            },
            initData: function() {
                setup.debug && console.log('Initializing Timeline Data');
                uvisData = uvisBackend.uvisData();
                PL = uvisBackend.playlist();

                var tl_headline = uvisData.config.uvis_post_title;
                var tl_text = "";
                if (uvisData.config.uvis_post_content && uvisData.config.uvis_post_content.length > 0) {
                    tl_text = uvisData.config.uvis_post_content.replace(/\n/g,"<br>");
                }

                this.timelineData = {
                    "timeline":
                    {
                        "type":"default",
                        "headline": tl_headline,
                        "text": tl_text,
                        "asset": {
                            "media":uvisData.playlist.featured_image,
                            //"credit":"Credit Name Goes Here",
                            //"caption":"Caption text goes here"
                        },
                        "font": "Lora-Istok",
                        "era": [
                        ]
                    }
                };

                this.tl_config = {
                    debug: false,
                    width: '100%',
                    height: '100%',
                    type: 'timeline',
                    font: 'Lekton-Molengo',
                    hash_bookmark: false,
                    source: api.timelineData,
                    embed_id: 'uvisTimelineEmbed'
                };
                if (uvisData.config.uvis_timeline_direction === "end") {
                    api.tl_config.start_at_end = true;
                } else {
                    delete api.tl_config.start_at_end;
                }

                var dates = [];
                _.each(PL.getItems(), function(item){
                    var bindings = {};
                    // FIXME: make method to get right dates;
                    var ymd = api.dateStrFmt(PL.getDateRaw(item));
                    var content = PL.getContent(item) || '';
                    if (PL.getPermalink(item) != "" && PL.getPermalink(item) !== undefined) {
                        content = content + '<div class="uvisTimelinePermalink">' + PL.getPermalink(item) + '</div>';
                    }
                    var date_item =  {
                        "startDate": ymd,
                        "endDate": ymd,
                        "headline": PL.getTitle(item),
                        "text": "<div class='uvisTimelineContentWrap'>" + content.replace(/\n/g,"<br>") + "</div>",
                        "font": "Lora-Istok",
                        "asset": {}
                    };
                    if (item.attachments.length > 0 && uvisData.config.uvis_attachment_display) {
                        date_item.asset.media = uvisPlayer.makePlayerScript(item);
                    }
                    dates.push(date_item);

                    // Add bindings to PL
                    //PL.moduleBindings[item.ID] = bindings;
                });
                api.timelineData.timeline.date = dates;
                var timerange = PL.getDateSpread(uvisData.config.uvis_timeline_orderby);
                var era = {
                    "startDate": api.dateStrFmt(timerange[0]),
                    "endDate": api.dateStrFmt(timerange[1]),
                    "headline":"Era Headline",
                    "text":"<p>Era Body text goes here, some HTML is OK</p>",
                    "font": "Lora-Istok"
                };
            },
            timelineData: {},
            tl_config: {},
            initTimeline: function(e) {
                var data = uvisBackend.uvisData();
                PL = uvisBackend.playlist();
                setup.debug && console.log('Initializing Timeline');

                $('#uvisTimelineEmbed .back-home').click();
                $("#uvisTimelineEmbed").empty();
                $("#uvisTimelineEmbed").unbind();

                api.initData();


                // TESTING: TimelineJS3
                //timeline = new VCO.Timeline("uvisTimelineEmbed", {}, api.tl_config);

                timeline = new VMM.Timeline("uvisTimelineEmbed");
                timeline.init(api.tl_config);
                if (setup.debug) {
                    window.uvisTimeline = timeline;
                }
                var fields = uvisData.config.uvis_filters_show_fields;
                if (_.contains(fields, "post_date")) {
                   $('#uvisTimelineEmbed').removeClass("hide_dates");
                } else {
                   $('#uvisTimelineEmbed').addClass("hide_dates");
                }
                if (!uvisData.config.uvis_timeline_display) {
                   $('#uvisTimelineEmbed').addClass("hide_vconav");
                } else {
                   $('#uvisTimelineEmbed').removeClass("hide_vconav");
                }

                var checkheight = function(){
                    var cont = $(".uvisTimelineContentWrap");
                    var maxh = $("#uvisTimelineEmbed").height() - 200;
                    cont.each(function(i){
                        var c = $(this);
                        var h = c.height();
                        if (h > maxh) {
                            c.height(maxh);
                        }
                    });
                };
                $("#uvisTimelineEmbed").on("click", ".marker", function (e,a) {
                    checkheight();
                });
                $("#uvisTimelineEmbed").on("click", ".nav-next", function (e,a) {
                    checkheight();
                });
                $("#uvisTimelineEmbed").on("click", ".nav-previous", function (e,a) {
                    checkheight();
                });
                checkheight();

                return timeline;
            },
            playlistSelect: function(plid) {
                // no playlist, just pass
                return;
            },
            updateFilterDisplay: function() {
                this.initTimeline();
                //this.updateItemDisplay();
            },
            updateItemDisplay: function() {
                uvisData = uvisBackend.uvisData();
                this.initTimeline();
                return;
                /* graceful update always fails thanks to buggy timelinejs API */
                api.initData();
                timeline.reload(api.timelineData);
                var fields = uvisData.config.uvis_filters_show_fields;
                if (_.contains(fields, "post_date")) {
                   $('#uvisTimelineEmbed').removeClass("hide_dates");
                } else {
                   $('#uvisTimelineEmbed').addClass("hide_dates");
                }
            }
        };
        //FIXME:  a bit ugly?
        timelineSetup.uvisRender = api;
        return api;
    }]);

    app.controller('uvisTimelineController', ['$scope', 'uvisBackend', 'uvisTimelineMaker', function ($scope, uvisBackend, uvisTimelineMaker) {
        var data = uvisBackend.uvisData();
        var timeline = uvisTimelineMaker.initTimeline($scope);
    }]);


});