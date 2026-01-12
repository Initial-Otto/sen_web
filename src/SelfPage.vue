<script>
import MyPost from './components/myPost.vue'
import MyCollect from './components/myCollect.vue'
import router from "@/router";
import { useCookies } from '@vueuse/integrations/useCookies'
import emitter from "@/components/event-bus";
export default {
  components: {MyCollect, MyPost},
  data(){
    return{
      backgroundImg:"",
      userID:"",
      pImg:"",
      name:"",
      level:0,
      eValue:0,
      eNow:"",
      computeValue:[500,1000,1500,2000],
      page:'myPost',
      showSettingsPanel:false,
      cookies:useCookies(),
      isLoading:true,
      noMore:false,
    }
  },
  methods:{
    router() {
      return router
    },
    backHome(){
      this.$router.go(-1)
    },
    getImg(type){
      if(type==='background'){
        this.backgroundImg = "background-image:url(\""+require("@/img/background/back.jpeg")+"\")"
      }else if(type === 'pImg'){
        this.pImg = require("@/assets/b.jpg")
      }
    },
    getName(){
      this.name="name"
    },
    getLevel(){
      this.level=0
      this.eValue=250
    },
    changePage(page){
      this.page = page
    },
    toggleSettingsPanel(){
      this.showSettingsPanel = !this.showSettingsPanel
    },
    handleEditProfile(){
      this.toggleSettingsPanel()
      router.push('/Self/Change')
    },
    handleChangePassword(){
      this.toggleSettingsPanel()
      router.push('/Self/ChangePW')
    },
    handleLogout() {
      this.toggleSettingsPanel()

      // 发送退出登录请求到后端
      fetch('http://localhost:8000/src/php/logout.php', {
        method: 'POST',
        credentials: 'include'
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          console.log(data)
          document.cookie = 'PHPSESSID=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';

          // 清除本地存储的用户信息
          localStorage.removeItem('auth_cache')
          router.push('/Main?_reload=' + Date.now())
        }
      })
      .catch(error => {
        console.error('退出登录失败:', error)

        router.push('/Main?_reload=' + Date.now())
      })
    },
    async checkLoginStatus(forceCheck = false) {
      const cachedAuth = localStorage.getItem('auth_cache');
      if (cachedAuth && !forceCheck) {
        const authData = JSON.parse(cachedAuth);
        const cacheAge = Date.now() - authData.lastCheck;

        // 如果缓存时间在2小时内，直接使用缓存
        if (cacheAge < 2 * 60 * 60 * 1000) {
          this.pImg = `/${authData.user.avatar_path}`
          this.name = authData.user.username
          this.level = authData.user.level
          this.eValue = authData.user.experience
          this.userID = authData.user.user_id
          return
        }
      }
      try {
        const response = await fetch('http://localhost:8000/src/php/check_login.php', {
          method: 'GET',
          credentials: 'include',
          withCredentials: true
        })

        const result = await response.json()
        console.log(result)

        if (result.success && result.logged_in) {
          // 用户已登录
          const newAuthData = {
            user: result.user,
            loginTime: Date.now(),
            lastCheck: Date.now()
          };
          localStorage.setItem('auth_cache', JSON.stringify(newAuthData));

          this.pImg = `/${result.user.avatar_path}`
          this.name = result.user.username
          this.level = result.user.level
          this.eValue = result.user.experience
          this.userID = result.user.user_id

        } else {
          await router.push("/Main")
        }
      } catch (error) {
        console.error('检查登录状态失败:', error)
        await router.push("/Main")
      }

    },
    gotoAdmin(){
      console.log("已触发路由")
      router.push({name:'Admin'})

    }
  },
  async mounted() {
    this.getImg("background")
    this.getImg('pImg')
    this.getName()
    this.getLevel()
    await this.checkLoginStatus()
    console.log(this.userID)
    console.log("「」「」「」「」「」「」「」「」「」「」「」「」")
    emitter.on('loading', () => {
      this.isLoading = true
    })
    emitter.on('load', () => {
      this.isLoading = false
    })
    emitter.on('noMore', () => {
      this.noMore = true
    })
    if ('scrollRestoration' in history) {
      history.scrollRestoration = 'manual';
    }

    // 立即滚动到顶部
    window.scrollTo(0, 0);

    // 添加一个小的延迟确保滚动生效
    setTimeout(() => {
      window.scrollTo(0, 0);
    }, 10);
  },
  unmounted() {
    emitter.off('loading')
    emitter.off('load')
    emitter.off('noMore')
  },
  watch:{
    eValue(){
      this.eNow = "width:"+this.eValue/this.computeValue[this.level]*100+"%"
    }
  }
}
</script>

<template>
<header :style="backgroundImg">
  <h2 @click="backHome">首页</h2>
  <div class="info">
    <div class="pImg">
      <img :src="pImg">
    </div>
    <div class="name">{{name}}</div>
    <div class="levelBox">
      <p class="level">{{level}} LEVEL</p>
      <div class="progress-container">
        <div class="now" :style="eNow"></div>
      </div>
    </div>
  </div>
  <div class="setting" @click="toggleSettingsPanel">⚙️</div>
  <div v-if="showSettingsPanel" class="settings-panel">
    <ul class="settings-list">
      <!-- 修改简介 -->
      <li class="setting-item" @click="handleEditProfile">
        <span class="item-icon">👤</span>
        <span class="item-text">修改简介</span>
      </li>

      <!-- 修改密码 -->
      <li class="setting-item" @click="handleChangePassword">
        <span class="item-icon">🔒</span>
        <span class="item-text">修改密码</span>
      </li>

      <!-- 退出登录 -->
      <li class="setting-item" @click="handleLogout">
        <span class="item-icon">🚪</span>
        <span class="item-text">退出登录</span>
      </li>
    </ul>
  </div>
  <div v-if="showSettingsPanel" class="panel-overlay" @click="toggleSettingsPanel"></div>
  <div class="black"></div>
