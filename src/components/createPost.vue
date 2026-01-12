<script>
import emitter from './event-bus.js';
import Classify from './classifyPost.vue'
export default {
  components:{Classify},
  data(){
    return{
      ifClose:'none',
      imageUrl: null,
      imageFile: null,
      title: '',
      content: '',
      currentSection: 1,
      isLoading: false,
      isAddP:false,
      Px:0,
      Py:0,
      imgList:[],
      imgCount:0
    }
  },
  methods:{
    openPost(){
      this.ifClose='grid'
    },
    close(){
      this.ifClose='none'
      this.resetForm()
    },
    handleImageSelect(event) {
      const file = event.target.files[0]
      if (!file) return

      // 检查文件类型
      if (!file.type.startsWith('image/')) {
        alert('请选择图片文件！')
        return
      }

      // 创建临时URL用于预览
      this.imageFile = file
      this.imageUrl = URL.createObjectURL(file)
    },
    removeImage() {
      if (this.imageUrl) {
        URL.revokeObjectURL(this.imageUrl)
      }
      this.imageUrl = null
      this.imageFile = null
      this.$refs.fileInput.value = ''
      this.imgList=[]
      this.imgCount=0
    },
    resetForm() {
      this.title = ''
      this.content = ''
      this.removeImage()
      this.currentSection = 1
    },
    async submitPost() {
      // 表单验证
      if (!this.title.trim()) {
        alert('请输入标题！')
        return
      }

      if (!this.content.trim()) {
        alert('请输入内容！')
        return
      }

      if (!this.imageFile) {
        this.imageFile="/img/post/back.jpeg"
        this.imageUrl=""
      }

      // 检查用户是否登录
      const authCache = localStorage.getItem('auth_cache')
      if (!authCache) {
        alert('请先登录！')
        emitter.emit('show-login')//???
        return
      }

      const userData = JSON.parse(authCache)
      if (!userData.user || !userData.user.user_id) {
        alert('用户信息无效，请重新登录！')
        return
      }

      this.isLoading = true

      try {
        const formData = new FormData()
        formData.append('user_id', userData.user.user_id)
        formData.append('title', this.title)
        formData.append('content', this.content)
        formData.append('section_id', this.currentSection)
        formData.append('image', this.imageFile)
        this.imgList.forEach((file, index) => {
          if (file) {  // 确保文件存在
            formData.append(`imgList[${index}]`, file)  // 使用数组格式
          }
        })

        // 添加图片数量信息
        formData.append('imgCount', this.imgList.length)

        const response = await fetch('http://localhost:8000/src/php/create_post.php', {
          method: 'POST',
          credentials: 'include',
          body: formData
        })

        const result = await response.json()
        console.log(result.post.content)

        if (result.success) {
          alert('发帖成功！')
          this.close()
          // 通知主页面刷新数据
          emitter.emit('post-created', result.post)
        } else {
          alert('发帖失败：' + result.message)
        }
      } catch (error) {
        console.error('发帖错误:', error)
        alert('网络错误，请稍后重试')
      } finally {
        this.isLoading = false
      }
    },
    handleSectionChange(sectionId) {
      this.currentSection = sectionId
      console.log(this.currentSection)
      console.log("++++++++++++")
    },
    addP(event){
      event.preventDefault()
      this.isAddP = !this.isAddP
      this.Px = event.clientX
      this.Py = event.clientY-150
    },
    closeAdd(){
      this.isAddP = false
    },
    selectP(){
      this.$refs.contentFileInput.click()
    },
    insertImg(event){
      const file = event.target.files[0]
      this.content += '[img]'+this.imgCount+'[/img]'
      this.imgList[this.imgCount] = file
      this.imgCount+=1
      console.log(this.imgList)
    }
  },
  mounted() {
    // 使用 mitt 的 on 方法监听事件
    emitter.on('open-createPost-event', this.openPost);
    emitter.on('close-createPost-event',this.close)
    emitter.on('select', this.handleSectionChange)
  },
  beforeUnmount() { // 更正生命周期钩子名称
    // 组件卸载前，使用 mitt 的 off 方法移除事件监听，避免内存泄漏
    emitter.off('open-createPost-event', this.openPost);
    emitter.off('close-createPost-event',this.close)
    emitter.off('select', this.handleSectionChange)
    if (this.imageUrl) {
      URL.revokeObjectURL(this.imageUrl)
    }
  },
}

</script>

