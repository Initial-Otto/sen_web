<script>
import router from "@/router";

export default {
  data(){
    return{
      account:"",
      oldPw:"",
      pw:"",
      completePw:"",
      ifpw:false,
      ifcpw:false,
    }
  },
  methods:{
    handleKeyDown(event){
      if (event.keyCode === 32 || event.key === ' ') {
        event.preventDefault()
      }
    },
    handleBeforeInput(event){
      if (event.data && /[^a-zA-Z0-9,@.]/.test(event.data)) {
        event.preventDefault()
        console.log('已阻止非法字符输入:', event.data)
      }
    },
    async chPW() {
      if(!this.ifpw && !this.ifcpw && this.pw !== "" && this.completePw !== "") {
        try {
          const response = await fetch('http://localhost:8000/src/php/changePassword.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              account: this.account,
              oldPw: this.oldPw,
              pw: this.pw,
              comPw: this.completePw
            })
          });

          const result = await response.json();

          if (result.success) {
            alert(result.message);
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
          } else {
            alert(result.message);
          }
        } catch (error) {
          console.error('密码修改请求失败:', error);
          alert('网络错误，请稍后重试');
        }
      } else {
        alert("请正确输入内容");
      }
    }
  },
  watch:{
    pw(){
      if(this.pw===""){
        this.ifpw=false
      }else {
        if (this.pw.length < 8) {
          this.ifpw=true
        }else {
          const hasLetter = /[a-zA-Z]/.test(this.pw);
          const hasNumber = /[0-9]/.test(this.pw);
          this.ifpw = !(hasLetter && hasNumber);
        }
      }
    },
    completePw(){
      if(this.completePw===""){
        this.ifcpw=false
      }else {
        this.ifcpw = this.completePw !== this.pw;
      }
    }
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
      <div class="log">
        <h1>修改密码</h1>
        <form action="">
          <input
              type="text"
              id="account"
              name="account"
              v-model="account"
              placeholder=" 账号/邮箱"
          >
          <input
              type="password"
              id="pw"
              name="oldPw"
              v-model="oldPw"
              placeholder=' 旧密码'
          >
          <input
              type="password"
              @keydown="handleKeyDown"
              @beforeinput="handleBeforeInput"
              id="pw" name="pw"
              v-model="pw"
              placeholder=' 新密码'
          >
          <p v-show="ifpw">请输入含字母与数字的至少8位密码</p>
          <input
              type="password"
              @keydown="handleKeyDown"
              @beforeinput="handleBeforeInput"
              id="comPw"
              name="comPw"
              v-model="completePw"
              placeholder=' 确认密码'
          >
          <p v-show="ifcpw">与密码不一致</p>
          <input
              type="button"
              id="request"
              value="确认修改"
              @click="chPW"
          >
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
  min-height: 600px;
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
  height: 360px;
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
  grid-template-rows: 80px 60px 60px 50px 60px;
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
  background-color: rgba(0,0,0,0);
}
.log form input{
  width: 100%;
  height: 50px;
  background-color: rgb(140,140,140);
  border-radius: 20px;
  border:1px black solid;
  margin: 0 0 20px 0;
}
.log form p{
  margin: 0;
  padding: 0;
  color: red;
  position: relative;
  top:-10px
}
.log input::placeholder{
  color: rgb(200,200,200);
  font-size: 20px;
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