<template>
  <header>
    <div class="profile_box" @click="toSelf">
      <div class="profile" v-show="first" @click="toLog">登录</div>
      <div class="profile" v-show="!first">
        <img :src="img" alt="头像">
      </div>
      <p class="name">{{ userInfo.username || '用户名' }}</p>
      <p class="level">{{ userInfo.level || 0 }}<br />LEVEL</p>
      <div class="progress-container">
        <div class="now" :style="eNow" v-if="!first"></div>
      </div>
    </div>
    <div class="logo"><img src="@/assets/LOGO.svg"></div>
    <div class="menu_box">
      <!-- 左侧SVG：圆角矩形 -->
      <svg
          class="menu-svg-left"
          viewBox="0 0 220 100"
          xmlns="http://www.w3.org/2000/svg"
          preserveAspectRatio="none"
          :class="{clicked:leftClicked}"
          @click="toggleLeft"
      >
        <!-- 透明的SVG画布，两个矩形将填满此区域 -->
        <rect x="0" y="0" width="180" height="100" rx="50" ry="50" fill="#808080"/>
        <rect x="120" y="0" width="100" height="100" rx="20" ry="20" fill="#808080" transform="skewX(-30)"/>
        <text x="100" y="50"
              text-anchor="middle"
              dominant-baseline="middle"
              fill="white"
              font-family="Arial"
              font-size="30"
              font-weight="bold">
          主页
        </text>
      </svg>

      <!-- 右侧SVG：倾斜矩形 -->
      <svg
          class="menu-svg-right"
          viewBox="0 0 220 100"
          xmlns="http://www.w3.org/2000/svg"
          preserveAspectRatio="none"
          :class="{ clicked: rightClicked }"
          @click="toggleRight"
      >
        <!-- 透明的SVG画布，两个矩形将填满此区域 -->
        <rect x="55" y="0" width="100" height="100" rx="20" ry="20" fill="#323232" transform="skewX(-30)"/>
        <rect x="35" y="0" width="180" height="100" rx="50" ry="50" fill="#323232"/>
        <text x="110" y="50"
              text-anchor="middle"
              dominant-baseline="middle"
              fill="white"
              font-family="Arial"
              font-size="30"
              font-weight="bold">
          发现
        </text>
      </svg>
    </div>
  </header>
  <div class="back"></div>
</template>
<script setup>
import {onMounted, onUnmounted, ref} from 'vue'
import {useRouter} from 'vue-router'
import emitter from "@/components/event-bus";

const router = useRouter()
const leftClicked=ref(false)
const rightClicked=ref(false)
const first=ref(true)
const eNow=ref("")
const userInfo = ref({})
const img = ref("")

