// FIXME: TimelineJS Hack to support custom uvis media elements
// Sorry, this essentially breaks TimelineJS. 
// As of May 2015 TimlineJS3 wasn't production ready, but it looks promising to support custom media.
// See https://github.com/NUKnightLab/TimelineJS3


define(["require", "timelinejs"], function(require){
    if (VMM && VMM.MediaType !== undefined) {

        // Keep HTML Quotes
        VMM.Util.properQuotes = function(str) { return str; }

        VMM.bindEvent = function (e, t, n, r) {
            var i, s = "click", o = {};
            n != null && n != "" && (s = n);
            o != null && o != "" && (o = r);
            jQuery(e).unbind(s, o, t);
            typeof jQuery != "undefined" && jQuery(e).bind(s, o, t)
        };

        // Always return unkown media type
        VMM.MediaType = function(_d) {
            var d   = _d.replace(/^\s\s*/, '').replace(/\s\s*$/, ''),
                success = false,
                media = {
                    type:   "unknown",
                    id:     "",
                    start:    0,
                    hd:     false,
                    link:   "",
                    lang:   VMM.Language.lang,
                    uniqueid: VMM.Util.unique_ID(6)
                };
            trace("unknown media");  
            media.type = "unknown";
            media.id = d;
            success = true;
            if (success) { 
                return media;
            } else {
                trace("No valid media id detected");
                trace(d);
            }
            return false;
        }
    } else {
        console.error("VMM Patch failed (TimelineJS)");
    }
});
