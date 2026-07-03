import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/authStore'
import './assets/design-system.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

const auth = useAuthStore()
auth.initFromStorage()

// Refresh permissions dari backend setelah init dari storage
if (auth.isAuthenticated) {
  auth.fetchMe()
}

app.mount('#app')
