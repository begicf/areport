require('./bootstrap');
import initTreeModule from './modules/tree';

if (document.getElementById('modules')) {
    initTreeModule();
}