const checkLoginStatus = async (forceCheck = false) => {
  const cachedAuth = localStorage.getItem('auth_cache');
  if (cachedAuth && !forceCheck) {
    const authData = JSON.parse(cachedAuth);
    const cacheAge = Date.now() - authData.lastCheck;

    // 如果缓存时间在2小时内，直接使用缓存
    if (cacheAge < 2 * 60 * 60 * 1000) {
      first.value = false;
      userInfo.value = authData.user;
      console.log(userInfo.value.avatar_path)
      img.value = `/${userInfo.value.avatar_path}`

      getLevel(userInfo.value.level, userInfo.value.experience);
      console.log("缓存获取")
      console.log(userInfo.value)
      return;
    }
  }
  try {
    const response = await fetch('http://localhost:8000/src/php/check_login.php', {
      method: 'GET',
      credentials: 'include', // 重要：包含Cookie
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

      first.value = false
      userInfo.value = result.user
      console.log(userInfo.value.avatar_path)
      img.value = `/${userInfo.value.avatar_path}`
      getLevel(userInfo.value.level, userInfo.value.experience)
    } else {
      // 用户未登录
      first.value = true
      userInfo.value = {};
    }
  } catch (error) {
    console.error('检查登录状态失败:', error)
    first.value = true // 默认显示登录
    userInfo.value = {};
  }
}

const toggleLeft=()=>{
  if(!leftClicked.value){
    leftClicked.value = !leftClicked.value
    if (leftClicked.value) {
      rightClicked.value = false
    }
    emitter.emit("mainPage")
    emitter.emit('reset-mainPage')
  }
}
const toggleRight=()=>{
  if(!rightClicked.value){
    rightClicked.value = !rightClicked.value
    if (rightClicked.value) {
      leftClicked.value = false
    }
    emitter.emit("searchPage")
    console.log("searchPage")
  }
}
const toLog=()=>{
  emitter.emit('switch-to-login');

  router.push({name:'Log'})
}
const loginSuccess = (userData)=>{
  console.log('login-success')
  emitter.emit("mainPage")

  first.value = false
  userInfo.value = userData // 保存用户信息
  getLevel(userInfo.value.level, userInfo.value.experience)
}
const toSelf = () => {
  if(!first.value){
    emitter.emit('selfPage')
    router.push({name:'Self'})
  }
}
const getLevel = (level = 0, experience = 0) => {
  let computeValue = [500, 1000, 1500, 2000]
  eNow.value = "width:"+experience/computeValue[level]*100+"%"
}
onMounted(()=>{
  checkLoginStatus()
  leftClicked.value=true
  emitter.on('login-success', loginSuccess)

  checkLoginStatus()
})
onUnmounted(()=>{
  emitter.off('login-success', loginSuccess)
})
</script>
<style scoped>
header{
  display:flex;
  justify-content: space-between;
  align-items:center;
  background-color:black;
  width:100%;
  height:100px;
  position:fixed;
  margin:0;
  top:0;left:0;
  z-index:1000;
}
/*顶部左*/
/* 个人简介盒*/
.profile_box {
  position:relative;
  text-align: left;
  min-width: 250px;
  max-width: 250px;
  height: 50px;
  border-radius: 25px;
  background-color: rgb(30 30 30);
  box-shadow:inset 0 1px 1px rgba(255,255,255,0.2);
  top:5%;
  left: 2%;
  transition:transform 0.5s;
  z-index: 2;
}
/* 个人简介*/
.profile {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: rgb(50,50,50);
  position: absolute;
  top: 5px;
  left: 5px;
  display: flex;
  justify-content: center;
  align-items: center;
  transition:transform 0.5s;
  box-shadow:inset 0 1px 2px rgba(225,225,225,0.2);
  cursor:pointer;
  color:white;
  flex: none;
  overflow: hidden;
}
.profile img{
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}
/* 个人等级*/
.level {
  background-color: rgba(30,30,30,0.1);
  min-height:40px;
  min-width:40px;
  color: white;
  font-size: 10pt;
  text-align: center;
  position: absolute;
  top:6px;
  right: 15px;
  margin:0;
}
/*用户名*/
.name{
  position:absolute;
  top:5px;
  left:25%;
  max-width:90px;
  background-color:rgba(30,30,30,0.1);
  color:white;
  margin:0;
}
/*等级进度条*/
.progress-container {
  height: 10px;
  width: 45%;
  background-color: rgb(40 40 40);
  box-shadow: inset 0 0 2px rgba(255,255,255,0.2);
  border-radius: 5px;
  position: absolute;
  bottom: 10px;
  left: 25%;
}
.now{
  height: 8px;
  background-color: #4CAF50;
  margin: 1px 0 1px 1px;
  border-radius: 4px;
}

/*中间logo*/
.logo{
  position: relative;
  text-align: center;
  object-fit: contain;
  max-height: 100px;
  /*max-width: 50%;*/
  /*min-width: 10%;*/
  min-width: 250px;
  max-width: 250px;
  min-height: 50px;
  z-index: 1;
  top: 5%;
  flex: none;
  background: black;

}

/*菜单栏*/
.menu_box {
  position: relative;
  z-index: 2;
  /*max-width: 25%;*/
  /*width: 10%;*/
  min-width: 250px;
  max-width: 250px;
  height: 80px;
  top: 5%;
  right: 2%;
  background-color: rgba(0,0,0,0);
  flex: none;
  margin: 0;
  padding: 0 5px; /* 添加内边距避免SVG贴边 */
  cursor: pointer;
}

/* SVG样式 - 各占一半宽度，高度与menu_box一致 */
.menu-svg-left {
  position: absolute;
  top:0;
  left:-7px;
  width: 155px;
  height: 60px;
  opacity: 1;
  transition: all 0.3s ease;
  background: rgba(0,0,0,0);
}
.menu-svg-left rect {
  fill: #808080;
  transition: fill 0.3s ease;
}
.menu-svg-left.clicked{
  z-index: 1001;
  animation: pulseAnimation 2s infinite ease-in-out;
}
.menu-svg-left.clicked rect {
  animation: colorChange 2s infinite ease-in-out;
}
.menu-svg-left.clicked text{
  fill:black;
}
.menu-svg-right {
  position: absolute;
  top:0;
  right:-7px;
  width: 155px;
  height: 60px;
  opacity: 1;
  transition: all 0.3s ease;
  background: rgba(0,0,0,0);
}
.menu-svg-right rect {
  fill: #808080;
  transition: fill 0.3s ease;
}
.menu-svg-right.clicked {
  animation: pulseAnimation 2s infinite ease-in-out;
}

.menu-svg-right.clicked rect {
  animation: colorChange 2s infinite ease-in-out;
}
.menu-svg-right.clicked text{
  fill:black;
}
@keyframes pulseAnimation {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.15);
  }
}

/* 颜色渐变动画定义 */
@keyframes colorChange {
  0%, 100% {
    fill: #4CAF50; /* 绿色 */
  }
  50% {
    fill: #FFEB3B; /* 黄色 */
  }
}


/* 悬停效果 */
.menu-svg-left:hover,
.menu-svg-right:hover {
  opacity: 1;
  transform: scale(1.05);
}

.menu-svg-left:hover rect,
.menu-svg-right:hover rect {
  filter: brightness(1.2);
}
.menu-svg-left:not(.clicked) rect,
.menu-svg-right:not(.clicked) rect {
  fill: #323232 !important;
  animation: none;
}

.profile_box:hover {
  transform: translateY(-5px);
}
.profile:hover{
  transform:scale(1.1)
}
.back{
  width: 100%;
  height: 100px;
}
</style>