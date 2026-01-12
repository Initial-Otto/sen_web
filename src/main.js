// main.js
import { createApp } from 'vue'
import App from './App.vue'
import router from './router' // 导入上一步创建的路由实例

const app = createApp(App)
app.use(router) // 使用路由插件
app.mount('#app')