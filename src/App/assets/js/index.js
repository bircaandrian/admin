// Load jQuery and Bootstrap

try {
    window.$ = window.jQuery = require('jquery');
    require('bootstrap');
    require('bootstrap-fileinput');
    require('bootstrap-slider');
    require('jquery-mousewheel');

    window.toastr = require('toastr');

    window.translateText = function translateText(text)
    {
        let translation;

        $.ajax({
            type: 'post',
            url: '/language/translate-text',
            data:  {
                'text': text
            },
            async: false,
        }).done(function (response) {
            translation = response['translation'];
        }).fail(function (jqXHR) {
            return text;
        });

        return translation;
    };
} catch (e) {
}

require('./components/_language');
require('./components/_profile');
require('./components/_main');
require('./components/_bootstrap-table');
require('./components/_sidebar');
require('./components/_nanoScroller');
require('./components/_chart-data');
require('./components/_easypiechart');
require('./components/_easypiechart-data');
require('./components/_bsTable');
require('./components/_default');
require('./components/_admin');
require('./components/_user');
