import { createApp } from 'vue'
import App from './components/App.vue'
import router from './router'
import './bootstrap'
import '../css/app.css'

createApp(App)
    .use(router)
    .mount('#app')
