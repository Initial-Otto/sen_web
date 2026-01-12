<script>
import emitter from "@/components/event-bus"
import router from "@/router";

export default {
  data(){
    return{
      log:true,
      account:"",
      pw:""
    }
  },
  methods:{
    async logIn() {
      try {
        const response = await fetch('http://localhost:8000/src/php/login.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          credentials: 'include',
          body: JSON.stringify({
            account: this.account,
            pw: this.pw
          })
        });

        const result = await response.json();

        if (result.success) {
          // 登录成功，保存用户信息到本地或Vuex
          const authData = {
            user: result.user,
            loginTime: result.login_time || Date.now(),
            lastCheck: Date.now()
          };
          localStorage.setItem('auth_cache', JSON.stringify(authData))
          emitter.emit('login-success')

          setTimeout(() => {
            if (window.history.length > 1) {
              router.go(-1);
            } else {
              router.push('/');
            }
          }, 100);
        } else {
          alert(result.message);
        }
      } catch (error) {
        console.error('登录错误:', error);
        alert('网络错误，请稍后重试');
      }
    },
  }
}
</script>

<template>
<div class="mainWindow">
  <div class="box">
    <div class="head">
      <img class="logo" src="@/assets/LOGO.svg" alt="">
    </div>
    <div class="logBack"></div>
    <div class="log" v-if="log">
      <h1>登录</h1>
      <form >
        <input
            type="text"
            style="grid-row: 1"
            id="account"
            name="account"
            placeholder=" 账号/邮箱"
            v-model="account"
        >
        <input
            type="password"
            style="grid-row: 2"
            id="pw" name="pw"
            placeholder=' 密码'
            v-model="pw"
        >
        <div class="a" style="grid-row: 3">
          <router-link to="/Sign" @click="toSign">注册&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</router-link>
        </div>
        <input type="button" style="grid-row: 4" id="request" value="进入森网" @click="logIn">
      </form>

    </div>
  </div>
</div>
</template>

<style scoped>
.mainWindow{
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  height: 100%;
}
.box{
  min-height: 500px;
  width: 80%;
  height: 60%;
  background-color: rgb(30,30,30);
  border-radius: 20px;
  display: grid;
  grid-template-columns: 20px 1fr 20px;
  grid-template-rows: 50px 1fr;
  position: relative;
}
.head{
  grid-row: 1;
  grid-column-start: 1;
  grid-column-end: 4;
  background-color: black;
  border-radius: 20px 20px 0 0;
  display: grid;
  grid-template-rows: 1fr;
  grid-template-columns: 1fr 150px 1fr;

}
.logBack{
  background-color: black;
  width: 80%;
  height: 290px;
  margin: 0 auto;
  position: absolute;
  top:200px;
  left:10%;
  z-index: 9;
  border-radius: 20px;
}
.logo{
  grid-column: 2;
}
.log{
  margin: 0 auto;
  grid-column: 2;
  grid-row: 2;
  background-color: rgba(50,50,50,0);
  width: 80%;
  height: 260px;
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: 80px 1fr;
  gap: 20px;
  padding: 20px 0 0 0;
  z-index: 10;

}
.log h1{
  background-color: rgba(50,50,50,0);
  text-align: center;
  color: white;
  font-size: 50px;
  margin: 0;
}
.log form{
  width: 60%;
  margin: 0 auto;
  grid-row:2;
  background-color: rgba(0,0,0,0);
  display: grid;
  grid-template-rows: 60px 60px 50px 60px;
  grid-template-columns: 100%;
}
.log form input{
  width: 100%;
  height: 50px;
  background-color: rgb(140,140,140);
  border-radius: 20px;
  border:1px black solid;
  margin: 0 0 20px 0;
}
.log input::placeholder{
  color: rgb(200,200,200);
  font-size: 20px;
}
.a{
  background-color: rgba(50,50,50,0);
  display: flex;
  justify-content: center;
}
.a a{
  color: rgb(200,200,200);
  text-decoration: none;
  background-color: rgba(0,0,0,0);
}

#request{
  background-color: black;
  border: rgb(140,140,140) 2px solid;
  color: white;
  font-size: 20px;
  bottom: 20px;
  grid-row: 5;
  transition: transform 0.5s;
}
#request:hover{
  transform: translateY(-5px);
}
#account{
  grid-column: 1;
  grid-row: 2;
}
#pw{
  grid-column: 1;
  grid-row: 3;
}
</style>