<template>
  <div class="backdrop" :style="'display:'+ifClose"></div>
  <div class="postBox" :style="'display:'+ifClose">
    <div class="head">
      <svg
          class="close"
          viewBox="0 0 220 100"
          xmlns="http://www.w3.org/2000/svg"
          preserveAspectRatio="none"
          @click="close"
      >
        <!-- 透明的SVG画布，两个矩形将填满此区域 -->
        <rect x="0" y="0" width="180" height="100" rx="50" ry="50" fill="#FF0000"/>
        <rect x="120" y="0" width="100" height="100" rx="25" ry="25" fill="#FF0000" transform="skewX(-30)"/>
        <text x="100" y="50"
              text-anchor="middle"
              dominant-baseline="middle"
              fill="black"
              font-family="Arial"
              font-size="50"
              font-weight="bold">
          x
        </text>
      </svg>
    </div>
    <div class="title">
      <input
          type="text"
          name="title"
          placeholder="请输入标题"
          v-model="title"
          maxlength="200"
      >
      <div class="classify">
        <Classify class="child" title="cPost"/>
      </div>
    </div>
    <div class="content"
         @contextmenu.prevent="addP"
         @click="closeAdd"
    >
      <form enctype="multipart/form-data">
        <input
            type="file"
            ref="fileInput"
            name="img"
            accept="image/*"
            style="display: none"
            @change="handleImageSelect"
        >
        <div class="seeImg">
          <div class="imgBox">
            <img :src="imageUrl" alt="图片预览" class="preview-image">
          </div>
          <div @click="removeImage" class="delete" v-if="imageUrl">X</div>
          <p>选择图片</p>
          <label @click="$refs.fileInput.click()">🖼</label>
        </div>
        <textarea
            name="content"
            v-model="content"
            placeholder="请输入帖子内容..."
        ></textarea>

      </form>
    </div>
    <div
        class="complete"
        @click="submitPost"
        :class="{ loading: isLoading }"
    >
      {{ isLoading ? '发布中...' : '确认' }}
    </div>
  </div>
  <div
      class="addP"
      v-show="isAddP"
      :style="'left:'+Px+'px;top:'+Py+'px'"
  >
    <input type="button" value="插入图片" @click="selectP">
    <input
        type="file"
        ref="contentFileInput"
        name="contentImg"
        accept="image/*"
        style="display: none"
        @change="insertImg"
    >
  </div>
</template>

<style scoped>
.backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  backdrop-filter: blur(10px);
  background-color: rgba(0, 0, 0, 0.5); /* 半透明黑色 */
  z-index: 10; /* 低于POSTPage但高于其他内容 */
}
.postBox{
  width: 90%;
  height: 80%;
  min-height: 400px;
  display: none;
  grid-template-columns: 10px 1fr 10px;
  grid-template-rows: 80px 150px 1fr 5px;
  gap:5px 20px;
  position: fixed;
  top:15%;
  left:5%;
  background-color: rgb(40,40,40);
  border-radius: 20px;
  border:10px rgb(60,60,60) solid;
  z-index: 10;
  backdrop-filter: blur(10px);
}
.head{
  grid-row:1;
  grid-column-start: 1;
  grid-column-end: 6;
  background-color: black;
  border-radius: 10px 10px 0 0;
  display: grid;
  grid-template-rows: 40px 40px;
  grid-template-columns: 80px 200px 1fr;
}
.close{
  position: absolute;
  top:20px;
  right: 15px;
  width: 100px;
  height: 40px;
  background-color: rgba(0,0,0,0);
  transition: transform 0.5s;
  cursor: pointer;
}
.title{
  grid-row: 2;
  grid-column: 2;
  display: grid;
  grid-template-columns: 5% 150px 1fr 5%;
  grid-template-rows: 75px 60px;
  gap: 5px;
  border-radius: 20px;
  box-shadow: 10px 5px 15px rgba(0, 0, 0, 0.3);
  margin: 0 0 10px 0;
}
.title input{
  grid-column-start: 2;
  grid-column-end: 4;
  grid-row: 1;
  color:white;
  font-size: 20px;
  width: 100%;
  margin: 10px 0 0 0;
  background-color: rgb(100,100,100);
  border-radius: 10px;
}
.classify{
  grid-column: 2;
  grid-row: 2;
}
.classify :deep(.child div){
  width: 60px;
  height: 40px;
  font-size: 15px;
  border-radius: 20px;
}
.content{
  grid-column: 2;
  grid-row: 3;
  box-shadow: 10px 5px 15px rgba(0, 0, 0, 0.3);
  border-radius: 20px;
  overflow: hidden;
}
.content form{
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  border-radius: 20px;
}
.content form textarea{
  width: 90%;
  height: 60%;
  border: none;
  overflow: auto;
  resize: none;
  font-size: 20px;
  background-color: rgb(100,100,100);
  box-shadow: 10px 10px 15px rgba(0, 0, 0, 0.3) inset;
  border-radius: 20px;
}
.seeImg label{
  font-size: 60px;
  color: white;
  cursor: pointer;
  background-color: rgba(0,0,0,0);
}
.seeImg{
  width: 90%;
  height: 30%;
  margin: 0 0 10px 0;
  background-color: rgba(0,0,0,0);
  position: relative;
  display: flex;
}
.seeImg label{
  position: absolute;
  bottom: 10px;
  right: 10px;
}
.imgBox{
  height: 100%;
  width: auto;
  min-width: 50px;
  max-width: 70%;
  background-color: rgb(100,100,100);
  border-radius: 20px;
  font-size: 100px;
  overflow: hidden;
}
.imgBox img{
  height: 100%;
  font-size: 10px;
  color: white;
  background-color: rgba(0,0,0,0);

}
.seeImg p{
  margin: 0;
  position: absolute;
  right: 80px;
  bottom: 20px;
  color: white;
}
.delete{
  position: relative;
  top:0;
  left: -3px;
  width: 15px;
  height: 15px;
  border-radius: 7px;
  background-color: red;
  font-size: 10px;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor:pointer;
}
.complete{
  position: absolute;
  bottom: 30px;
  right: 30px;
  width: 80px;
  height: 80px;
  border-radius: 40px;
  background-color: greenyellow;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor:pointer;
  transition: background-color 0.3s ease;
}
.complete:hover{
  background-color: #4CAF50;
}
.addP{
  position: absolute;
  background-color: white;
  z-index: 1010;
}
</style>