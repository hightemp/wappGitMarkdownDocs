 
import Vue from 'vue'
import App from './Application.vue'

import BootstrapVue from 'bootstrap-vue'

import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue/dist/bootstrap-vue.css'

import '../css/styles.css'

Vue.use(BootstrapVue)

window.oApplication = new Vue({
  el: '#application',
  render: h => h(App)
})
