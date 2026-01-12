<script>
import router from "@/router";

export default {
  data(){
    return{
      name:'',
      Email:'',
      Account:'',
      img:null,
      id:0,
      chImg:false,
      allImg:[],
      selectImg:"",
      chname:"",
      chaccount:"",
      chemail:"",
      ifac:false,
      ifem:false,
      userInfo: {}
    }
  },
  methods:{
    getInfo(){
      this.name='myname'
      this.Email='2894163607@qq.com'
      this.img=require("@/assets/a.jpg")
      this.Account='abcdefghijklmn'
    },
    handleKeyDown(event){
      if (event.keyCode === 32 || event.key === ' ') {
        event.preventDefault()
      }
    },
    handleBeforeInput_ac(event){
      if (event.data && /[^a-zA-Z0-9]/.test(event.data)) {
        event.preventDefault()
        console.log('已阻止非法字符输入:', event.data)
      }
    },
    handleBeforeInput(event){
      if (event.data && /[^a-zA-Z0-9,@.]/.test(event.data)) {
        event.preventDefault()
        console.log('已阻止非法字符输入:', event.data)
      }
    },
    async submitProfile() {
      if(this.chname !== "" || this.chaccount !== "" || this.chemail !== "" || this.selectImg !== ""){
        if (!this.ifac && !this.ifem) {
          try {
            // 收集表单数据
            const formData = {
              username: this.chname,
              email: this.chemail,
              account: this.chaccount,
              avatar_path: this.selectImg,
              userid:this.id
            };

            const response = await fetch('http://localhost:8000/src/php/change_self.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              credentials: 'include',
              body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
              alert('个人简介更新成功！');

              // 更新本地用户信息
              if (result.user) {
                console.log(result)
                const cachedAuth = localStorage.getItem('auth_cache');
                let authData = { loginTime: Date.now(), lastCheck: Date.now() };

                if (cachedAuth) {
                  try {
                    const oldAuth = JSON.parse(cachedAuth);
                    // 保留原有的登录时间，只更新用户数据和检查时间
                    authData.loginTime = oldAuth.loginTime || Date.now();
                  } catch (e) {
                    console.warn('解析缓存失败，使用新结构');
                  }
                }
                authData.user = result.user;
                authData.lastCheck = Date.now();
                localStorage.setItem('auth_cache', JSON.stringify(authData));
                // 更新显示的数据
                console.log(localStorage.getItem('auth_cache'))
                this.name = result.user.username || this.name;
                this.Email = result.user.email || this.Email;
                this.Account = result.user.account || this.Account;

              }

              this.back();
            } else {
              alert('更新失败: ' + result.message);
            }
          } catch (error) {
            console.error('更新个人简介失败:', error);
            alert('网络错误，请稍后重试');
          }
        } else {
          alert("请输入正确的格式")
        }
      }else{
        this.back()
      }
    },
    async checkLoginStatus(forceCheck = false) {
      const cachedAuth = localStorage.getItem('auth_cache');
      if (cachedAuth && !forceCheck) {
        const authData = JSON.parse(cachedAuth);
        const cacheAge = Date.now() - authData.lastCheck;

        // 如果缓存时间在2小时内，直接使用缓存
        if (cacheAge < 2 * 60 * 60 * 1000) {
          console.log(1)
          // 直接使用 authData.user 而不是 userInfo.value
          console.log(authData.user.avatar_path)
          this.img = `/${authData.user.avatar_path}`
          this.name = authData.user.username
          this.Account = authData.user.account
          this.Email = authData.user.email
          this.id = authData.user.user_id
          console.log("缓存获取")
          console.log(authData.user)
          return;
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

          // 直接更新组件数据，不使用 userInfo 变量
          this.img = `/${result.user.avatar_path}`
          this.name = result.user.username
          this.Account = result.user.account
          this.Email = result.user.email
        } else {
          this.back()
        }
      } catch (error) {
        console.error('检查登录状态失败:', error)
        this.back()
      }
    },
    chMyImg(){
      this.chImg=true
    },
    close(){
      this.chImg=false
    },
    change(index){
      this.img=this.allImg[index].fullPath
      this.selectImg=this.allImg[index].relativePath
      console.log(this.selectImg)
      this.close()
    },
    back(){
      router.go(-1)
    },
    loadImg(){
      const imageContext = require.context(
          '@/img/head/', // 图片存放路径
          false,               // 不遍历子目录
          /\.(png|jpg|jpeg|gif|svg)$/ // 匹配的图片格式
      );

      // 遍历所有匹配的图片文件
      imageContext.keys().forEach(key => {
        // 将图片路径添加到数组中
        const fileName = key.replace('./', '');
        const relativePath = `img/head/${fileName}`;
        const fullPath = imageContext(key);
        this.allImg.push({
          fullPath: fullPath,        // 完整的webpack路径，用于显示
          relativePath: relativePath
        });
      });
    },
  },
  watch:{
    chaccount(){
      if(this.chaccount===""){
        this.ifac=false
      }else {
        if(this.chaccount.length <9){
          this.ifac=true
        }else {
          this.ifac = false
        }
      }
    },
    chemail(){
      const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/
      if(this.chemail===""){
        this.ifem=false
      }else {
        if(!emailRegex.test(this.chemail)) {
          this.ifem = true
        }else {
          this.ifem = false
        }
      }
    }
  },
  mounted() {
    this.getInfo()
    this.loadImg()
    this.checkLoginStatus()
  }
}
</script>

