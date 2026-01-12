<script>
import emitter from "@/components/event-bus";

export default {
  name: 'ClassifyPost',
  data() {
    return {
      oneClicked: true,
      twoClicked: false,
      activeSection: 1,
    }
  },

  mounted() {
    console.log('ClassifyPost 组件已挂载，开始加载初始数据');

    // 只在这里加载一次数据
    setTimeout(()=>{
      emitter.emit("changeac",this.activeSection)
    },100)
    // 监听更多数据请求

  },

  methods: {
    // 切换分类
    switchSection(section) {
      if (section === 1 && !this.oneClicked) {
        this.oneClicked = true;
        this.twoClicked = false;
        this.activeSection = 1;
        this.resetAndLoad();
      } else if (section === 2 && !this.twoClicked) {
        this.twoClicked = true;
        this.oneClicked = false;
        this.activeSection = 2;
        this.resetAndLoad();
      }
    },
    getCurrentSection() {
      emitter.emit("select",this.activeSection)
    },
    // 重置状态并加载
    resetAndLoad() {
      if(this.title !== "cPost"){
        emitter.emit("changeac", this.activeSection)
      }else {
        this.getCurrentSection()
      }
    },
  },

  props:["title"]
}
</script>

<template>
  <div class="classifyBox">
    <div
        :class="{ clicked: oneClicked }"
        @click="switchSection(1)"
    >
      怪谈
    </div>
    <div
        :class="{ clicked: twoClicked }"
        @click="switchSection(2)"
    >
      悬案
    </div>
  </div>
</template>

<style scoped>
.classifyBox{
  width: 100%;
  height: 50px;
  background-color: rgba(0,0,0,0);
  position: relative;
  top:10px;
  display: flex;
}
.classifyBox div{
  width: 100px;
  height: 44px;
  margin:3px 10px 3px 10px;
  background-color: rgb(140,140,140);
  color: black;
  text-align: center;
  border-radius: 20px;
  border: 2px white solid;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor:pointer;
  transition: transform 0.5s;
}
.classifyBox div.clicked{
  background-color: greenyellow;
}
.classifyPost div:hover{
  transform: translateY(-5px);
}
</style>