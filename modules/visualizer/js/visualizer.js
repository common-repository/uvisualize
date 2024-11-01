// TODO: split this up in single modules
define(['require','uvisSetup','uvisApp','sprintf','jqrangeslider','underscore'], function(require) {

    var $ = jQuery;

    var setup = require('uvisSetup');
    var app = require('uvisApp');
    var sprintf = require('sprintf').sprintf;
    var _ = require('underscore');

    var gettext = function(src) { return src; };

    var formatDate = function(inputFormat) {
        // FIXME: use some external date formatting lib?!
        function pad(s) { return (s < 10) ? '0' + s : s; }
        var d = new Date(inputFormat);
        return [pad(d.getDate()), pad(d.getMonth()+1), d.getFullYear()].join('/');
    };

    if (setup.debug) {
        window.uvisSetup = setup;
        window.uvisApp = app;
    }

    setup.debug && console.info("Init Visualizer");

    setup.mainTabs.push({
        title: gettext('Story Editor'),
        id: 'storyeditor',
        url: uvisurlbase + 'modules/visualizer/templates/storyeditor.html',
        secondaryTabs: []
    });


    // This is some workaround to post form data to ajax backend
    app.factory(
        "transformRequestAsFormPost",
        function() {
            function transformRequest( data, getHeaders ) {
                var headers = getHeaders();
                headers[ "Content-Type" ] = "application/x-www-form-urlencoded; charset=utf-8";
                return( serializeData( data ) );
            }
            // Return the factory value.
            return( transformRequest );
            function serializeData( data ) {
                // If this is not an object, defer to native stringification.
                if ( ! angular.isObject( data ) ) {
                    return( ( data == null ) ? "" : data.toString() );
                }
                var buffer = [];
                // Serialize each key in the object.
                for ( var name in data ) {
                    if ( ! data.hasOwnProperty( name ) ) {
                        continue;
                    }
                    var value = data[ name ];
                    buffer.push(
                        encodeURIComponent( name ) +
                        "=" +
                        encodeURIComponent( ( value == null ) ? "" : value )
                    );
                }
                // Serialize the buffer and clean it up for transportation.
                var source = buffer
                    .join( "&" )
                    .replace( /%20/g, "+" )
                ;
                return( source );
            }
        }
    );

    // Workaround to enable disabled options in select lists

    app.directive('optionsDisabled', function($parse) {
        var disableOptions = function(scope, attr, element, data,
                                      fnDisableIfTrue) {
            // refresh the disabled options in the select element.
            var options = element.find("option");
            for(var pos= 0,index=0;pos<options.length;pos++){
                var elem = angular.element(options[pos]);
                if(elem.val()!=""){
                    var locals = {};
                    locals[attr] = data[index];
                    elem.attr("disabled", fnDisableIfTrue(scope, locals));
                    index++;
                }
            }
        };
        return {
            priority: 0,
            require: 'ngModel',
            link: function(scope, iElement, iAttrs, ctrl) {
                // parse expression and build array of disabled options
                var expElements = iAttrs.optionsDisabled.match(
                    /^\s*(.+)\s+for\s+(.+)\s+in\s+(.+)?\s*/);
                var attrToWatch = expElements[3];
                var fnDisableIfTrue = $parse(expElements[1]);
                scope.$watch(attrToWatch, function(newValue, oldValue) {
                    if(newValue)
                        disableOptions(scope, expElements[2], iElement,
                            newValue, fnDisableIfTrue);
                }, true);
                // handle model updates properly
                scope.$watch(iAttrs.ngModel, function(newValue, oldValue) {
                    var disOptions = $parse(attrToWatch)(scope);
                    if(newValue)
                        disableOptions(scope, expElements[2], iElement,
                            disOptions, fnDisableIfTrue);
                });
            }
        };
    });

    app.factory('uvisPlayer', function($http, $sce, uvisBackend) {
        // Returns a MediaElement player instance and a simple gallery wrapper for multiple items
        var uvisData = uvisBackend.uvisData();
        var PL = uvisBackend.playlist();
        var api = {};
        api.MEjsplayers = [];
        api.playerUI = $("<div class='uvisPlayerUI'><div class='uvisPlayerItems'></div></div>");
        api.playerUIControls = $("<div class='uvisPlayerUIControls noselect'><div class='uvisPlayerPrev disabled'>&#9664;</div><div class='uvisPlayerCounter'>1/1</div><div class='uvisPlayerNext'>&#9654;</div>");
        api.updateUI = function(playerUI){
            var curr = playerUI.find(".uvisPlayerItems .uvisPlayerContainer.selected");
            var countall = playerUI.find(".uvisPlayerItems .uvisPlayerContainer").length;
            var countcurr = playerUI.find(".uvisPlayerItems .uvisPlayerContainer.selected").index()+1;
            playerUI.find(".uvisPlayerCounter").text(countcurr + "/" + countall);
            if (countcurr === 1) {
                playerUI.find('.uvisPlayerPrev').addClass("disabled");
            } else {
                playerUI.find('.uvisPlayerPrev').removeClass("disabled");
            }
            if (countcurr === countall) {
                playerUI.find('.uvisPlayerNext').addClass("disabled");
            } else {
                playerUI.find('.uvisPlayerNext').removeClass("disabled");
            }
        };
        api.bindPlayerUI = function(){
            $(".uvisWrap").off("click.playerUIPrev").on("click.playerUIPrev", ".uvisPlayerUI .uvisPlayerPrev, .uvisPlayerUI .uvisPlayerNext", function(e){
                e.preventDefault();
                e.stopPropagation();
                var me = $(this);
                var prevnext = me.hasClass("uvisPlayerPrev") ? "prev" : "next";
                var playerUI = me.parent().parent()
                var curr = playerUI.find(".uvisPlayerItems .uvisPlayerContainer.selected");
                if (curr[prevnext]() && curr[prevnext]().length > 0) {
                    curr.removeClass("selected")[prevnext]().addClass("selected");
                }
                api.updateUI(playerUI);
                curr = playerUI.find(".uvisPlayerItems .uvisPlayerContainer.selected");
                playerUI.resize();
                playerUI.trigger("autoPlay");
            });
            $(".uvisWrap").off("autoPlay").on("autoPlay", ".uvisPlayerUI", function(e){
                e.preventDefault();
                e.stopPropagation();
                var playerUI = $(this);
                var curr = playerUI.find(".uvisPlayerItems .uvisPlayerContainer.selected");
                uvisData = uvisBackend.uvisData();
                if (uvisData.config && uvisData.config.uvis_module !== "timeline") {
                    var avnode = curr.find("audio, video");
                    api.pauseAll();
                    api.autoPlay();
                    return;
                    if (avnode.length > 0) {
                        if (PL.paused === true && uvisData.config.uvis_animation_autoplay) {
                            return false;
                        } else if (!uvisData.config.uvis_attachment_autoplay) {
                            return false;
                        } else {
                            api.play(curr.attr("data-uvis-id"));
                        }
                    }
                }
            });

            //$(".uvisWrap").off("click.playerImageZoom").on("click.playerImageZoom", ".uvisPlayerImage:not(.uvisPlayerImageBig)", function(e){
            $(".uvisWrap").off("click.playerImageZoom").on("click.playerImageZoom", ".uvisPlayerImage", function(e){
                var media = $(this).clone();
                media.addClass("uvisPlayerImageBig");
                var lightbox = $('<div class="uvisImageFullscreen"></div>');
                lightbox.width($( window ).width());
                lightbox.height($( window ).height());
                lightbox.append(media.clone());
                lightbox.click(function(){
                    lightbox.remove();
                });
                $("body").append(lightbox);
            });

        };

        api.makePlayerContainers = function(item) {

            var attachments = PL.getAttachments(item);
            if (attachments.length === 0 || uvisData.config.uvis_attachment_display === false) {
                return undefined;
            }
            //type.split("/")[0]
            var players = [];
            var playerUI = api.playerUI.clone();
            var UIID = "uvisPlayerUI" + item.ID;
            playerUI.attr("id", UIID);
            _.each(attachments, function(attachment){
                players.push(api.makePlayerContainer(attachment));
            });

            _.each(players, function(player){
                playerUI.find('.uvisPlayerItems').append($(player));
            });
            playerUI.find(".uvisPlayerContainer").first().addClass("selected");
            if (attachments.length > 1) {
                var UIControls = api.playerUIControls.clone();
                playerUI.prepend(UIControls);
                UIControls.find(".uvisPlayerCounter").text("1/" + attachments.length);
                api.bindPlayerUI();
            }
            return playerUI[0].outerHTML;
        };

        api.makePlayerContainer = function(attachment) {
            // TODO: Implement other media types
            if (attachment !== undefined && attachment.post_mime_type.split("/")[0] === "audio") {
                // AUDIO
                var container = $('<div class="uvisPlayerContainer uvisTypeAudio"></div>');
                container.attr("id", "uvisPlayer" + attachment.ID);
                var title = $('<div class="uvisPlayerContainerTitle"></div>')
                title.text(attachment.post_title);
                var media = $('<audio controls></audio>');
                // FIXME
                //media.attr('src', attachment.post_attachment_url);
                media.attr('data-container-id', container.attr("id"));
                media.attr('src', attachment.guid);
                media.attr('type', attachment.post_mime_type);
                media.attr('preload', "none");
                media.text("Error player failed");
                container.append(title);
                container.append(media);
                return container[0].outerHTML;
            } else if (attachment !== undefined && attachment.post_mime_type.split("/")[0] === "image") {
                // IMAGE
                var container = $('<div class="uvisPlayerContainer uvisTypeImage"></div>');
                container.attr("id", "uvisPlayer" + attachment.ID);
                var title = $('<div class="uvisPlayerContainerTitle"></div>')
                title.text(attachment.post_title);
                var media = $('<div class="uvisPlayerImage storify"></div>');
                media.attr('data-container-id', container.attr("id"));
                // FIXME
                //media.css({'background-image': sprintf('url(%s)', attachment.post_attachment_url) });
                media.css({'background-image': sprintf('url(%s)', attachment.guid) });
                container.append(media);
                container.append(title);
                return container[0].outerHTML;
            } else if (attachment !== undefined && attachment.post_mime_type.split("/")[0] === "video") {
                // VIDEO
                var container = $('<div class="uvisPlayerContainer uvisTypeVideo"></div>');
                container.attr("id", "uvisPlayer" + attachment.ID);
                var title = $('<div class="uvisPlayerContainerTitle"></div>')
                title.text(attachment.post_title);
                var media = $('<video controls></video>');
                // FIXME
                //media.attr('src', attachment.post_attachment_url);
                media.attr('data-container-id', container.attr("id"));
                media.attr('src', attachment.guid);
                media.attr('type', attachment.post_mime_type);
                media.attr('preload', "none");
                media.text("Error player failed");
                container.append(title);
                container.append(media);
                return container[0].outerHTML;
            } else if (attachment !== undefined) {
                // ATTACHMENT
                var container = $('<div class="uvisPlayerContainer uvisTypeDocument"></div>');
                container.attr("id", "uvisPlayer" + attachment.ID);
                //var title = $('<div class="uvisPlayerContainerTitle"></div>')
                //title.text(attachment.post_title);
                var media = $('<a class="button button-primary"></a>');
                // FIXME
                //media.attr('href', attachment.post_attachment_url);
                media.attr('data-container-id', container.attr("id"));
                media.attr('href', attachment.guid);
                media.attr('target', "_blank");
                media.attr('data-type', attachment.post_mime_type);
                media.html('<span class="dashicons dashicons-media-document"></span>' + attachment.post_title + ' (' + attachment.post_mime_type.split("/")[1] +')');
                //media.text();
                container.append(title);
                container.append(media);
                return container[0].outerHTML;
            } else {
                return ""
            }
        };

        api.play = function(MEjsID){
            var startme = _.findWhere(api.MEjsplayers, { id: MEjsID });
            if (startme !== undefined) {
                startme.player.load();
                startme.player.play();
            } else {
                console.error("uvisPlayer: autoplay failed", MEjsID, api.MEjsplayers);
            }
        };
        api.autoPlay = function(PLItemID){
            PL = uvisBackend.playlist();
            var tries = 0;
            var tryPlay = function(){
                uvisData = uvisBackend.uvisData();
                if (!uvisData.config) {
                    return false;
                }
                if (PL.paused === true && uvisData.config.uvis_animation_autoplay) {
                    return false;
                } else if (!uvisData.config.uvis_attachment_autoplay) {
                    return false;
                }
                if (PLItemID === undefined) {
                    PLItemID = uvisBackend.playlist().currentItem.ID;
                }
                var id = "uvisPlayerUI" + PLItemID;
                var playerUI = $("#"+id);
                if (playerUI.length < 1) {
                    // No playerUI found, forward to next item in playlist
                    var oplayer = new uvisDummyPlayer();
                    oplayer.ended(function(){
                        if (PL._last === false) {
                            PL.$.trigger("next");
                        } else {
                            PL.pause();
                        }
                    });
                    api.addPlayer(oplayer, PLItemID);
                    api.play(PLItemID);
                } else {
                    var selPlayer = playerUI.find(".uvisPlayerContainer.selected");
                    var MEjsID = selPlayer.attr("data-uvis-id");
                    api.play(MEjsID);
                }
                api.updateUI(playerUI);
                return;





                var mediaItem = $("#"+id).find("audio, video");
                /**/
                var mediaItem = $("#"+id).find(".uvisPlayerImage, .uvisPlayerVideo, .uvisPlayerAudio, uvisPlayerAttachment");
                /**/
                var containerID = mediaItem.attr("data-container-id");
                var currItem = playerUI.find(".selected");
                if (currItem.length < 1) {
                    currItem = playerUI.find("#" + containerID);
                }
                //var mediaItem = currItem.find("audio, video");
                var MEjsID = mediaItem.attr("data-uvis-id");
                if (MEjsID !== undefined) {
                    api.play(MEjsID);
                    return true;
                } else {
                    return false;
                }
            };
            if (api.timeout !== undefined) {
                window.clearTimeout(api.timeout);
            }
            // TODO: make this a promise and do some retries?!
            api.pauseAll();
            api.timeout = window.setTimeout(function(){
                tryPlay();
            },500);
        };
        api.pauseAll = function(){
            _.each(api.MEjsplayers, function(i){
                i.player.pause();
            });
        };
        // register autoPlay in Playlist to avoid cross-dependencies
        PL.autoPlay = api.autoPlay;
        PL.autoPause = api.pauseAll;

        api.addPlayer = function(player, id) {
            var indexer = {
                id: id,
                player: player
            };
            var changeme = _.findWhere(api.MEjsplayers, { id: id });
            if (changeme !== undefined) {
                api.MEjsplayers[_.indexOf(api.MEjsplayers, changeme)] = indexer;
            } else {
                api.MEjsplayers.push({
                    id: id,
                    player: player
                });
            }
        };
        window.uvisPlayers = api.MEjsplayers;

        api.convertPlayerContainers = function(fallback) {
            var veles = $(".uvisPlayerContainer");
            _.each(veles, function(el, k){
                var vele = $(el);
                var va = vele.find("audio, video");
                _.each(va, function(VAElem, kk){
                    vele.attr("data-uvis-id", k + kk + vele.attr("ID"));
                    var playerID = vele.attr("data-uvis-id");
                    var vplayer = new MediaElementPlayer(VAElem, {
                        defaultVideoWidth: 500,
                        defaultVideoHeight: 260,
                        videoWidth: 500,
                        videoHeight: 260,
                        audioWidth: "100%",
                        audioHeight: 30,
                        enableAutosize: true,
                        pauseOtherPlayers: true,
                        success: function(player, node) {
                            player.addEventListener('ended', function(e){
                                var $node = $(node);
                                var playerUI = $node.parents().find(".uvisPlayerUI");
                                var currID = $node.attr("data-container-id");
                                var currItem = playerUI.find("#"+currID);
                                var items = playerUI.find(".uvisPlayerItems .uvisPlayerContainer");
                                var nextItem = currItem.next(".uvisTypeAudio, .uvisTypeVideo, .uvisTypeDocument, .uvisTypeImage");
                                if (nextItem.length > 0) {
                                    items.removeClass("selected");
                                    nextItem.addClass("selected");
                                    api.updateUI(playerUI);
                                    api.autoPlay();
                                } else {
                                    PL.$.trigger("next");
                                }
                            });
                            player.addEventListener('timeupdate', function(e){
                                var pos = vplayer.media.currentTime;
                                var dur = vplayer.media.duration;
                                var perc = pos/dur * 100;
                                PL.$.trigger("progress", perc);
                            });
                        }
                    });
                    api.addPlayer(vplayer, playerID);
                    vplayer.showControls();
                });
                if (vele.is(".uvisTypeImage, .uvisTypeDocument")) {
                    vele.attr("data-uvis-id", k + vele.attr("ID"));
                    var playerID = vele.attr("data-uvis-id");
                    var oplayer = new uvisDummyPlayer();
                    oplayer.ended(function(){
                        var playerUI = vele.parent().parent().parent();
                        var currItem = playerUI.find(".selected");
                        var items = playerUI.find(".uvisPlayerItems .uvisPlayerContainer");
                        var nextItem = currItem.next(".uvisTypeAudio, .uvisTypeVideo, .uvisTypeDocument, .uvisTypeImage");
                        if (nextItem.length > 0) {
                            items.removeClass("selected");
                            nextItem.addClass("selected");
                            api.updateUI(playerUI);
                            api.autoPlay();
                        } else {
                            PL.$.trigger("next");
                        }

                    });
                    api.addPlayer(oplayer, playerID);
                }
            });
        };

        var uvisDummyPlayer = function(){
            var uvisData = uvisBackend.uvisData();
            this.anirate = 60;
            this.dur = uvisData.config.uvis_animation_delay;
            this.progress = 0;
        };
        uvisDummyPlayer.prototype.tick = function(){
            var me = this;
            this.anitimeout = window.setTimeout(function(){
                me.progress = me.progress + me.anirate;
                if (me.progress >= me.dur) {
                    PL.$.trigger("progress", 100);
                    me.cb();
                } else {
                    var pos = me.progress;
                    var dur = me.dur;
                    var perc = pos/dur * 100;
                    PL.$.trigger("progress", perc);
                    me.tick();
                }
            }, this.anirate);
        };
        uvisDummyPlayer.prototype.pause = function(){
            if (this.timeout !== undefined) {
                window.clearTimeout(this.timeout);
            }
            if (this.anitimeout !== undefined) {
                window.clearTimeout(this.anitimeout);
            }
        };
        uvisDummyPlayer.prototype.load = function(){
            return;
        };
        uvisDummyPlayer.prototype.play = function(){
            var uvisData = uvisBackend.uvisData();
            this.dur = uvisData.config.uvis_animation_delay;
            if (uvisData && !uvisData.config.uvis_animation_autoplay) {
                return;
            }
            var cb = this.cb;
            this.timeout = window.setTimeout(function(){
                if (cb && typeof cb === "function") {
                    //cb();
                }
            }, this.dur);
            this.tick();
        };
        uvisDummyPlayer.prototype.ended = function(cb){
            this.cb = cb;
        };

        api.convertPlayerContainersGeneric = function() {
            var $ = jQuery;
            var veles = $(".uvisPlayerContainer");
            _.each(veles, function(el, k){
                var vele = $(el);
                var va = vele.find("audio, video");
                _.each(va, function(VAElem, kk){
                    $(VAElem).attr("data-uvis-id", kk+k + veles.attr("id"));
                    var vplayer = new MediaElementPlayer(VAElem, {
                        defaultVideoWidth: 500,
                        defaultVideoHeight: 260,
                        videoWidth: 500,
                        videoHeight: 260,
                        audioWidth: "100%",
                        audioHeight: 30,
                        enableAutosize: true,
                        pauseOtherPlayers: true
                    });
                    vplayer.showControls();
                });
            });
       };

        api.makePlayerScript = function(item) {
            // FIXME: Used by Timeline as the Timeline API is stupid
            // returns script tag with standalone bindings
            // TODO: make player global and independend?
            var c = api.makePlayerContainers(item);
            if (c !== undefined && c.length > 0) {
                var html = $(c)[0].outerHTML;
                html = html + "<script>var uvisConvertPlayer = " + api.convertPlayerContainersGeneric.toString() + "; uvisConvertPlayer(" + item.ID + ");</script>";
                api.bindPlayerUI();
                return html;
            } else {
                // return undefined to prevent rendering of disabled attachments
                return undefined;
            }
        };

        return api;
    });

    app.factory('uvisBackend', function($http, $log, $sce, $timeout, transformRequestAsFormPost) {
        // Uvis Data singleton
        var uvisData;
        var PLID = window.uvisPLID || undefined;
        // Which Playlist Items Keys should be processed for config.uvis_item_config:
        // FIXME: this probably will not make sense as story title and commonts will
        // not overwrite post_title and post_content?
        var itemConfigKeys = ["title", "comment"];
        var currentModule;
        // Playlist Singleton
        var PLItems;
        var playlist = {
            sorted: [],
            filtered: [],
            ranged: [],
            processed:[],
            getSorted: function(){
                return this.sorted;
            },
            getItems: function(){
                // returns items filtered, ranged and sorted according to settings
                // TODO: this should recognize module setup??
                // FIXME: we should cache this???
                return uvisData.PLItemsProcessed = this.processed = this.filter(this.range(this.sort(this.items())));
            },
            makeItems: function(){
                uvisData.PLItemsProcessed = this.processed = this.filter(this.range(this.sort(this.items())));
                this.rangeSort();
            },
            currentItem: {},
            moduleBindings: {},
            filterTax: [],
            animationDelay: 1000,
            paused: false,
            play: function(scope){
                var pl = this;
                pl.paused = false;
                if (scope) {
                    scope.uvisPlay = true;
                }
                $timeout(function() {
                    pl.select(playlist.currentItem);
                    pl.autoPlay();
                }, 200);
                /*
                if (pl._last && scope.uvisPlay === true) {
                    pl.pause();
                    scope.$apply(function(){
                        scope.uvisPlay = false;
                        scope.animation = pl.animation;
                    });
                    return;
                }
                if (scope) {
                    scope.uvisPlay = true;
                }
                if (scope && pl.animation !== undefined) {
                    scope.$apply(function(){
                        $timeout(function() {
                            scope.animation = pl.animation;
                        },0);
                    });
                }
                pl.animation = window.setTimeout(function(){
                    pl.next();
                    pl.play(scope);
                }, playlist.animationDelay);
                */
            },
            pause: function(){
                var pl = this;
                pl.paused = true;
                if (pl.autoPause) {
                    pl.autoPause();
                }
            },
            next: function(){
                var ci = _.indexOf(playlist.processed, playlist.currentItem);
                if (ci > -1 && ci < playlist.processed.length - 1) {
                    playlist.select(playlist.processed[ci+1]);
                } else if (ci === -1) {
                    playlist.select(playlist.currentItem);
                }
                if (ci === playlist.processed.length - 1) {
                    playlist._last = true;
                }
            },
            previous: function(){
                var ci = _.indexOf(playlist.processed, playlist.currentItem);
                if (ci > 0) {
                    playlist.select(playlist.processed[ci-1]);
                } else if (ci === -1) {
                    playlist.select(playlist.currentItem);
                }
            },
            select: function(item){
                this.selectApply(item, true);
            },
            selectApply: function(item, donotapply){
                if (!item) {
                    playlist.pause();
                    return;
                }
                playlist.currentItem = _.findWhere(playlist.processed, { ID: item.ID });
                if (playlist.currentItem === undefined) {
                    playlist.currentItem = _.first(playlist.processed);
                    playlist._last = false;
                }
                if (playlist.processed.length < 1) {
                    playlist._last = false;
                    return;
                }
                if (setup.currentModule !== undefined && setup.currentModule.uvisRender !== undefined && playlist.currentItem !== undefined) {
                    if (typeof setup.currentModule.uvisRender.playlistSelect === "function") {
                        setup.currentModule.uvisRender.playlistSelect(playlist.currentItem.ID);
                    } else {
                        setup.debug && console.error("uvisRender missing 'playlistSelect' callback");
                    }
                }
                var ci = _.indexOf(playlist.processed, playlist.currentItem);
                if (ci === playlist.processed.length - 1) {
                    playlist._last = true;
                } else {
                    playlist._last = false;
                }
                uvisData.currentItem = playlist.currentItem;
                playlist.autoPlay();
                if (playlist.$scope && donotapply !== true) {
                    playlist.$scope.$apply(function(){
                        $timeout(function() {
                            playlist.$scope.uvisData.currentItem = uvisData.currentItem;
                        },0);
                    });
                }
            },
            apply: function(cb){
                if (!playlist.$scope) { return; }
                playlist.$scope.$apply(function(){
                    $timeout(function() {
                        playlist.$scope.uvisData.currentItem = uvisData.currentItem;
                        if (cb && typeof cb === 'function') {
                            cb();
                        }
                    },0);
                });
            },
            items: function(){
                // return originally sorted Items
                var items = [];
                _.each(uvisData.PLItemsOrigSorting, function(val){
                    if (PLItems[val] !== undefined) {
                        items.push(PLItems[val]);
                    }
                });
                return items;
            },
            updateItemDisplay: function(){
                if (setup.currentModule &&
                    setup.currentModule.uvisRender &&
                    typeof setup.currentModule.uvisRender.updateItemDisplay === "function") {
                        setup.currentModule.uvisRender.updateItemDisplay(uvisData.config.uvis_filters_show_fields);
                } else {
                    setup.debug && console.error("uvisRender missing 'updateItemDisplay' callback");
                }
            },
            updateFilterDisplay: function(){
                var ci = _.indexOf(playlist.processed, playlist.currentItem);
                if (ci === -1 && playlist.currentItem !== undefined) {
                    playlist.select(_.first(playlist.processed));
                }
                if (setup.currentModule &&
                    setup.currentModule.uvisRender &&
                    typeof setup.currentModule.uvisRender.updateFilterDisplay === "function") {
                        setup.currentModule.uvisRender.updateFilterDisplay();
                } else {
                    setup.debug && console.error("uvisRender missing 'updateFilterDisplay' callback");
                }
            },
            filterMod: function(taxid){
                // modify the current filter settings
                var ftax = this.filterTax;
                if (_.contains(ftax, taxid)) {
                    ftax = _.without(ftax, taxid);
                } else {
                    ftax.push(taxid);
                }
                this.filterTax = ftax;
                //this.filter();
                this.makeItems();
                this.updateFilterDisplay();
            },
            filterClear: function(){
                this.filterTax = [];
                //this.filter();
                this.makeItems();
                this.updateFilterDisplay();
            },
            filter: function(items){
                var filteredItems = [];
                var filterTax = this.filterTax;
                if (items === null || items === undefined) {
                    items = playlist.items();
                }
                if (filterTax.length > 0) {
                    var filtered = _.filter(items, function(item){
                        var itermids = _.pluck(item.taxonomies, "term_id");
                        var inters = _.intersection(itermids, filterTax);
                        if (inters.length > 0) {
                            filteredItems.push(item);
                        }
                    });
                    this.filtered = filteredItems;
                } else {
                    this.filtered = items;
                }
                return uvisData.PLItemsFiltered = this.filtered;
            },
            sorting: "original",
            setSorting: function(orderby){
                //uvisData.config.uvis_timeline_orderby
            },
            sort: function(items){
                // FIXME: Time sorting by current string format for now (where does the WP formatting come from?)
                // FIXME: we don't have a setting for desc/asc yet!?
                if (items === null || items === undefined) {
                    items = playlist.items();
                }
                if (this.sorting === "original" || this.sorting === undefined) {
                    this.sorted = this.items();
                } else {
                    // FIXME: messy sharing of PLItems across app
                    if (items.length > 0 && items[0].hasOwnProperty(playlist.sorting)) {
                        playlist.sorted = _.sortBy(items, function(item){
                            if (!item.hasOwnProperty(playlist.sorting)){
                                console.error("Illegal sort field!", playlist.sorting);
                            } else {
                                return item[playlist.sorting];
                            }
                        });
                    } else {
                        playlist.sorted = items;
                        console.error("Illegal sort field!", playlist.sorting);
                    }
                }
                return uvisData.PLItemsSorted = playlist.sorted;
            },
            rangeSort: function(sorting){
                sorting = uvisData.config.uvis_filter_by_timerange || "post_date";
                var items = this.sorted;
                if (items.length > 0 && sorting !== undefined) {
                    playlist.range_sorted = _.sortBy(items, function(item){
                        if (!item.hasOwnProperty(sorting)){
                            console.error("Illegal sort field!", sorting);
                        } else {
                            return item[sorting];
                        }
                    });
                } else {
                    playlist.range_sorted = items;
                    console.error("Illegal sort field!", sorting);
                }
                return uvisData.PLItemsRangeSorted = playlist.range_sorted;
            },
            rangeMin: undefined,
            rangeMax: undefined,
            range: function(items){
                if (items === null || items === undefined) {
                    items = playlist.items();
                }
                if (!uvisData.config.uvis_filters_timerange_enable) {
                    return items;
                }
                var sortby = uvisData.config.uvis_filter_by_timerange;
                var getDate = this.getDateObj;
                var filtered = [];
                if (this.rangeMin === undefined || this.rangeMax === undefined) {
                    var minmax = this.getDateSpread(sortby);
                    playlist.rangeMin = new Date(minmax[0].replace(/-/g, '/'));
                    playlist.rangeMax = new Date(minmax[1].replace(/-/g, '/'));
                }
                _.each(items, function(item){
                    var idate = getDate(item, sortby);
                    if ((playlist.rangeMin <= idate) && (idate <= playlist.rangeMax)) {
                        filtered.push(item);
                    }
                });
                playlist.ranged = filtered;
                return uvisData.PLItemsRanged = playlist.ranged;
            },
            setDateRange: function(min,max){
                if (!min || !max) {
                    return;
                }
                this.rangeMin = min;
                this.rangeMax = max;
                this.makeItems();
                playlist.updateFilterDisplay();
                if (playlist.$scope) {
                    playlist.$scope.$apply(function(){
                        $timeout(function() {
                            playlist.$scope.uvisData.PLItemsProcessed = uvisData.PLItemsProcessed;
                        },0);
                    });
                }
            },
            getBindings: function(item) {
                return playlist.moduleBindings[item.ID];
            },
            getDate: function(item) {
                if (_.contains(uvisData.config.uvis_filters_show_fields, "post_date") !== true) {
                    return undefined;
                }
                return item[uvisData.config.uvis_timeline_orderby];
            },
            getDateRaw: function(item) {
                // Get Date regardless of itemDisplayOptions
                return item[uvisData.config.uvis_timeline_orderby];
            },
            getDateObj: function(item, orderby) {
                // Get Date regardless of itemDisplayOptions and as Date Object
                orderby = orderby || uvisData.config.uvis_timeline_orderby;
                var dstr = item[orderby];
                return new Date(dstr.replace(/-/g, '/'));
            },
            getDateSpread: function(orderby) {
                if (orderby === null || orderby === undefined) {
                    // FIXME does this happen??
                    console.error("orderby undefined");
                    orderby = "post_date";
                }
                var lsorted = _.sortBy(PLItems, function(item){
                    return item[orderby];
                });
                return [ _.first(lsorted)[orderby], _.last(lsorted)[orderby] ];
            },
            getTitle: function(item) {
                if (_.contains(uvisData.config.uvis_filters_show_fields, "post_title") !== true) {
                    return undefined;
                }
                if (item.title && item.title != "") {
                    return item.title;
                } else {
                    return item.post_title;
                }
            },
            getContent: function(item) {
                if (_.contains(uvisData.config.uvis_filters_show_fields, "post_content") !== true) {
                    return undefined;
                }
                if (item.comment && item.comment != "") {
                    return item.comment;
                } else {
                    return item.post_content;
                }
            },
            getContentHTML: function(item) {
                if (_.contains(uvisData.config.uvis_filters_show_fields, "post_content") !== true) {
                    return undefined;
                }
                if (item.comment && item.comment != "") {
                    return item.comment;
                } else {
                    return item.post_contentHTML;
                }
            },
            getPermalink: function(item) {
                if (_.contains(uvisData.config.uvis_filters_show_fields, "post_permalink") !== true) {
                    return undefined;
                }
                if (item.post_permalink && item.post_permalink != "") {
                    return '<a href="' + item.post_permalink + '" target="_blank" title="Open permalink"><span class="dashicons dashicons-admin-links"></span></a>';
                } else {
                    return "";
                }
            },
            getPermalinkHTML: function(item) {
                if (_.contains(uvisData.config.uvis_filters_show_fields, "post_permalink") !== true) {
                    return undefined;
                }
                if (item.post_permalink && item.post_permalink != "") {
                    return item.post_permalinkHTML;
                } else {
                    return "";
                }
            },
            countAttachments: function(item) {
                var countit = function(type){
                    var result = _.filter(item.attachments, function(attachment){
                        return attachment.post_mime_type.split("/")[0] === type;
                    });
                    return result.length;
                };
                var mediacount = [
                    {
                        'type': 'audio',
                        'count': countit("audio")
                    },
                    {
                        'type': 'video',
                        'count': countit("video")
                    },
                    {
                        'type': 'image',
                        'count': countit("image")
                    },
                    {
                        'type': 'document',
                        'count': countit("application")
                    }
                ];

                // 'count': _.where(item.attachments, { post_mime_type: 'audio/mpeg' }).length
                return mediacount;
            },
            getAttachments: function(item) {
                var attachments = [];
                var mtypes = _.map(uvisData.config.uvis_attachment_show_mediatypes, function(type){
                    if (type === "document") {
                        return "application";
                    } else {
                        return type;
                    }
                });
                _.each(item.attachments, function(attachment) {
                    if ( _.contains(mtypes, attachment.post_mime_type.split("/")[0])) {
                        attachments.push(attachment);
                    }
                });
                return attachments;
            },
            init: function(data) {
                this.$ = $(playlist);
                // Bind next/previous event
                this.$.on('next previous', function(e){
                    e.stopPropagation();
                    var bar = $("#uvisProgress .uvisProgressBar");
                    if (bar.length > 0) {
                        bar.width(0+"%");
                    }
                    playlist.apply(playlist.autoPlay);
                });
                this.$.on('progress', function(e, perc){
                    e.stopPropagation();
                    var bar = $("#uvisProgress .uvisProgressBar");
                    if (bar.length > 0) {
                        bar.width(perc+"%");
                    }
                });

                var itemconf = data.config.uvis_item_config;
                // Reset Playlist Data
                this.filterTax = [];
                this.moduleBindings = {};

                // Init PLItems
                data.PLItems = PLItems = _.object(_.pluck(data.items, 'ID'), data.items);
                //data.PLItemsOrigSorting = _.pluck(data.items, "ID");
                // FIXME: backend hacked to include items original order
                data.PLItemsOrigSorting = data.items_order;
                // Prepare module bindings
                _.each(_(PLItems).keys(), function(id){
                    playlist.moduleBindings[id] = {};
                });
                // Enhance Items with config data
                _.each(PLItems, function(item){
                    var confitem = _.findWhere(itemconf, { 'post_id': item.ID });
                    if (confitem) {
                      _.extend(item, _.pick(confitem, itemConfigKeys));
                    }
                });
                // Prepare stuff for template
                _.each(PLItems, function(item){
                    item.mediacount = playlist.countAttachments(item);
                    item.post_contentHTML = $sce.trustAsHtml(item.post_content);
                    item.post_permalinkHTML = $sce.trustAsHtml(playlist.getPermalink(item));
                });
                // FIXME: only sort when timeline is current module
                //playlist.sort(uvisData.config.uvis_timeline_orderby);
                playlist.makeItems();
                //playlist.select(_.first(playlist.sorted));

                if (setup.debug) {
                    window.uvisPlaylist = playlist;
                }
            }
        };
        var api = {
            cache: {},
            addUvis: function(playlist_id, uvis_title, successcb) {
                PLID = playlist_id;
                return $http({ url: ajaxurl,
                        method: "POST",
                        params: { "action":"uvis_add_visualization" },
                        data: {
                            "playlist_id": playlist_id,
                            "title": uvis_title
                        }
                }).success(function(data, status, headers, config) {
                        if (data.error) {
                            console.warn('addUvis: Creation failed.')
                        } else if (data.result) {
                            uvisData = data.result;
                            api.initUvisData();
                        } else {
                            console.error('No result.')
                        }
                        if (successcb) { successcb(data); }
                        //$log.info(data, status, headers(), config)
                }).error(function(data, status, headers, config) {
                        $log.warn(data, status, headers(), config)
                });
            },
            deleteUvis: function(uvis_id, successcb) {
                $('.uvisWrap').addClass('loading');
                return $http({ url: ajaxurl,
                        method: "POST",
                        params: { "action":"uvis_delete_visualization" },
                        data: { "visualization_id": uvis_id }
                }).success(function(data, status, headers, config) {
                        $('.uvisWrap').removeClass('loading');
                        if (successcb) { successcb(data); }
                        //$log.info(data, status, headers(), config)
                }).error(function(data, status, headers, config) {
                        $log.warn(data, status, headers(), config)
                });
            },
            getUvis: function(uvis_id, successcb) {
                $('.uvisWrap').addClass('loading');
                return $http({ url: ajaxurl,
                        method: "POST",
                        //transformRequest: transformRequestAsFormPost,
                        params: { "action":"uvis_get_visualization" },
                        data: { "visualization_id": uvis_id }
                }).success(function(data, status, headers, config) {
                        // TODO: proper errorhandling
                        $('.uvisWrap').removeClass('loading');
                        setup.debug && console.log('uvisData LOADED', data);
                        if (data.error !== undefined) {
                            console.error('getUvis: Loading failed.');
                        } else if (data.result) {
                            uvisData = data.result;
                            api.initUvisData();
                            // FIXME: not very failsave?
                            PLID = data.result.playlist.ID;
                        } else {
                            console.error('AJAX failed: No result.');
                            data = {};
                            data.error = [{
                                error_msg: "Unauthorized",
                                id: 401
                            }];
                        }
                        if (successcb) { successcb(data); }
                }).error(function(data, status, headers, config) {
                        $log.warn(data, status, headers(), config)
                });
            },
            getUvisOnTheFly: function(settings, successcb) {

                $('.uvisWrap').addClass('loading');
                return $http({ url: ajaxurl,
                        method: "POST",
                        //transformRequest: transformRequestAsFormPost,
                        params: { "action":"uvis_get_visualization_on_the_fly" },
                        data: { "settings": settings }
                }).success(function(data, status, headers, config) {
                        // TODO: proper errorhandling
                        $('.uvisWrap').removeClass('loading');
                        if (data.error) {
                            console.error('getUvisOnTheFly: Loading failed.')
                        } else if (data.result) {
                            uvisData = data.result;
                            api.initUvisData();
                            // FIXME: not very failsave?
                            PLID = data.result.playlist.ID;
                        } else {
                            console.error('No result.')
                        }
                        if (successcb) { successcb(data); }
                        //$log.info(data, status, headers(), config)
                }).error(function(data, status, headers, config) {
                        $log.warn(data, status, headers(), config)
                });
            },
            setCurrentModule: function(mid){
                var mod_ids = _.pluck(uvisData.active_modules, 'id');
                if (_(mod_ids).contains(mid)) {
                    uvisData.config.uvis_module = mid;
                    setup.currentModule = _.findWhere(setup.providedModules, { id: mid });
                }
            },
            initUvisData: function(){
                // reset cache
                this.cache = {};
                var data = uvisData;
                var itemconf = data.config.uvis_item_config;

                if (setup.debug) {
                    window.uvisData = data;
                }

                // Configure active modules
                data.mainTabs = [].concat(setup.mainTabs);
                _.each(setup.providedModules, function(m,k){
                    var mod_ids = _.pluck(data.active_modules, 'id');
                    if ( _(mod_ids).contains(m.id) ) {
                        data.mainTabs.push(m);
                    }
                });
                api.setCurrentModule(uvisData.config.uvis_module);

                playlist.init(data);

                data.PLItemsSorted = playlist.sorted;

                // FIXME see below! make taxonomy filters:
                //  uvisData.taxonomies = [
                //    {
                //      "slug": "category",
                //      "terms": {
                //        "Kunstuniversität": 666,
                //        "name": term_id,
                //        ...
                //    },
                //    {...}
                //  ]

                data.taxonomies = [];
                data.taxonomies_flat = [];
            },
            mapTaxonomiesToPLItems: function(){
                var data = uvisData;
                var mapping = data.taxonomies_mapping || cache.taxonomies_mapping;
                var items = PLItems;
                if (!data || !items) { return; }
                _.each(PLItems, function(item, id){
                    if (mapping[id] !== undefined) {
                        item.taxonomies = mapping[id]
                    }
                });

            },
            makeTaxonomies: function(taxdata){
                this.cache.taxonomies = [];
                this.cache.taxonomies_flat = [];
                var cache = this.cache;
                var alltax = _.values(taxdata.result);
                alltax = _.flatten(alltax);
                cache.taxonomies = [];
                cache.taxonomies_flat = [];
                cache.taxonomies_mapping = taxdata.result;
                _.each(_.uniq(_.pluck(alltax, 'taxonomy')), function(taxslug){
                    cache.taxonomies.push({ slug: taxslug, terms: {} });
                });
                _.each(alltax, function(tax){
                    var taxo = _.findWhere(cache.taxonomies, { slug: tax.taxonomy });
                    taxo['terms'][tax.name] = tax.term_id;
                    cache.taxonomies_flat.push({
                        "id": tax.term_id,
                        "term": tax.name,
                        "tax": tax.taxonomy
                    });
                });
                cache.taxonomies_flat = _.uniq(cache.taxonomies_flat, function(tax){
                        return tax.id;
                });
                this.cache = cache;
                if (uvisData) {
                    uvisData.taxonomies = this.cache.taxonomies;
                    uvisData.taxonomies_flat = this.cache.taxonomies_flat;
                    uvisData.taxonomies_mapping = this.cache.taxonomies_mapping;
                    this.mapTaxonomiesToPLItems();
                }
            },
            getTaxonomies: function(playlist_id, successcb) {
                //$('.uvisWrap').addClass('loading');
                return $http({ url: ajaxurl,
                        method: "POST",
                        //transformRequest: transformRequestAsFormPost,
                        params: { "action": "uvis_get_playlist_taxonomies" },
                        data: { "playlist_id": playlist_id }
                }).success(function(data, status, headers, config) {
                        // TODO: proper errorhandling
                        //$('.uvisWrap').removeClass('loading');
                        if (data.error !== undefined) {
                            console.error('getTaxonomies: Loading failed.');
                        } else if (data.result) {
                            //uvisData = data.result;
                            //api.initUvisData();
                            // FIXME: not very failsave?
                            //PLID = data.result.playlist.ID;
                            api.makeTaxonomies(data);
                        } else {
                            console.error('AJAX failed: No result.');
                            data = {};
                            data.error = [{
                                error_msg: "Unauthorized",
                                id: 401
                            }];
                        }
                        if (successcb) { successcb(data); }
                }).error(function(data, status, headers, config) {
                        $log.warn(data, status, headers(), config)
                });
            },
            getTaxonomyTermsByIds: function(termids){
                // FIXME: term_ids are unique, doesn't need tax-slug
                if (uvisData.taxonomies_flat === undefined) { return []; }

                var terms = _.filter(uvisData.taxonomies_flat, function(tax){ return _.contains(termids, tax.id); });

                //var inverted = _.invert(mytax.terms);
                return terms;
            },
            getTaxonomyTerms: function(tax, termIds){
                // FIXME: term_ids are unique, doesn't need tax-slug
                if (uvisData.taxonomies === undefined) { return []; }


                var mytax = _.findWhere(uvisData.taxonomies, {slug: tax});
                if (mytax !==undefined) {
                  var inverted = _.invert(mytax.terms);
                  var terms = _.pick(inverted, termIds);
                  return terms;
                }
                return [];
            },
            updateUvisConfig: function(){
                var data = uvisData;
                var itemconf = data.config.uvis_item_config;
                _.each(PLItems, function(item){
                    var changeme = _.findWhere(itemconf, { 'post_id': item.ID });
                    if (changeme !== undefined) {
                        _.extend(changeme, _.pick(item, itemConfigKeys));
                    } else {
                        changeme = { post_id: item.ID };
                        _.extend(changeme, _.pick(item, itemConfigKeys));
                        itemconf.push(changeme);
                    }
                });
            },
            uvisData: function() {
                return uvisData;
            },
            getAllUvis: function(playlist_id, successcb) {
                var plid = playlist_id || PLID;
                $('.uvisWrap').addClass('loading');
                return $http({ url: ajaxurl,
                        method: "POST",
                        params: { "action":"uvis_get_visualizations" },
                        data: { "playlist_id": PLID }
                }).success(function(data, status, headers, config) {
                        $('.uvisWrap').removeClass('loading');
                        if (successcb) { successcb(data); }
                        //$log.info(data, status, headers(), config)
                }).error(function(data, status, headers, config) {
                        $log.warn(data, status, headers(), config)
                });
            },
            saveUvis: function(uvis_data, successcb) {
                return $http({ url: ajaxurl,
                        method: "POST",
                        //transformRequest: transformRequestAsFormPost,
                        params: { "action":"uvis_save_visualization" },
                        data: { "uvis_data": uvis_data }
                }).success(function(data, status, headers, config) {
                        if (successcb) { successcb(data); }
                        //$log.info(data, status, headers(), config)
                }).error(function(data, status, headers, config) {
                        $log.warn(data, status, headers(), config)
                });
            },
            playlist: function() {
                return playlist;
            }
        };
        if (setup.debug) {
            window.uvisBackend = api;
        }
        return api;
    });

    app.controller('uvisAppController', function($scope, $sce, $rootScope, $http, ngDialog, uvisBackend, gettext) {
        // FIXME: make global wordings dict
        $scope.uvis_playlist_post_type_name_plural = window.uvis_playlist_post_type_name_plural;
        $rootScope.sprintf = sprintf;
        $scope.uvisPlaylist = uvisurlbase + 'modules/visualizer/templates/playlist.html';
        $scope.uvisFilters = uvisurlbase + 'modules/visualizer/templates/filters.html';

        $scope.add = function (playlist_id) {
            $scope.playlist_id = playlist_id;
            var popup = ngDialog.open({
              template: uvisurlbase + 'modules/visualizer/templates/add.html',
              controller: uvisAddPopup,
              closeByDocument: true,
              closeByEscape: true,
              scope: $scope
            });
        };

        $scope.errorPopup = function (errorMsgs) {
            $scope.errorMsgs = errorMsgs;
            $scope.error = uvisurlbase + 'modules/visualizer/templates/error.html';
            var popup = ngDialog.open({
              template: uvisurlbase + 'modules/visualizer/templates/error_popup.html',
              controller: uvisAddPopup,
              closeByDocument: true,
              closeByEscape: true,
              scope: $scope
            });
            return popup;
        };

        $scope.updateUvisList = function(uvisList) {
            $scope.uvisList = uvisList;
        };

        if (window.uvisInitList) {
            $scope.uvisList = window.uvisInitList;
            setup.debug && console.log("Init List Injection");
        }

        $scope.refreshUvisList = function() {
            uvisBackend.getAllUvis(undefined, function(data){
                if (data.error) {
                    console.error('refreshUvisList: Loading failed.')
                } else if (data.result) {
                    $scope.uvisList = data.result;
                } else {
                    console.error('No result.')
                }
            });
        };

        $scope.delete = function (visualization_id) {
            $scope.visualization_id = visualization_id;
            var popup = ngDialog.open({
              template: uvisurlbase + 'modules/visualizer/templates/delete.html',
              controller: uvisDeletePopup,
              closeByDocument: true,
              closeByEscape: true,
              scope: $scope
            });
        };

        $scope.openUvisPopup = function (uvisData) {
            $scope.uvisData = uvisData;
            var popup = ngDialog.open({
              template: uvisurlbase + 'modules/visualizer/templates/visualizer.html',
              controller: uvisPopup,
              closeByDocument: false,
              closeByEscape: false,
              scope: $scope
            });
            popup.closePromise.then(function (data) {
                  uvisBackend.playlist().pause();
            });
        };

        $scope.open = function (uvis_id) {
            setup.debug && console.log("Fetching uvisData", uvis_id);
            uvisBackend.getUvis(uvis_id, function(data) {
                if (data.error !== undefined) {
                    $scope.errorPopup(data.error);
                } else {
                    $scope.openUvisPopup(data.result);
                }
            });
        }

       // TODO: maybe move Viewer code to standalone controller
       $scope.openUvisViewer = function (uvisData) {
            $scope.uvisData = uvisData;
            var popup = ngDialog.open({
              template: uvisurlbase + 'modules/visualizer/templates/viewer.html',
              controller: uvisViewer,
              closeByDocument: false,
              closeByEscape: false,
              scope: $scope
            });
            popup.closePromise.then(function (data) {
                  uvisBackend.playlist().pause();
            });
        };

        $scope.includeView = function (uvis_id) {
            $scope.uvis_id = uvis_id;
            return uvisurlbase + 'modules/visualizer/templates/viewerInclude.html';
        }

        $scope.view = function (uvis_id) {
            setup.debug && console.log("Fetching uvisData", uvis_id);
            var loader = $('<div class="uvisLoading"><div class="spinner"></div></div>');
            loader.show();
            $("body").append(loader);
            uvisBackend.getUvis(uvis_id, function(data) {
                if (data.error !== undefined) {
                    $scope.errorPopup(data.error);
                } else {
                    $scope.openUvisViewer(data.result);
                }
                loader.fadeOut(200, function(){
                    loader.remove();
                });
            });
        }

        $scope.viewonthefly = function (settings) {
            setup.debug && console.log("Fetching uvisData on the fly:" + settings );
            uvisBackend.getUvisOnTheFly(settings, function(data) {
                $scope.openUvisViewer(data.result);
            });
        }

        $scope.copyPermalinkToClipboard = function(uvis_permalink, uvisID) {
            if (uvis_permalink === undefined) {
                try {
                    uvis_permalink = location.href.split(location.pathname)[0] + "/?post_type=uvis_visualization&p=" + uvisID;
                }
                catch(err) {
                    uvis_permalink = gettext("Sorry, there was an error, try reloading the page.");
                }
            }
            window.prompt( gettext("This is your visualization's permalink.\n\nCopy to clipboard: Ctrl+C, Enter"), uvis_permalink);
        }

        $scope.copyShortcodeToClipboard = function(uvisID) {
            if (uvisID === undefined) {
                    uvisID = gettext("Sorry, there was an error, try reloading the page.");
            }
            window.prompt( gettext("Put this shortcode in a post to add a link to this visualization.\n\nCopy to clipboard: Ctrl+C, Enter"), '[uvis id="' + uvisID +  '"]');
        }

				$scope.renderHtml = function(html_code) {
				    return $sce.trustAsHtml(html_code);
				};

        $(window).off("resize.uvis").on("resize.uvis", function(){
            transformLayout(uvisBackend.uvisData());
        });

        // Remove Lock when done.
        $scope.refreshUvisList();
        //$('.uvisWrap').removeClass('loading');
    });

    var uvisViewer = app.controller('uvisViewer', function($scope, uvisBackend) {
        // TODO: make this viewer only, no tabs!
        var uvisData = uvisBackend.uvisData();
        var PL = uvisBackend.playlist();
        var tab = {};

        $scope.switchModule = function(){
            if (uvisData.config.uvis_module !== "") {
                uvisBackend.setCurrentModule(uvisData.config.uvis_module);
                tab = setup.currentModule;
                transformLayout(uvisData);
            } else {
                tab.url = uvisurlbase + 'modules/visualizer/templates/nomodule.html';
                tab.id = "nomodule";
            }
            $scope.currentTab = tab.url;
            $scope.currentTabId = tab.id;
        };

        // we have the data, just set the module
        if (uvisData !== undefined) {
            $scope.switchModule();
        } else {
            // nope, no data, so fetch it, then switch to the module
            setup.debug && console.log("Fetching uvisData", $scope.uvis_id);
            var loader = $('<div class="uvisLoading"><div class="spinner"></div></div>');
            loader.show();
            $("body").append(loader);
            uvisBackend.getUvis($scope.uvis_id, function(data) {
                if (data.error !== undefined) {
                    $scope.errorPopup(data.error);
                } else {
                    $scope.uvisData = data.result;
                    // refresh local variables
                    uvisData = uvisBackend.uvisData();
                    PL = uvisBackend.playlist();
                    // set the module
                    $scope.switchModule();
                }
                loader.fadeOut(200, function(){
                    loader.remove();
                });
            });
        }
    });


    app.controller('uvisAppContainer', function($scope, uvisBackend) {
      // FIXME: we don't need this? currently does nothing.
      return;
    });

    var uvisAddPopup = app.controller('uvisAddPopup', function ($scope, ngDialog, uvisBackend) {
        $scope.uvisTitle = "My Uvis";
        $scope.save = function() {
            var loader = $('<div class="uvisLoading"><div class="spinner"></div></div>');
            loader.show();
            $("body").append(loader);
            uvisBackend.addUvis($scope.playlist_id, $scope.uvisTitle, function(data) {
                loader.remove();
                if (data.error !== undefined) {
                    console.warn('Creation of Visualization failed.');
                    $scope.errorMsgs = data.error;
                    $scope.errorCancel = true;
                    $scope.error = uvisurlbase + 'modules/visualizer/templates/error.html';
                } else if (data.result) {
                    setup.debug && console.log('Created uvis_ID', data.result);
                    ngDialog.close();
                    $scope.openUvisPopup(data.result);
                    // FIXME:  cleanup and move certain functions from $scope to factory
                    $scope.refreshUvisList();
                } else {
                    console.error('No result.')
                }
            });
        };
    });

    var uvisDeletePopup = app.controller('uvisDeletePopup', function ($scope, gettext, $timeout, ngDialog, uvisBackend) {
        $scope.deleteUvis = function() {
            setup.debug && console.log('Delete and close Dialog', $scope.uvisList);
            uvisBackend.deleteUvis($scope.visualization_id, function(data) {
                setup.debug && console.log('Success', data);
                if (data.error) {
                    console.error('Deletion of Visualization failed.', $scope.visualization_id)
                    $scope.errorMsgs = data.error;
                    $scope.errorCancel = true;
                    $scope.error = uvisurlbase + 'modules/visualizer/templates/error.html';
                } else if (data.result) {
                    setup.debug && console.log('Deleted uvis_ID', $scope.visualization_id);
                    $scope.updateUvisList(data.result);
                    ngDialog.close();
                } else {
                    console.error('No result.')
                }
            });
        };
    });

    var uvisPopup = app.controller('uvisPopup', function ($scope, gettext, ngDialog, uvisBackend) {
        var uvisData = uvisBackend.uvisData();
        var PL = uvisBackend.playlist();
        $scope.tabs = uvisData.mainTabs;
        $scope.uvisurlbase = uvisurlbase;
        $scope.tabsSecondary = [];

        if (uvisData.config.uvis_module) {
            uvisBackend.setCurrentModule(uvisData.config.uvis_module);
            $scope.currentTab = setup.currentModule.url;
            $scope.currentTabId = setup.currentModule.id;
            $scope.tabsSecondary = setup.currentModule.secondaryTabs;
            if ($scope.tabsSecondary && $scope.tabsSecondary.length > 0) {
                $scope.currentTabSecondary = _.first($scope.tabsSecondary).url;
            }
        } else {
            $scope.currentTab = uvisurlbase + 'modules/visualizer/templates/generic.html';
            $scope.currentTabId = 'generic';
            $scope.tabsSecondary = undefined;
        }
        // Init some data
        if (uvisData.config.uvis_playlist_display === 1) {
            $scope.uvisData.config.uvis_playlist_display = true;
        }
        if (uvisData.config.uvis_attachment_autoplay === 1) {
            $scope.uvisData.config.uvis_attachment_autoplay = true;
        }

        $scope.onClickTab = function (tab) {
            if (tab !== undefined) {
                uvisBackend.setCurrentModule(tab.id);
                PL.filterTax = [];
                PL.makeItems();
                $scope.tabsSecondary = tab.secondaryTabs;
                $scope.currentTab = tab.url;
                $scope.currentTabId = tab.id;
                if ($scope.tabsSecondary && $scope.tabsSecondary.length > 0) {
                    $scope.currentTabSecondary = _.first($scope.tabsSecondary).url;
                }
            } else {
                $scope.currentTab = uvisurlbase + 'modules/visualizer/templates/generic.html';
                $scope.currentTabId = 'generic';
                $scope.tabsSecondary = undefined;
            }
        };
        $scope.onClickModule = function(id) {
            var tab = _.findWhere(uvisData.mainTabs, { id: id });
            if (tab !== undefined) {
                $scope.onClickTab(tab);
            }
        };

        $scope.isActiveModule = function(tabid) {
            return tabid === uvisData.config.uvis_module;
        };

        $scope.isActiveTab = function(tabUrl) {
            return tabUrl === $scope.currentTab;
        };

        $scope.onClickTabSecondary = function (tab) {
            $scope.currentTabSecondary = tab.url;
        };

        $scope.isActiveTabSecondary = function(tabUrl) {
            $(".uvisTabFloating").css('height', 'auto'); // necessary for FF
            return tabUrl === $scope.currentTabSecondary;
        };


        $scope.mediatypes = [
            {
                id: "audio",
                title: gettext("Audio files")
            },
            {
                id: "video",
                title: gettext("Videos")
            },
            {
                id: "image",
                title: gettext("Images")
            },
            {
                id: "document",
                title: gettext("Documents")
            }
        ];
        $scope.toggleAttachmentDisplay = function() {
            PL.updateItemDisplay();
        };
        $scope.toggleMediatypeSelection = function(mtype) {
            var idx = uvisData.config.uvis_attachment_show_mediatypes.indexOf(mtype);
            if (idx > -1) {
                uvisData.config.uvis_attachment_show_mediatypes.splice(idx, 1);
            }
            else {
                uvisData.config.uvis_attachment_show_mediatypes.push(mtype);
            }
            PL.updateItemDisplay();
        };

        $scope.filters_show_fields = [
            {
                id: "post_title",
                title: gettext("Post Title")
            },
            {
                id: "post_date",
                title: gettext("Post Date")
            },
            {
                id: "post_content",
                title: gettext("Post Content")
            },
            {
                id: "post_permalink",
                title: gettext("Post Permalink")
            }
        ];
        $scope.toggleFieldSelection = function(field) {
            var idx = uvisData.config.uvis_filters_show_fields.indexOf(field);
            if (idx > -1) {
                uvisData.config.uvis_filters_show_fields.splice(idx, 1);
            }
            else {
                uvisData.config.uvis_filters_show_fields.push(field);
            }
            PL.updateItemDisplay();
        };

        $scope.save = function(close) {
            setup.debug && console.log('Save and close Dialog', uvisData);
            uvisBackend.saveUvis($scope.uvisData, function(data) {
                if (data.error) {
                    console.error('Save failed.')
                } else if (data.result) {
                    setup.debug && console.log('Visualization saved');
                    if (close) { ngDialog.close(); }
                    $scope.refreshUvisList();
                } else {
                    console.error('No result.')
                }
            });
        };
    });

    app.filter('filterterms', function() {
        return function(options, terms) {
            var out = [];
            var arr = _.clone(options);
            var sorted = arr.sort(function (a, b) {
                return a.name.toLowerCase().localeCompare(b.name.toLowerCase());
            });
            var sorted = arr;
            _.each(sorted, function(term){
                if (!_.contains(terms, term.id)) {
                    out.push(term);
                }
            });
            return out;
        };
    });

    app.filter('sortLocale', function() {
        return function(list, sortkey) {
            //FIXME check for sortkey, else just sort list as strings
            var arr = _.clone(list);
            var sorted = arr.sort(function (a, b) {
                return a[sortkey].toLowerCase().localeCompare(b[sortkey].toLowerCase());
            });
            return sorted;
        };
    });

    app.filter('sortLocaleValue', function() {
        return function(list, sortkey) {
            var arr = _.values(list);
            var sorted = arr.sort(function (a, b) {
                return a.toLowerCase().localeCompare(b.toLowerCase());
            });
            return sorted;
        };
    });


    // Controller for Settings
    app.controller('uvisPlaylistFiltersController', function ($scope, $sce, uvisBackend) {
        var uvisData = $scope.uvisData = uvisBackend.uvisData();
        var PL = uvisBackend.playlist();
        if (uvisData.taxonomies && uvisData.taxonomies.length < 1) {
            setup.debug && console.info("Loading Taxonomies");
            uvisBackend.getTaxonomies(uvisData.config.uvis_playlist);
        }
        $scope.loading = true;
        if (setup.debug) {
            window.uvisBackend = uvisBackend;
        }
        // FIXME: options defined globally and/or for custom fields?
        $scope.timerangeOptions = ["post_date","post_modified"],
        $scope.getTaxonomyTerms = uvisBackend.getTaxonomyTerms;
        $scope.playlistPositions = ['top', 'right', 'bottom', 'left', 'overlay'];
        $scope.currentTaxSlug = "";
        $scope.currentTerms = [];
        $scope.currentFilter = {
            uvis_filter_by_taxonomy: "",
            uvis_filter_by_taxonomy_term_ids: [],
            uvis_filter_by_timerange: undefined
        };
        $scope.myTaxonomyTerms = [];
        // Transform Datastructure (once again)
        var mapTerms = function(termsObj, reverse){
            var arr = [];
            _.each(termsObj, function(v,k){
                if (reverse) {
                    arr.push({ name:k, id:v });
                } else {
                    arr.push({ name:v, id:k });
                }
            });
            return arr;
        };
        var makeTerms = function() {
            if ($scope.myTaxonomy && $scope.myTaxonomy.terms) {
                $scope.myTaxonomyTerms = mapTerms($scope.myTaxonomy.terms, true);
            }
        };
        makeTerms();

        $scope.initFilter = function(){
            if (uvisData.taxonomies && uvisData.taxonomies.length > 0) {
                $scope.loading = false;
            }
            if (uvisData.config.uvis_filters && uvisData.config.uvis_filters.length > 0) {
                // TODO: Allow editing of multiple filters
                $scope.firstFilter = uvisData.config.uvis_filters[0];
                $scope.currentFilter.uvis_filter_by_taxonomy = $scope.firstFilter.uvis_filter_by_taxonomy;
                $scope.currentFilter.uvis_filter_by_taxonomy_term_ids = $scope.firstFilter.uvis_filter_by_taxonomy_term_ids;
                $scope.currentFilter.uvis_filter_by_timerange = $scope.firstFilter.uvis_filter_by_timerange;
                $scope.myTaxonomy = _.findWhere(uvisData.taxonomies, { slug:$scope.firstFilter.uvis_filter_by_taxonomy });
                makeTerms();
                $scope.currentTerms = mapTerms(uvisBackend.getTaxonomyTerms($scope.currentFilter.uvis_filter_by_taxonomy, $scope.firstFilter.uvis_filter_by_taxonomy_term_ids));
            }
        };
        $scope.changeTax = function(){
            $scope.currentFilter.uvis_filter_by_taxonomy = $scope.myTaxonomy.slug;
            makeTerms();
            $scope.myTaxonomyTerm = undefined;
        };
        $scope.addTerm = function(){
            if ($scope.myTaxonomyTerm === undefined) { return; }
            var terms = $scope.currentFilter.uvis_filter_by_taxonomy_term_ids;
            terms.push($scope.myTaxonomyTerm.id);
            $scope.currentFilter.uvis_filter_by_taxonomy_term_ids = _.uniq(terms);
            $scope.currentTerms = mapTerms(uvisBackend.getTaxonomyTerms($scope.currentFilter.uvis_filter_by_taxonomy, terms));
            $scope.myTaxonomyTerm = undefined;
        };
        $scope.removeTerm = function(termId){
            var terms = $scope.currentFilter.uvis_filter_by_taxonomy_term_ids;
            $scope.currentFilter.uvis_filter_by_taxonomy_term_ids = _.without(terms, parseInt(termId));
            $scope.currentTerms = mapTerms(uvisBackend.getTaxonomyTerms($scope.currentFilter.uvis_filter_by_taxonomy, $scope.currentFilter.uvis_filter_by_taxonomy_term_ids));
            if ($scope.currentFilter.uvis_filter_by_taxonomy_term_ids.length < 1) {
                // FIXME: resets all filters when last term was removed
                // (to allow multiple taxonomies, this must be handled otherwise)
                uvisData.config.uvis_filters = [];
            }
        };
        $scope.$watch('uvisData.taxonomies', function(newTax, oldTax){
            $scope.initFilter();
        });
        $scope.$watch('uvisData.config.uvis_filters_timerange_enable', function(oldVal, newVal){
            PL.makeItems();
            PL.updateFilterDisplay();
        });
        $scope.$watch('uvisData.config.uvis_filters_enable', function(oldVal, newVal){
            PL.filterClear();
            PL.makeItems();
            PL.updateFilterDisplay();
        });

        $scope.$watchCollection('currentFilter', function(newFilter, oldFilter){
            PL.makeItems();
            PL.updateFilterDisplay();
            if ($scope.currentFilter.uvis_filter_by_taxonomy_term_ids.length > 0) {
                uvisData.config.uvis_filters = [{
                    uvis_filter_by_taxonomy: $scope.currentFilter.uvis_filter_by_taxonomy,
                    uvis_filter_by_taxonomy_term_ids: $scope.currentFilter.uvis_filter_by_taxonomy_term_ids,
                    uvis_filter_by_timerange: $scope.currentFilter.uvis_filter_by_timerange
                }];
            }
        });
    });

    var transformLayout = function(uvisData){
        var data = uvisData;
        if (data === undefined) {
            return;
        }
        var $header = $(".uvisHeader");
        var $curruvis = undefined;
        if (data.config.uvis_module === "map") {
            $curruvis = $("#uvisMapWrap");
        } else if (data.config.uvis_module === "layers") {
            $curruvis = $("#uvisLayers");
        } else if (data.config.uvis_module === "timeline") {
            if ($('.uvisViewerInclude').length > 0) {
                var tb = $(".uvisTabBody");
                tb.height(tb.height()- $(".uvisWrap").offset().top);
            }
        } else if ($curruvis === undefined && data.config.uvis_module !== "timeline") {
            $curruvis = $(".uvisModule");
        }
        if ($curruvis === undefined || $curruvis.length < 1) {
            return;
        }

        var $pl = $(".uvisPlaylist");
        var $pi = $(".uvisPlaylistItems");
        var wh = $(".uvisTabBody").height();
        var ww = $(".uvisTabBody").width();
        var ph = wh - $(".uvisPlaylistHeader").height(); // Reduces height of scrollable playlist items by playlist header

        var plpos = uvisData.config.uvis_playlist_position;
        if (plpos === "bottom") {
            $pl.removeAttr("style");
            $curruvis.height(wh - $pl.height());
            $curruvis.width(ww);
            $curruvis.css({"top": 0});
            $curruvis.css({"left": 0});
            $pl.draggable('disable');
        }
        else if (plpos === "top") {
            $pl.removeAttr("style");
            $curruvis.height(wh - $pl.height());
            $curruvis.width(ww);
            $curruvis.css({"top": $pl.height()});
            $curruvis.css({"left": 0});
            $pl.draggable('disable');
        }
        else if (plpos === "left") {
            $pl.removeAttr("style");
            $curruvis.height(wh);
            $curruvis.width(ww - $pl.width());
            $curruvis.css({"top": 0});
            $curruvis.css({"left": $pl.width()});
            $pl.draggable('disable');
            $pi.height(ph);
        }
        else if (plpos === "right") {
            $pl.removeAttr("style");
            $curruvis.height(wh);
            $curruvis.width(ww - $pl.width());
            $curruvis.css({"top": 0});
            $curruvis.css({"left": 0});
            $pl.draggable('disable');
            $pi.height(ph);
        }

        if (plpos === "overlay" || uvisData.config.uvis_playlist_display === false) {
            $pl.removeAttr("style");
            $curruvis.height(wh);
            $curruvis.width(ww);
            $curruvis.css({"top": 0});
            $curruvis.css({"left": 0});
            $pl.draggable('enable');
        }
    };

    if (setup.debug) {
        window.uvisTransformLayout = function() { transformLayout(window.uvisData); };
    }

    app.directive('uvisPlaylist', function() {
        return {
            restrict: 'A',
            link: function(scope, elm, attrs) {
                var uvisData = scope.uvisData;
                scope.$watchCollection("[uvisData.config.uvis_playlist_background_color, uvisData.config.uvis_playlist_text_color]", function(){
                    var elems = elm.find('.uvisPlaylistHeader, .uvisPlaylistHeader h5, .uvisPlaylistItems');
                    elems.css({
                        "background-color": uvisData.config.uvis_playlist_background_color,
                        "border-color": uvisData.config.uvis_playlist_text_color,
                        "color": uvisData.config.uvis_playlist_text_color
                    });
                });
                scope.$watch("uvisData.currentItem", function(){
                    if (uvisData.currentItem && uvisData.currentItem.ID) {
                        var currelem = elm.find(sprintf(".uvisPlaylistItem[data-id='%s']", uvisData.currentItem.ID));
                        if (currelem.length > 0) {
                            elm.find(".uvisPlaylistItem").removeClass("current");
                            currelem.addClass("current");
                        }
                    }
                });
            }
        };
    });

    app.directive('uvisPlaylistItem', function() {
        return {
            restrict: 'A',
            link: function(scope, elm, attrs) {
                var uvisData = scope.uvisData;
                if (uvisData.currentItem && uvisData.currentItem.ID === scope.item.ID) {
                    elm.addClass("current");
                }
                scope.$watchCollection("[uvisData.config.uvis_playlist_background_color, uvisData.config.uvis_playlist_text_color]", function(){
                    elm.css({
                        "background-color": uvisData.config.uvis_playlist_background_color,
                        "border-color": uvisData.config.uvis_playlist_text_color,
                        "color": uvisData.config.uvis_playlist_text_color
                    });
                });
            }
        };
    });

    app.directive('uvisPlayer', ['$compile', '$timeout', 'uvisBackend', 'uvisPlayer', function($compile, $timeout, uvisBackend, uvisPlayer) {
        return {
            scope: {
                item: "=uvisItem"
            },
            restrict: 'A',
            link: function(scope, elm, attrs) {
                scope.uvisData = uvisBackend.uvisData();
                var makePlayer = function() {
                    elm.empty();
                    elm.html(uvisPlayer.makePlayerContainers(scope.item));
                    uvisPlayer.convertPlayerContainers();
                    $timeout(function(){
                        uvisPlayer.autoPlay();
                    }, 500);
                };
                scope.$watch('item', function(){
                    makePlayer();
                });
                scope.$watch('uvisData.config.uvis_attachment_display', function(){
                    makePlayer();
                });
                scope.$watchCollection('uvisData.config.uvis_attachment_show_mediatypes', function(){
                    makePlayer();
                });
            }
        };
    }]);

    app.controller('uvisPlaylistController', function ($scope, $sce, uvisBackend, $timeout) {
        var PL = uvisBackend.playlist();
        var uvisData = $scope.uvisData = uvisBackend.uvisData();

        //bind scope to the playlist for syncing external api
        PL.$scope = $scope;

        $scope.$watchCollection('[uvisData.config.uvis_playlist_position, uvisData.config.uvis_playlist_display]', function(newConf, oldConf){
            $timeout(function(){
                transformLayout(uvisData);
            });
        });
        $scope.$watch('uvisData.config.uvis_module', function(newModule, oldModule){
            if (newModule !== oldModule) {
                $scope.pause();
                $scope.uvisPlay = false;
            }
        });
        $scope.uvisPlay = false;
        $scope.play = function(){
            PL.animationDelay = uvisData.config.uvis_animation_delay;
            PL.play($scope);
        };
        $scope.pause = function(){
            $scope.uvisPlay = false;
            PL.pause();
        };
        $scope.select = function(item){
            PL.select(item);
        };
        $scope.next = function(){
            PL.next();
        };
        $scope.previous = function(){
            PL.previous();
        };
        $scope.isCurrentPLItem= function(item){
            if ($scope.uvisData.currentItem === item ) {
                return true;
            }
        };

        if (uvisData.config.uvis_animation_autoplay) {
            $timeout(function() {
                $scope.uvisPlay = true;
                PL.play($scope);
            }, 1000);
        } else {
            $scope.uvisPlay = false;
            PL.pause();
        }
    });

    app.controller('uvisFiltersController', function ($scope, $sce, uvisBackend) {
        var uvisData = $scope.uvisData = uvisBackend.uvisData();
        var PL = uvisBackend.playlist();

        if (uvisData.config.uvis_filters_enable && uvisData.taxonomies && uvisData.taxonomies.length < 1) {
            uvisBackend.getTaxonomies(uvisData.config.uvis_playlist);
        }
        $scope.loading = true;
        $scope.getTaxonomyTerms = uvisBackend.getTaxonomyTerms;
        $scope.getTaxonomyTermsByIds = uvisBackend.getTaxonomyTermsByIds;
        $scope.$watch('uvisData.taxonomies', function(newTax, oldTax){
            if (uvisData.taxonomies && uvisData.taxonomies.length > 0) {
                $scope.loading = false;
            }
        });
        // TODO: animation queue
        $scope.filterMod = function(taxid){
            PL.filterMod(taxid);
        };
        $scope.filterClear = function(){
            PL.filterClear();
        };
        $scope.filterIsOn = function(taxid){
            return _.contains(PL.filterTax, taxid);
        };
        $scope.filterDateRange = function() {
            return uvisData.config.uvis_filters_timerange_enable;
        };
    });

    app.directive('uvisLayout', function() {
        return {
            restrict: 'A',
            link: function(scope, elm, attrs) {
                window.setTimeout(function(){
                    transformLayout(scope.uvisData);
                },1000);
            }
        };
    });

    app.directive('uvisTooltip', function() {
        return {
            restrict: 'A',
            link: function(scope, elm, attrs) {
                elm.attr("title", attrs.uvisTooltip);
                elm.tooltip({
                    delay: 800
                });
            }
        };
    });

    app.directive('uvisEnter', function() {
        return function(scope, element, attrs) {
            element.bind("keydown keypress", function(event) {
                if(event.which === 13) {
                    scope.$apply(function(){
                        scope.$eval(attrs.uvisEnter, {'event': event});
                    });

                    event.preventDefault();
                }
            });
        };
    });

    app.directive('uvisColorpicker', function() {
        return {
            require: "ngModel",
            restrict: 'A',
            scope: {
                ngModel: "="
            },
            link: function(scope, elm, attrs, ctrl) {
                var cpcolor = scope.ngModel;
                elm.colorpicker({
                    color: cpcolor,
                    history: false,
                    displayIndicator: false,
                    strings: "Select a color,Basic colors,More colors,Less colors,Palette,History,No history."
                });
                elm.on("change.color", function(event, color) {
                    ctrl.$setViewValue(color);
                    ctrl.$render();
                    scope.$apply();
                });
            }
        };
    });

    app.directive('uvisDraggable', function() {
        return {
            restrict: 'A',
            link: function(scope, elm, attrs) {
                var attrsOwn = attrs.uvisDraggable || {};
                var override = scope.$eval(attrsOwn);
                var options = {
                    snap: '.modal-body, .uvisPlaylist, .uvisPlaylistFilter', snapMode: 'both', scroll: false, containment: '.uvisTabBody', handle: '.uvisDraggableHandle'
                };
                //FIXME: assign doesn't work in older _ versions (conflict)
                //options = _.assign(options,override);
                options = _.extend(options,override);
                if (options.handle === ".uvisDraggableHandle") {
                    if (elm.find(".uvisDraggableHandle").length < 1) {
                        elm.addClass("uvisDraggableHandle");
                    }
                };
                elm.draggable(options).css('height', 'auto');
            }
        };
    });

    app.directive('uvisRefreshDaterange', ['$compile', 'uvisBackend', function($compile, uvisBackend) {
        return {
            restrict: 'A',
            link: function(scope, elm, attrs, ctrl) {
                var PL = uvisBackend.playlist();
                elm.on( "dragstart", function(e) {
                    var drs = elm.find(".ui-rangeSlider");
                    drs.animate({"opacity":0.7}, 200, function() {
                    });
                });
                elm.on( "dragstop", function(e) {
                    var drs = elm.find(".ui-rangeSlider");
                    drs.dateRangeSlider('resize');
                    drs.hide();
                    var bounds = drs.dateRangeSlider("bounds");
                    var min = drs.dateRangeSlider("min");
                    var max = drs.dateRangeSlider("max");
                    drs.dateRangeSlider("destroy");
                    drs.dateRangeSlider(makeDateRangeOptions(scope, PL));
                    drs.addClass("uvisNoupdate");
                    //drs.dateRangeSlider("bounds", bounds.min, bounds.max);
                    drs.dateRangeSlider("min", min);
                    drs.dateRangeSlider("max", max);
                    drs.dateRangeSlider('resize');
                    drs.css("opacity", 0);
                    drs.show();
                    drs.dateRangeSlider('resize');
                    drs.animate({"opacity":1}, 200, function() {
                        drs.dateRangeSlider('resize');
                        drs.removeClass("uvisNoupdate");
                    });
                });
                scope.$watch(attrs.ngShow, function(shown) {
                    if (shown) {
                        var drs = elm.find(".ui-rangeSlider");
                        drs.hide();
                        drs.dateRangeSlider('resize');
                        window.setTimeout(function(){
                            drs.show();
                            drs.dateRangeSlider('resize');
                        }, 200);
                    }
                });
            }
        };
    }]);

    var makeDateRangeOptions = function(scope, PL){
        var orderby = scope.uvisData.config.uvis_filter_by_timerange || "post_date";
        var sorted = PL.rangeSort(orderby);
        var dates = [];
        dates = _.pluck(sorted, orderby)
        dates = _.map(dates, function(d){ return new Date(d.replace(/-/g, '/')) });
        var dfirst = _.first(dates);
        var dlast = _.last(dates);
        PL.rangeMin = dfirst;
        PL.rangeMax = dlast;
        var dcurr = dfirst;
        return {
            bounds: {min: dfirst, max: dlast},
            defaultValues: {min: dfirst, max: dlast},
            formatter:function(val){
                return formatDate(val);
            },
            scales: [{
              first: function(value){
                  return value;
              },
              end: function(value) {
                  return value;
              },
              next: function(value){
                var nextk = dates.indexOf(value) + 1;
                if (nextk >= dates.length) {
                    return dlast;
                } else {
                    return dates[nextk];
                }
              },
              label: function(value){
                return ""
                //return dates[value];
              },
              format: function(tickContainer, tickStart, tickEnd){
                //tickContainer.addClass("myCustomClass");
              }
            }]
        }
    };


    app.directive('uvisDaterange', ['$compile', 'uvisBackend', function($compile, uvisBackend) {
        return {
            restrict: 'A',
            link: function(scope, elm, attrs, ctrl) {
                var PL = uvisBackend.playlist();

                scope.$watch("uvisData.config.uvis_filter_by_timerange",function(newSort,oldSort) {
                    if (elm.hasClass("ui-rangeSlider")) {
                        elm.dateRangeSlider('disable');
                        elm.dateRangeSlider('destroy');
                        elm.dateRangeSlider(makeDateRangeOptions(scope, PL));
                        elm.dateRangeSlider('resize');
                        PL.makeItems();
                        PL.updateFilterDisplay();
                    } else {
                        elm.dateRangeSlider(makeDateRangeOptions(scope, PL));
                        elm.dateRangeSlider('resize');
                    }
                });
                elm.on("valuesChanged", function(e, data){
                    if (!elm.hasClass("uvisNoupdate")) {
                        PL.setDateRange(data.values.min, data.values.max);
                    }
                });
            }
        };
    }]);

    app.controller('uvisStoryEditorController', function ($scope, $sce, uvisBackend) {
        var uvisData = uvisBackend.uvisData();
        var PL = uvisBackend.playlist();
        //$scope.PL = PL;
        var itemconf = uvisData.config.uvis_item_config;
        $scope.PLItems = PL.sorted;
        $scope.$watch('PLItems', function(newPLItems, oldPLItems){
            uvisBackend.updateUvisConfig();
        }, true);
        // Expands post content when clicking on comment inputs
        $(".uvisStoryboardItemConf").live("click", function() {
          var previd = "#" + $(this).prev(".uvisStoryItemProps").attr("id");
          $(".uvisStoryItemContent").css("overflow-y", "hidden"); // Collapse all post contents
          $(previd).addClass("active"); // Highlight
          $(previd).find(".uvisStoryItemContent").css("overflow-y", "visible").css("height", "auto"); // Expand contents
        });

        $(".uvisStoryboardItemConf").live("blur", function() {
          var previd = "#" + $(this).prev(".uvisStoryItemProps").attr("id");
          $(".uvisStoryItemContent").css("overflow-y", "hidden").css("height", "5em");
          $(previd).removeClass("active");
        });
        $scope.uvisPoststatus = ['draft', 'private', 'publish'];
    });

});