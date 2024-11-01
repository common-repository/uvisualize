var uvisurlbase = uvis_url + '/';

if (typeof jQuery === 'function') {
    define('jquery', function () { return jQuery; });
}

//FIXME: newer underscore breaks featured image selection in wpress admin, so revert to existing
if (typeof _ === 'function') {
    define('underscore', function () { return _; });
} else {
    define('underscore', ['require'], function (require) {
        var _ = require('../vendor/underscore/underscore-min');
        return _;
    });
}

// Loading requirements and modules
require.config({
    baseUrl: uvis_url,
    paths: {
        // Vendor
        // FIXME: we should use this version of underscore
        //'underscore': 'vendor/underscore/underscore-min',
        'sprintf': 'vendor/sprintf/sprintf.min',
        'angular': 'vendor/angular/angular.min',
        'angular-gettext': 'vendor/angular-gettext/angular-gettext.min',
        'ngDialog': 'vendor/ngDialog/js/ngDialog.min',
        'angularAMD': 'vendor/angularAMD/angularAMD.min',
        'jqrangeslider': 'vendor/jqrangeslider/jQDateRangeSlider-withRuler-min',
        // UVis Core
        'uvisSetup': 'js/uvisSetup',
        'uvisApp': 'js/app',
        'uvisTranslations': 'i18n/translations',
        'uvisVisualizer': 'modules/visualizer/js/visualizer'
    },
    shim: {
        'angularAMD': ['angular'],
        'ngDialog': {
            deps: ['angular'],
            exports: 'ngDialog'
        },
        'angular-gettext': {
            deps: ['angular'],
            exports: 'gettext'
        },
        'underscore': {
            exports: '_'
        },
        'uvisTranslations': {
            deps: ['angular', 'uvisApp']
        }
    },
    // start the app
    deps: ['jquery', 'underscore', 'uvisApp', 'uvisTranslations']
});