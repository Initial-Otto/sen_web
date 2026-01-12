<script>
import router from "@/router";
import emitter from "@/components/event-bus";

export default {
  data(){
    return{
      account:"",
      pw:"",
      completePw:"",
      ifac:false,
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
    async Sign() {
      if (!this.ifac
          && !this.ifpw
          && !this.ifcpw
          && this.account !== ""
          && this.pw !== ""
          && this.completePw !== ""
      ) {
        try {
          const response = await fetch('http://localhost:8000/src/php/register.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              account: this.account,
              pw: this.pw,
              comPw: this.completePw
            })
          });

          const result = await response.json();

          if (response.ok) {
            alert("注册成功！");
            this.$router.push('/login');
            router.go(-1)
            emitter.emit("signSuccess")
          } else {
            alert(result.error || "注册失败");
          }
        } catch (error) {
          console.error('注册请求失败:', error);
          alert("网络错误，请稍后重试");
        }

      } else {
        alert("请正确输入内容")
      }
    }
  },
  watch:{
    account(){
      const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/
      if(this.account===""){
        this.ifac=false
      }else {
        if(this.account.length <9){
          if(!emailRegex.test(this.account)) {
            this.ifac = true
          }else {
            this.ifac = false
          }
        }else {
          this.ifac = false
        }
      }
    },
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
        <h1>注册</h1>
        <form action="">
          <input
              type="text"
              @keydown="handleKeyDown"
              @beforeinput="handleBeforeInput"
              id="account"
              name="account"
              v-model="account"
              placeholder=" 账号/邮箱"
          >
          <p v-show="ifac">账号长度小于9/邮箱格式错误</p>
          <input
              type="password"
              @keydown="handleKeyDown"
              @beforeinput="handleBeforeInput"
              id="pw" name="pw"
              v-model="pw"
              placeholder=' 密码'
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
          <input type="button" id="request" value="进入森网" @click="Sign">
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