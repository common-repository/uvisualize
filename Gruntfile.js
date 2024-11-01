module.exports = function(grunt) {
    grunt.loadNpmTasks('grunt-angular-gettext');
    grunt.initConfig({
      nggettext_extract: {
        options: {
            extensions: {
                htm: 'html',
                html: 'html',
                php: 'html',
                js: 'js'
            }
        },
        pot: {
          files: {
            'i18n/messages.pot': [
                'modules/*/js/*.js',
                'modules/*/templates/*.html',
                'modules/*/templates/*.php'
            ]
          }
        }
      },
      nggettext_compile: {
        all: {
          options: {
            module: 'ngAppUvis'
          },
          files: {
            'i18n/translations.js': ['i18n/po/*.po']
          }
        }
      }
    });
    grunt.registerTask('default', []);
    grunt.registerTask('extract-messages', ['nggettext_extract']);
    grunt.registerTask('compile-messages', ['nggettext_compile']);
};
