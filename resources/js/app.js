import { createApp } from 'vue'
import App from './components/App.vue'
import router from './router'
import './bootstrap'
import '../css/app.css'
import axios from 'axios'

window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
window.axios.defaults.withCredentials = true

const token = localStorage.getItem('token')
if (token) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

createApp(App)
    .use(router)
    .mount('#app')
