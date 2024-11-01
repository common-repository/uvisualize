define([
    'require',
    'angularAMD',
    'angular-gettext',
    'ngDialog',
    'uvisSetup',
    'underscore',
    'jquery'
    ],
    function(require) {

        var angularAMD = require('angularAMD');
        var setup = require('uvisSetup');
        var $ = require('jquery');
        var _ = require('underscore');

        setup.debug && console.info("Init uvisApp");

        // Define app
        var app = angular.module('ngAppUvis', ['gettext','ngDialog']);

        app.run(function (gettextCatalog) {
            if (setup.debug) {
                window.uvisGettextCatalog = gettextCatalog;
            }
            if (setup.debug_i18n) {
                gettextCatalog.debug = true;
            }
            //set language according to html-lang
            var lang = $("html").attr("lang");
            if (lang) {
                lang = lang.substr(0,2);
                gettextCatalog.setCurrentLanguage(lang);
            }
        });

        // Bootstrap app
        angular.element(document).ready(function() {
            var loadmodules = ["uvisVisualizer"].concat(_.pluck(setup.activeModules, "require"));
            require(loadmodules, function(){
                angularAMD.bootstrap(app);
            });
        });

        return app;
});
