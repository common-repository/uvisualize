define(['require','underscore'], function(require) {

    var $ = require("jquery");
    var _ = require('underscore');

    //////////////////////////////////////////
    // The layers singleton, a render API like mapjs, timelinejs etc...
    //////////////////////////////////////////

    var uvisLayers = function(elemid, PL, player, options){
        var ls = this;
        this.layers = [];
        this.player = player;
        this.PL = PL;
        this.axis = "z";
        this.scale = 1;
        this.x = 0;
        this.y = 0;
        this.z = 0;
        this.camZ = 0;
        this.rotate = 0;
        this.jq = $("#" + elemid);
        $(window).on("resize", function(e){
            ls.updateGeometry();
        });
        this.jq.on("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd", function(e){
            e.stopPropagation();
            //ls.updateLayers();
            ls.jq.width(ls.jq.width());
            ls.jq.height(ls.jq.height());
            ls.jq.width("100%");
            ls.jq.height("100%");

        });
        this.setMode(options);
        return this;
    };

    uvisLayers.prototype.makeLayers = function() {
        var ls = this;
        var PL = this.PL;
        this.width = this.jq.width();
        this.height = this.jq.height();
        this.jq.empty();
        this.layers = [];
        var inc = this.getIncrement();
        ls.jq.css({opacity:0});

        // Add a first layer as a welcome screen for the visualization
        /*
        if (uvisData.config.uvis_post_content && uvisData.config.uvis_post_content.length > 0) {
          var item = {'post_title': uvisData.config.uvis_post_title, 'post_content': uvisData.config.uvis_post_content};
          var l = ls.makeLayer(item);
          l[ls.axis] = 1;
          ls.jq.append(l.jq);
        }*/

        // make from sorted
        _.each(PL.sorted, function(item, i){
            var l = ls.makeLayer(item);
            l[ls.axis] = i * inc;
            //l.update();
            ls.jq.append(l.jq);
        });
        this.player.convertPlayerContainers();
        window.setTimeout(function(){
            ls.updateLayers();
            ls.jq.css({opacity:1});
            ls.updateArrows();
        },1000);
        ls.currentLayer = _.first(ls.layers);
        return ls;
    };

    uvisLayers.prototype.checkArrowDisplay = function() {
        var ls = this;
        if (ls.layers.length < 1) {
            ls.$next.hide();
            ls.$prev.hide();
            return;
        }
        if (ls.currentLayer && ls.currentLayer.ID === _.first(ls.layers).ID) {
            ls.$next.show();
            ls.$prev.hide();
        } else if (ls.currentLayer && ls.currentLayer.ID === _.last(ls.layers).ID) {
            ls.$next.hide();
            ls.$prev.show();
        } else if (ls.layers.length > 0) {
            ls.$next.show();
            ls.$prev.show();
        }
    };

    uvisLayers.prototype.addArrows = function(src) {
        var ls = this;
        ls.$next = $("<img class='uvisLayersArrowNext' />").attr('src', src);
        ls.$prev = $("<img class='uvisLayersArrowPrev' />").attr('src', src);
        ls.$prev.hide();
        ls.jq.append(ls.$next);
        ls.jq.append(ls.$prev);
        ls.$next.on("click", function(e){
            ls.jump(1);
        });
        ls.$prev.on("click", function(e){
            ls.jump(-1);
        });
        ls.updateArrows();
    };

    uvisLayers.prototype.jump = function(steps) {
        var ls = this;
        var PL = this.PL;
        if (ls.currentLayer !== undefined) {
            var next = _.indexOf(ls.layers, ls.currentLayer) + steps;
            var nextLayer = ls.layers[next];
            if (nextLayer && nextLayer.ID !== undefined) {
                PL.selectApply(nextLayer.item)
                ls.checkArrowDisplay();
            }
        }
    };

    uvisLayers.prototype.setArrowsImage = function(src) {
        var ls = this;
        if (ls.$next === undefined) { return; }
        ls.$next.attr("src", src);
        ls.$prev.attr("src", src);
    };

    uvisLayers.prototype.updateArrows = function() {
        var ls = this;
        var crimp = function(val, min, max) {
            min = (min === undefined) ? 5 : min;
            max = (max === undefined) ? 95 : max;
            return Math.min(Math.max(val, min), max)
        };
        var quantize = function(val){
            var q = val;
            if (val > 25) {
                q = Math.round(val / 50) * 50;
            } else {
                q = Math.floor(val / 50) * 50;
            }
            return q;
        };
        if (ls.$next === undefined) { return; }
        var nexttop = oy = Math.min(Math.max(parseInt(ls.originY), 0), 100);
        var prevtop = 100 - nexttop;
        nexttop = quantize(nexttop);
        prevtop = 100 - nexttop;
        var nextleft = ox = Math.min(Math.max(ls.originX, 0), 100);
        nextleft = quantize(nextleft);
        var prevleft = 100 - nextleft;
        if (ls.axis === "x") {
            nexttop = 50;
            prevtop = 50;
            nextleft = 100;
            prevleft = 0;

        } else if (ls.axis === "y") {
            nexttop = 100;
            prevtop = 0;
            nextleft = 50;
            prevleft = 50;
        } else {
            if (nextleft === prevleft && nexttop === prevtop) {
                ox = ox - 50;
                oy = oy - 50;
                if (Math.abs(ox) >= Math.abs(oy)) {
                    // horizontal spread is larger
                    nexttop = 50;
                    prevtop = 50;
                    nextleft = (ox < 0) ? 0 : 100;
                    prevleft = (ox < 0) ? 100 : 0;
                } else {
                    // vertical spread is larger
                    nextleft = 50;
                    prevleft = 50;
                    nexttop = (oy < 0) ? 0 : 100;
                    prevtop = (oy < 0) ? 100 : 0;
                }
            }
        }
        var nexth = ls.$next.height() | 0;
        var prevh = ls.$prev.height() | 0;
        var nextw = ls.$next.width() | 0;
        var prevw = ls.$prev.width() | 0;
        var theta = Math.atan2(nexttop - 50, nextleft - 50);
        ls.$next.css("transform", "rotate(" + theta + "rad)");
        ls.$prev.css("transform", "rotate(" + (theta + Math.PI) + "rad)");
        ls.$next.css("top", "calc("+ crimp(nexttop) +"% - "+ nexth/2 +"px)");
        ls.$prev.css("top", "calc("+ crimp(prevtop) +"% - "+ prevh/2 +"px)");
        ls.$next.css("left", "calc("+ crimp(nextleft) +"% - "+ nextw/2 +"px)");
        ls.$prev.css("left", "calc("+ crimp(prevleft) +"% - "+ prevw/2 +"px)");
    };

    uvisLayers.prototype.resetLayerAxis = function(){
        var ls = this;
        var inc = this.getIncrement();
        _.each(this.layers, function(l, i){
            l.z = 0;
            l.x = 0;
            l.y = 0;
            l[ls.axis] = i * inc;
            ls.jq.append(l.jq);
        });
    };

    uvisLayers.prototype.getLayers = function() {
        var ls = this;
        var PL = this.PL;
        var filtered = [];
        _.each(this.layers, function(l, i){
            if (_.findWhere(PL.getItems(), {ID: l.ID}) !== undefined) {
                filtered.push(l);
            }
        });
        return filtered;
    };

    uvisLayers.prototype.updateLayers = function() {
        var ls = this;
        var PL = this.PL;
        this.width = this.jq.width();
        this.height = this.jq.height();
        // items have been filtered?
        _.each(this.layers, function(l, i){
            l.hide();
            l.jq.hide();
        });
        var inc = this.getIncrement();
        _.each(this.getLayers(), function(l, i){
            l[ls.axis] = i * inc;
            l.jq.show();
            l.show();
            l.update();
        });
    };

    uvisLayers.prototype.updateGeometry = function() {
        var ls = this;
        var PL = this.PL;
        this.width = this.jq.width();
        this.height = this.jq.height();
        var inc = this.getIncrement();
        var l = this.getLayer();
        var off = l[ls.axis];
        _.each(this.getLayers(), function(ll){
           ll[ls.axis] = ll[ls.axis] - off;
           ll.update();
        });
        ls.updateArrows();
    };

    uvisLayers.prototype.updateColors = function(colors) {
        var ls = this;
        ls.jq.css({ "background-color": colors.backgroundColor });
        _.each(this.layers, function(l, i){
            l.jq.css({
                "background-color": colors.popupBackgroundColor,
                "color": colors.popupFontColor,
                "font-family": colors.popupFont,
                "border-color": colors.popupBorderColor
            });
        });
    };

    uvisLayers.prototype.getIncrement = function() {
        if (this.axis === "z") {
            return -80;
            //return - 1000 / this.PL.getItems().length;
        } else if (this.axis === "x") {
            return this.jq.width();;
        } else if (this.axis === "y") {
            return this.jq.height();;
        }

    };

    uvisLayers.prototype.getLayer = function(plid) {
        if (plid === undefined) {
            if (this.currentLayer !== undefined) {
                return this.currentLayer;
            } else {
                return this.layers[0];
            }
        } else {
            var layer = _.findWhere(this.layers, { ID: plid });
            if (layer !== undefined) {
                return layer;
            } else {
                return this.layers[0];
            }
        }
    };

    uvisLayers.prototype.hideAllLayers = function() {
        var ls = this;
        _.each(this.layers, function(l){
            l[ls.axis] = -1000;
            l.transform();
        });
    };

    uvisLayers.prototype.showLayer = function(plid) {
        // FIXME: fired twice, why? maybe PL callback n visualzer.js?
        var ls = this;
        var l = this.getLayer(plid);
        ls.currentLayer = l;
        l.jq.css({"visibility": "visible"});
        var off = l[ls.axis];
        _.each(this.getLayers(), function(ll){
           ll[ls.axis] = ll[ls.axis] - off;
           ll.transform();
        });
        ls.checkArrowDisplay();
    };

    uvisLayers.prototype.makeLayer = function(item){
        var l = new uvisLayer(item, this);
        this.layers.push(l);
        return l;
    };

    uvisLayers.prototype.setMode = function(options) {
        var ls = this;
        if (options === undefined) {
            options = {};
            options.axis = this.axis;
            options.originX = this.originX;
            options.originY = this.originY;
        }
        if (options.axis !== this.axis) {
            this.axis = options.axis || "z";
            this.resetLayerAxis();
        }
        this.axis = options.axis || "z";
        this.originX = options.originX;
        this.originY = options.originY;
        ls.jq.css({
            "-webkit-transform-style": "preserve-3d",
            "-moz-transform-style": "preserve-3d",
            "-o-transform-style": "preserve-3d",
            "transform-style": "preserve-3d",

            "-webkit-backface-visibility": "hidden",
            "-moz-backface-visibility": "visible",
            "-o-backface-visibility": "hidden",
            "backface-visibility": "visible",

            "-webkit-perspective-origin": this.originX + "% " + this.originY + "%",
            "-moz-perspective-origin": this.originX + "% " + this.originY + "%",
            "-o-perspective-origin": this.originX + "% " + this.originY + "%",
            "perspective-origin": this.originX + "% " + this.originY + "%",
            "-webkit-transition": "-webkit-perspective-origin .2s"
        });
        // FIXME: force rerendering in FIREFOX due to bug in perspective-origin change
        ls.jq.width(ls.jq.width());
        ls.jq.height(ls.jq.height());
        ls.jq.width("100%");
        ls.jq.height("100%");
        this.updateLayers();
        ls.updateArrows();
    };


    ///////////
    // uvisLayer
    ///////////

    uvisLayer = function(item, maker){
        var l = this;
        this.ID = item.ID;
        this.maker = maker;
        this.index = maker.layers.length;
        this.item = item;
        this.scale = 1;
        this.x = 0;
        this.y = 0;
        this.z = 0;
        if (maker.axis === "z") {
        } else if (maker.axis === "x") {
        } else if (maker.axis === "y") {
        }
        this[maker.axis] = -1000;
        this.rotate = 0;
        this.opacity = 1;
        var PL = maker.PL;
        this.jq = $('<div class="uvisLayersPopup"></div>');
        this.jqShield = $('<div class="uvisLayersPopupShield"></div>');
        this.jq.append(this.jqShield);
        this.jq.attr('data-id', item.ID);
        var permalink = (PL.getPermalink(item) != "" && PL.getPermalink(item) !== undefined) ? '<div class="uvisLayersPermalink">' + PL.getPermalink(item) + '</div>' : '';
        this.jq.append($('<h4 class="uvisLayersTitle"></h4>').text(PL.getTitle(item)).append(permalink));
        var content = (PL.getDate(item) !== undefined) ? '<span class="uvisLayersDate">' + PL.getDate(item) + '</span>' + ( PL.getContent(item) || '' ) : ( PL.getContent(item) || '' );
        this.jq.append($('<div class="uvisLayersComment"></div>').html(content));
        this.jq.append($(maker.player.makePlayerContainers(item)));
        this.jq.on("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd", function(e){
            e.stopPropagation();
            l.onTransitionEnd();
        });
        this.update();
        this.jqShield.on("click", function(e){
            l.jqShield.hide();
            PL.selectApply(l.item)
        });
        this.jqShield.hover(function(e){
            l.jq.addClass("hover");
        }, function(e){
            l.jq.removeClass("hover");
        });


        //this.transform();
        return this;
    };

    uvisLayer.prototype.update = function() {
        // FIXME: can't get calculated width of layer
        this.left = (this.maker.jq.width() - this.jq.width()) / 2;
        this.top = (this.maker.jq.height() - this.jq.height()) / 2;
        //this.left = (this.maker.jq.width() - 640) / 2;
        //this.top = (this.maker.jq.height() - 400) / 2;
        this.transform();
    };

    uvisLayer.prototype.onTransitionEnd = function() {
        if (this.z > 1) {
            // hide when out of sight
            //this.jq.css({height:0});
            this.hide();
            //this.jqShield.hide();
        }
    };

    uvisLayer.prototype.hide = function() {
        this.hidden = true;
        this.jq.css({"visibility": "hidden"});
    };

    uvisLayer.prototype.show = function() {
        this.hidden = false;
        this.jq.css({"visibility": "visible"});
    };

    uvisLayer.prototype.transform = function() {
        var x = this.x;
        var y = this.y;
        var z = this.z; // 0 to -1000
        //this.opacity = 1 - Math.abs(z / 1000); // 0 to 1
        //this.opacity = this.opacity * 0.5;
        this.opacity = (1 - Math.abs(z / 1000)) * 0.7; // 0 to 1
        if (z > 1) {
            this.opacity = 0;
        } else if (z >= 0) {
            this.jqShield.hide();
            this.opacity = 1;
            this.show();
        } else {
            this.jqShield.show();
            this.show();
        }
        this.jq.css({
            "-webkit-transform-style": "preserve-3d",
            "-webkit-backface-visibility": "hidden",
            "-moz-transform-style": "preserve-3d", // Massive FF Performance Boost on Scrolling ;)
            "-webkit-transform": "rotateZ("+this.rotate+"deg) scale3d("+this.scale+","+this.scale+",1) translate3d("+ x +"px,"+ y +"px,"+ z +"px)",
            "-moz-transform": "rotateZ("+this.rotate+"deg) scale3d("+this.scale+","+this.scale+",1) translate3d("+ x +"px,"+ y +"px,"+ z +"px)",
            "transform": "rotateZ("+this.rotate+"deg) scale3d("+this.scale+","+this.scale+",1) translate3d("+ x +"px,"+ y +"px,"+ z +"px)",
            "-webkit-transition": "-webkit-transform 0.5s, opacity 0.5s, -webkit-filter 0.5s",
            "-moz-transition": "-moz-transform 0.5s, opacity 0.5s",
            "transition": "transform 0.5s, opacity 0.5s",
            "opacity": this.opacity,
            //"-webkit-filter": "contrast("+ (this.opacity * 100) +"%) brightness("+ (100 + ((1 - this.opacity) * 100)) +"%)",
            "z-index": parseInt(1000 + z),
            "top": this.top,
            "left": this.left
        });
    };

    // return the api
    return {
        uvisLayer: uvisLayer,
        uvisLayers: uvisLayers
    }
});