<template>
<div class="main">
  <div class="img" @click="chMyImg">
    <img alt="头像" :src="img">
  </div>
  <hr>
  <form>
    <div class="detail">
      <p>&nbsp;&nbsp;&nbsp;用户名：{{name}}</p>
      <input
          type="text"
          name="name"
          v-model="chname"
      >
      <p>&nbsp;&nbsp;&nbsp;账号：{{Account}}</p>

      <input
          type="text"
          name="account"
          v-model="chaccount"
          @keydown="handleKeyDown"
          @beforeinput="handleBeforeInput_ac"
      >
      <p v-show="ifac" style="color: red;padding: 0 0 0 20px">账号长度小于9</p>
      <p>&nbsp;&nbsp;&nbsp;邮箱：{{Email}}</p>

      <input
          type="text"
          name="email"
          v-model="chemail"
          @keydown="handleKeyDown"
          @beforeinput="handleBeforeInput"
      >
      <p v-show="ifem" style="color: red;padding: 0 0 0 20px">邮箱格式错误</p>
      <input type="button" value="确认修改" class="ok" @click="submitProfile">

    </div>
  </form>
  <div v-show="chImg" class="imgBox">
    <h1>修改头像</h1><br>
    <div
        class="allImg"
        v-for="(item,index) in allImg"
        :key="index"
        @click="change(index)"
    >
      <img :src="item.fullPath" alt="头像">
    </div>
  </div>
  <div v-if="chImg" class="panel-overlay" @click="close"></div>
</div>
</template>

<style scoped>
.main{
  width: 90%;
  height: auto;
  margin: 0 auto;
  background-color: rgb(100,100,100);
  border-radius: 20px;
  position: relative;
  top:20px
}
.imgBox{
  position: absolute;
  top: 60px;
  left: 150px;
  border-radius: 20px;
  max-width: 500px;
  overflow: hidden;
  z-index: 4;
}
.imgBox h1{
  color: white;
  position: relative;
  top: -10px;
  margin: 20px 0 0 20px;
}
.allImg{
  width: 100px;
  height: 100px;
  border-radius: 50px;
  margin: 10px;
  overflow: hidden;
  display: inline-block;
}
.allImg img{
  height: 100%;
  width: 100%;
  cursor: pointer;
}
.img{
  width: 100px;
  height: 100px;
  border-radius: 50px;
  overflow: hidden;
  margin: 40px;
  position: relative;
  top:20px;
  cursor: pointer;
  transition: transform 0.5s;
}
.img:hover{
  transform: translateY(-5px);
}
.img img{
  width: 100%;
  height: 100%;
}
hr{
  position: relative;
  top:20px;
  height: 2px;
  width: 90%;
  background-color: rgba(0,0,0,0);
}
form{
  background-color: rgba(0,0,0,0);
}
.detail{
  position: relative;
  left: 5%;
  width: 90%;
  height: 90%;
  min-height: 750px;
  background-color: rgb(140,140,140);
  border-radius: 20px;
  margin: 40px 0 40px 0;
  border: 2px black solid;
  display: grid;
  grid-auto-rows: 100px;
}
.detail p{
  background-color: rgba(0,0,0,0);
  font-size: 20px;
  display: flex;
  align-items: center;
}
.detail input[type="text"]{
  font-size: 20px;
  height: 80%;
  width: 80%;
  margin: 10px 0 10px 10px;
  background-color: rgb(100,100,100);
  border-radius: 20px;
}
.ok{
  width: 200px;
  height: 100px;
  margin: 0 auto;
  border-radius: 50px;
  background-color: rgb(50,50,50);
  color:  white;
  cursor: pointer;
  border: 10px black solid;
  box-shadow: 5px 5px 10px rgba(255,255,255,0.3);
  transition: background-color 0.5s, transform 0.5s;
}
.ok:hover{
  background-color: rgb(100,100,100);
  transform: translateY(5px);
}
.panel-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: transparent;
  z-index: 3;
}
</style>