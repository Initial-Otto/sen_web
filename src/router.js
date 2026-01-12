// src/router.js
import { createRouter, createWebHistory } from 'vue-router'
import Home from './App.vue'
import Main from './MainPage.vue'
import Log from './UserLogin.vue'
import Self from './SelfPage.vue'
import Sign from './signUp.vue'
import Change from './changeSelf.vue'
import ChangePW from './changePassword.vue'
import Admin from "./admin.vue";
// 定义路由规则
const routes = [
    {
        path: '/', // 访问路径：根路径
        name: 'Home', // 路由名称（可选，但推荐使用）
        // 这里暂时使用一个简单的组件，稍后可以替换为实际页面
        component: Home
    },
    // 可以在这里添加更多路由...
    {
        path: '/Self',
        name: 'Self',
        component: Self
    },
    {
        path:'/Main',
        name:'Main',
        component: Main
    },
    {
        path:'/Log',
        name:'Log',
        component: Log
    },
    {
        path:'/Sign',
        name:'Sign',
        component: Sign
    },
    {
        path: '/Self/Change',
        name: 'Change',
        component: Change
    },
    {
        path: '/Self/ChangePW',
        name: 'ChangePW',
        component: ChangePW
    },
    {
        path: '/Admin',
        name: 'Admin',
        component: Admin
    }
]

// 创建路由实例
const router = createRouter({
    // 使用 History 模式（URL 中不带 #，需要服务器配置）
    history: createWebHistory(),
    // 如果使用 Hash 模式（URL 中带 #，无需服务器配置）
    // history: createWebHashHistory(),
    routes // 路由配置
})

// 导出路由实例（必须是实例，不能是函数）
export default router