</header>
<main>
  <div class="linkBox" @click="changePage('myPost')">见闻</div>
  <div class="linkBox" @click="changePage('myCollect')">收藏</div>
  <MyPost v-if="page === 'myPost'"/>
  <MyCollect v-else/>
</main>
  <div v-if="isLoading" class="loading-indicator">
    加载中...
  </div>

  <!-- 没有更多数据的提示 -->
  <div v-if="noMore" class="no-more">
    没有更多内容了
  </div>
  <input
      type="button"
      v-if="userID === '1'"
      @click="gotoAdmin"
      style="position: absolute; top:100px; left: 300px; width: 100px ;height: 100px; color: white; cursor: pointer; z-index: 1000"
      value="管理员"
  >
</template>

<style scoped>
header{
  position: relative;
  width: 100%;
  height: 250px;
  background-size: 100% auto;
}

header h2{
  color: white;
  position: absolute;
  top: 10px;
  left: 20px;
  background-color: rgba(140,140,140,0.6);
  width: 40px;
  height: 40px;
  border-radius: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor:pointer;
  transition: transform 0.5s;
  font-size: 15px;
  z-index: 2;
}
header h2:hover{
  transform: translateY(-5px);
}

.black{
  position: absolute;
  width: 100%;
  height: 250px;
  background-color: rgba(0,0,0,0.4);
  z-index: 1;
}
.info{
  position: absolute;
  bottom: 10px;
  left: 50px;
  width: 330px;
  height: 100px;
  display: grid;
  grid-template-rows: 50px 50px;
  grid-template-columns: 100px 200px;
  column-gap: 20px;
  background-color: rgba(0,0,0,0);
  z-index: 2;
}
.pImg{
  width: 80px;
  height: 80px;
  border-radius: 40px;
  overflow: clip;
  grid-row-start: 1;
  grid-row-end: 3;
  grid-column: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  margin: 10px;
}
.pImg img{
  width: 80px;
}

.name{
  font-size: 40px;
  color:white;
  grid-column: 2;
  grid-row: 1;
  background-color: rgba(0,0,0,0);
}


.levelBox{
  grid-column: 2;
  grid-row: 2;
  display: flex;
  flex-direction:column;
  background-color: rgba(0,0,0,0);
}
.progress-container {
  width: 80%;
  height: 10px;
  background-color: rgb(40 40 40);
  box-shadow: inset 0 0 2px rgba(255,255,255,0.2);
  border-radius: 5px;
  margin: 5px 0 0 0;
}
.now{
  height: 8px;
  background-color: #4CAF50;
  margin: 1px 0 1px 1px;
  border-radius: 4px;
}
.level {
  background-color: rgba(0,0,0,0);
  color: white;
  font-size: 10pt;
  margin:0;
}

main{
  width: 100%;
  height: 100%;
  position: relative;
}
.linkBox{
  margin: 10px;
  display: inline-block;
  width: 60px;
  height: 40px;
  border-radius: 20px;
  background-color: rgb(100,100,100);
  text-align: center;
  line-height:40px;
  color:white;
  font-size: 20px;
  cursor:pointer;
  transition: transform 0.5s;
}
.linkBox:hover{
  transform: translateY(-5px);
}
.setting{
  width: 40px;
  height: 40px;
  border-radius: 20px;
  background-color: rgba(0,0,0,0.3);
  z-index: 2;
  position: absolute;
  top:20px;
  right: 20px;
  font-size: 26px;
  text-align: center;
  cursor:pointer;
  transition: background-color 0.3s ease;
}
.setting:hover{
  background-color: rgb(90,90,90);
}

.settings-panel {
  position: absolute;
  top: 40px;
  right: 40px;
  width: 150px;
  background-color: rgb(100,100,100);
  border-radius: 20px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
  z-index: 4;
  transform-origin: top right;
  animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), fadeIn 0.2s ease-out;
}
@keyframes scaleIn {
  from {
    transform: scale(0.8);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.settings-panel {
  animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.settings-list {
  list-style: none;
  padding: 8px 0;
  margin: 0;
}

.setting-item {
  display: flex;
  align-items: center;
  padding: 10px 15px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.setting-item:hover {
  background-color: rgb(140,140,140);
}

.item-icon {
  margin-right: 10px;
  font-size: 16px;
  background-color: rgba(0,0,0,0);
}

.item-text {
  color: white;
  font-size: 14px;
  background-color: rgba(0,0,0,0);
}

/* 遮罩层样式 */
.panel-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: transparent;
  z-index: 3;
}
.loading-indicator, .no-more {
  text-align: center;
  padding: 20px;
  color: #666;
  font-size: 14px;
  position: fixed;
  bottom: 10px;
  left: 45%;
  border-radius: 20px;
  background-color: rgba(255,255,255,0.7);
  z-index: 1;
}
</style>