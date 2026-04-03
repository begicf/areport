/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

try {
    window.Popper = require('@popperjs/core');
    window.$ = window.jQuery = require('jquery');
    window.bootstrap = require('bootstrap');

    require('jquery-ui/ui/widgets/datepicker');
    require('bootstrap-table/dist/bootstrap-table.min');
    require('multiselect-two-sides/dist/js/multiselect.min')
    require('jstree/dist/jstree.min')
    require('bootstrap-select/dist/js/bootstrap-select.min')
} catch (e) {}
