
<script setup>
import {onMounted, onUnmounted} from 'vue'
import emitter from "@/components/event-bus"
import router from "@/router";
import {useCookies} from "@vueuse/integrations/useCookies";

const cookies = useCookies()

const loginSuccess = ()=>{
  console.log('login-success')
  emitter.emit("mainPage")
  emitter.off('login-success', loginSuccess)
  cookies.set('loginSuccess',true,{
    expires: new Date(Date.now() + 60 * 1000)
  })
}
onMounted(() => {
  emitter.on('login-success', loginSuccess)
  router.push({name:'Main'})
})
// 组件卸载时移除监听
onUnmounted(() => {
  emitter.off('switch-to-login')
  emitter.off('switch-to-main')
  emitter.off('mainPage')
  emitter.off('searchPage')
  console.log('remove')
})
</script>

<template>
  <div class="app-container">
<!--    <TopBar v-show="currentView === 'main' && currentPage !== 'selfPage'"/>-->
<!--    <ClassifyPost v-show="currentView === 'main' && currentPage === 'main'"/>-->
<!--    <MainPage v-show="currentView === 'main' && currentPage === 'main'"/>-->
<!--    <template v-if="currentPage === 'search'">-->
<!--      <SearchPage/>-->
<!--    </template>-->
<!--    <LogIn v-show="currentView === 'login'" />-->
    <router-view></router-view>
  </div>

</template>

<style>
html, body{
  height: 100%;
  margin: 0;
  padding: 0;
}

*{
  background-color:rgb(20,20,20);
  box-sizing: border-box;
}

.app-container {
  height: 100vh;
  min-height: 100%;
}
</style>
