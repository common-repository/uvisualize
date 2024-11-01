define(function(require){
    var _ = require('underscore');
    var settings = {
        debug: false,
        debug_i18n: false,
        locale_default: 'en',
        mainTabs: [],
        providedModules: [],
        urlbase: uvisurlbase,
        activeModules: [],
        getModule: function(module_id) {
            return _.findWhere(settings.providedModules, { id: module_id });
        },
        extendModule: function(data) {
            if (!data.hasOwnProperty('id')) {
                console.error("uvisSetup: Error module id missing");
            }
            var mod = settings.getModule(data.id);
            // No Module, so register
            if (mod === undefined) {
                settings.providedModules.push(data);
            } else {
            // else extend the module
                _.extend(mod, data);
            }
        }
    };
    if (uvis_active_modules) {
        _.each(uvis_active_modules, function(module){
            var name = "uvis" + module.id.charAt(0).toUpperCase() + module.id.slice(1);
            module.name = name;
            module.require = "modules/" + module.id + "/js/" + module.id;
            settings.activeModules.push(module);
        });
    }
    return settings;
});
