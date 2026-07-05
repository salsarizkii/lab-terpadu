import { createApp } from 'vue'
import { pinia } from './stores/pinia'
import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/authStore'
import './assets/design-system.css'

const app = createApp(App)

app.use(pinia)
app.use(router)

const auth = useAuthStore(pinia)
auth.initFromStorage()

if (auth.isAuthenticated) {
  auth.fetchMe().finally(() => {
    app.mount('#app')
  })
} else {
  app.mount('#app')
}
