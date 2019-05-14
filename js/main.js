 
import Vue from 'vue'
import App from './Application.vue'

import BootstrapVue from 'bootstrap-vue'

import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue/dist/bootstrap-vue.css'

Vue.use(BootstrapVue)

import '../css/styles.css'
import '../css/font-awesome.min.css'

import Snotify, { SnotifyPosition } from 'vue-snotify'

import "vue-snotify/styles/material.css";

const oOptions = {
    toast: {
        position: SnotifyPosition.rightTop,
        closeOnClick: true,
        //timeout: 0
    }
}

Vue.use(Snotify, oOptions)

import VueResource from 'vue-resource';

Vue.use(VueResource);

Vue.http.options.emulateJSON = true;


window.oVue = new Vue({
  el: '#application',
  render: h => h(App)
})
