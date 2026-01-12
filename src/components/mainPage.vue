<template>
  <main :key="state.key">
    <div class="cards-container">
      <div
          v-for="(item,index) in state.cardList"
          :key="item.id" class="text_menu"
          :style="getCardStyle(index)"
          @click="openPost(item)"
          :ref="setCardRef"
      >
        <div class="text_menu"
           :style="{
              width:`${state.carPos[index].width}px`,
              height:`${state.carPos[index].height}px`
           }"
        >
          <img :src="item.img" class="the_image">
          <div class="under_tm">
            <div class="under_c">

            </div>
            <div class="menu_line">
              <div class="up_name">{{ item.UPName }}</div>
            </div>
            <div class="menu_pf">
              <img :src="item.avatar_path" alt="头像">
            </div>
            <p class="in_text">
              <span class="title">{{ item.title }}</span><br />{{item.text}}
            </p>
          </div>
        </div>
      </div>

      <button @click="openCreatePost">+</button>

      <!-- POSTPage组件 -->
      <POSTPage/>
      <CreatPost/>
    </div>
  </main>
</template>
<script setup>
import {computed, onBeforeUnmount, onMounted, reactive, ref, nextTick, watch, defineProps} from "vue";
import POSTPage from "./openPOST.vue"
import CreatPost from './createPost.vue'
import emitter from './event-bus.js'


const state=reactive({
  carWidth:0,
  column:5,
  gap:20,
  currentPage:1,
  cardList:[],
  carPos:[],
  columnHeight:[0,0,0,0,0],
  key:0
})
const isLoading = ref(false)
const hasMore = ref(true)
const pageSize = 10
const cardRefs = ref([])
const lastCardRef = ref(null)
const currentObserver = ref(null)
const allPosts = ref([])
const currentSectionId = ref(0)
const needMoreFromClassify = ref(false)
const donot_to_load = ref(false)

const props = defineProps({
  title:{
    type:String,
    default:'main'
  }
})

// 创建观察者
const setCardRef = (el) => {
  if (el) {
    if (!cardRefs.value.includes(el)) {
      cardRefs.value.push(el)
    }
  }
}
watch(() => state.cardList.length, async (newLength) => {
  if (newLength === 0) return

  await nextTick()

  if (cardRefs.value.length > 0) {
    lastCardRef.value = cardRefs.value[cardRefs.value.length - 1]
  }

  createObserverForLastCard()
})
const createObserverForLastCard = () => {

  if (currentObserver.value) {
    currentObserver.value.disconnect()
    currentObserver.value = null
  }

  if (!hasMore.value || isLoading.value || !lastCardRef.value) {
    return
  }
  currentObserver.value= new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      debouncedObserverCallback(entry)
    })
  }, {
    root: null,
    rootMargin: '1px 0px 1px 0px',
    threshold: [0, 0.1]
  })

  try {
    currentObserver.value.observe(lastCardRef.value)
  } catch (error) {
    console.error('观察最后一个卡片失败:', error)
  }
}
const debounce = (func, wait) => {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}
const debouncedObserverCallback = debounce((entry) => {
  if (entry.isIntersecting && !isLoading.value && hasMore.value) {
    loadMoreData()
    console.log("已触发观察者aaaaaaaaaaa")
  }
}, 100)
const isRequesting = ref(false)

// 修改 loadMoreData 函数
const loadMoreData = async () => {
  console.log(donot_to_load.value)
  if (isLoading.value || isRequesting.value) {
    return
  }

  if (!hasMore.value) {

    return
  }
  if(donot_to_load.value && state.cardList.length>=allPosts.value.length){
    return
  }


  isRequesting.value = true
  isLoading.value = true

  try {
    // 1. 首先尝试从本地 allPosts 中加载
    const startIndex = state.cardList.length
    const endIndex = startIndex + pageSize

    if (startIndex < allPosts.value.length) {
      // 本地还有数据
      const newData = allPosts.value.slice(startIndex, Math.min(endIndex, allPosts.value.length))

      // 处理新数据
      for (let i = 0; i < newData.length; i++) {
        const globalIndex = startIndex + i
        if (!state.carPos[globalIndex]) {
          state.carPos[globalIndex] = { width: 200, height: 200, x: 0, y: 0 }
        }
      }

      state.cardList.push(...newData)
      await nextTick()
      computedCarPos(newData, startIndex)

      // 检查本地是否还有数据
      const remainingLocal = allPosts.value.length - state.cardList.length
      if (remainingLocal <= 0 && needMoreFromClassify.value) {
        if(props.title === 'main'){
          console.log("main------------")
          emitter.emit('request-more-posts')
        }else if(props.title === 'searchP'){
          emitter.emit('request-more-search')
        }
      } else if (remainingLocal <= 0) {
        hasMore.value = false
        console.log('没有更多数据了')
      }

    } else if (needMoreFromClassify.value) {
      // 本地没有数据，需要从 ClassifyPost 获取
      if(props.title === 'main') {
        emitter.emit('request-more-posts')
      }else if(props.title === 'searchP'){
        emitter.emit('request-more-search')
      }

      // 设置一个超时，防止请求卡住
      setTimeout(() => {
        if (isLoading.value) {
          console.log('请求超时，重置状态')
          isLoading.value = false
          isRequesting.value = false
        }
      }, 5000) // 5秒超时

    } else {
      hasMore.value = false
      console.log('没有更多数据了')
    }

  } catch (error) {
    console.error('加载数据失败:', error)
    hasMore.value = false
  } finally {
    // 延迟重置状态，避免过快触发下一次加载
    setTimeout(() => {
      isLoading.value = false
      isRequesting.value = false
      console.log(cardRefs.value.length)
      // 重新创建观察者
      if (cardRefs.value.length > 0) {
        lastCardRef.value = cardRefs.value[cardRefs.value.length - 1]
        createObserverForLastCard()
        console.log("已创建")
      }
    }, 100)
  }
}

const fetchMorePosts = async (page, size) => {
  return new Promise((resolve) => {
    // 如果没有数据，返回空数组
    if (!allPosts.value || allPosts.value.length === 0) {
      console.log('没有可用的帖子数据')
      resolve([])
      return
    }

    // 计算分页
    const startIndex = (page - 1) * size
    const endIndex = startIndex + size

    // 确保不超出范围
    if (startIndex >= allPosts.value.length) {
      resolve([])
      return
    }

    const pageData = allPosts.value.slice(startIndex, Math.min(endIndex, allPosts.value.length))

    // 标准化数据格式
    const normalizedData = pageData.map((post, index) => ({
      id: post.id || `post_${Date.now()}_${index}`,
      img: post.img || '/img/back.jpeg',
      width: post.width || 200,
      height: post.height || 150,
      UPName: post.UPName || '匿名用户',
      title: post.title || '无标题',
      text: post.text || '暂无内容',
      avatar_path: post.avatar_path || '/img/default-avatar.png',
      level: post.level || 0
    }))

    console.log(`加载第${page}页，获取到${normalizedData.length}条数据`)
    resolve(normalizedData)
  })
}

const getData = async () => {
  try {
    isLoading.value = true
    emitter.emit('loading')

    // 使用 fetchMorePosts 获取第一页数据
    const data = await fetchMorePosts(1, pageSize)

    if (data && data.length > 0) {
      // 重置状态
      state.cardList = []
      state.carPos = []
      state.columnHeight = [0, 0, 0, 0, 0]

      // 初始化位置数组
      data.forEach((_, index) => {
        state.carPos[index] = { width: 200, height: 200, x: 0, y: 0 }
      })

      state.cardList = data
      state.currentPage = 2

      await nextTick()
      computedCarPos(data, 0)
      const remainingData = allPosts.value.length - state.cardList.length
      needMoreFromClassify.value = remainingData <= 0
      console.log(`初始化后，剩余数据: ${remainingData}, 需要从classify获取: ${needMoreFromClassify.value}`)
    }
  } catch (error) {
    console.error('初始化数据失败:', error)
  } finally {
    isLoading.value = false
    emitter.emit('load')
  }
}
const receivePostData = (data) => {
  if (!data || !data.posts || !Array.isArray(data.posts)) {
    return
  }
  console.log("已切换")

  allPosts.value = []
  state.cardList = []
  state.carPos = []
  state.columnHeight = [0, 0, 0, 0, 0]
  state.currentPage = 1
  donot_to_load.value=false
  currentSectionId.value = data.sectionId

  // 存储所有数据
  allPosts.value = data.posts

  const totalPosts = allPosts.value.length
  const displayedPosts = Math.min(pageSize, totalPosts)
  needMoreFromClassify.value = displayedPosts < totalPosts
  console.log(`接收数据后，总数: ${totalPosts}, 显示: ${displayedPosts}, 需要更多: ${needMoreFromClassify.value}`)
  hasMore.value = needMoreFromClassify.value || totalPosts > 0
  // 加载第一页数据
  setTimeout(() => {
    getData()
  }, 100)
}
const handleMorePostsReceived = (data) => {
  if (!data || !data.posts || !Array.isArray(data.posts)) {
    console.warn('接收到的更多数据格式不正确')
    if(data.pagination.has_more && data.pagination.dont_to_load){
      donot_to_load.value=true
      console.log("donot_to_load已改变"+donot_to_load.value)
    }
    console.log("donot_to_load")
    return
  }

  if(donot_to_load.value){
    return;
  }
  console.log(data)
  console.log(`接收到更多数据，共${data.posts.length}条`)

  // 追加数据到allPosts（去重）
  const newPosts = data.posts.filter(newPost => {
    // 获取新帖子的ID，尝试多种可能的字段名
    const newPostId = newPost.id || newPost.postId || newPost.post_id

    // 检查是否已存在相同ID的帖子
    const isExisting = allPosts.value.some(existingPost => {
      const existingPostId = existingPost.id || existingPost.postId || existingPost.post_id
      return existingPostId && newPostId && String(existingPostId) === String(newPostId)
    })
    if (isExisting) {
      console.log("发现重复帖子，跳过:", newPostId)
      console.log()
    }

    return !isExisting
  })
  console.log(allPosts)

  allPosts.value = [...allPosts.value, ...newPosts]
  console.log(`追加后总数: ${allPosts.value.length}`)

  // 更新是否需要更多数据的状态
  const remaining = allPosts.value.length - state.cardList.length
  needMoreFromClassify.value = remaining > 0

  if(!data.pagination.has_more){
    donot_to_load.value=true
  }
}

const getCardStyle = (index) => {
  if (!state.carPos[index] || !state.cardList[index]) {
    return {
      width: '200px',
      height: '200px',
      transform: 'translate(0px, 0px)',
      display: 'none' // 隐藏未初始化的元素
    }
  }
  const pos = state.carPos[index] || { width: 200, height: 200, x: 0, y: 0 }
  return {
    width: `${pos.width}px`,
    height: `${pos.height}px`,
    transform: `translate(${pos.x}px, ${pos.y}px)`
  }
}

const openPost=(item)=>{
  emitter.emit('open-post-event',item)
  emitter.on('mainPage',closePost)
  emitter.on('searchPage',closePost)
}
const closePost = () => {
  emitter.emit('close-post-event')
  emitter.off('mainPage',closePost)
  emitter.off('searchPage',closePost)
}
const openCreatePost=()=>{
  emitter.emit('open-createPost-event')
  emitter.on('mainPage',closeCreatePost)
  emitter.on('searchPage',closeCreatePost)
}
const closeCreatePost = () => {
  emitter.emit('close-createPost-event')
  emitter.off('mainPage',closeCreatePost)
  emitter.off('searchPage',closeCreatePost)
}


const computedCarPos=(newList = null, startIndex = 0)=>{
  const listToCompute = newList || state.cardList

  if (!state.carWidth || state.carWidth <= 0) {
    setTimeout(() => computedCarPos(newList, startIndex), 100)
    return
  }
  listToCompute.forEach((item,index)=>{
    const globalIndex = startIndex + index

    if (!item) {
      console.warn(`Item at index ${globalIndex} is undefined`)
      state.carPos[globalIndex] = {
        width: state.carWidth || 200,
        height: 200,
        x: 0,
        y: 0
      }
      return
    }
    const itemWidth = item.width || 200
    const itemHeight = item.height || 200
    const cardHeight = (itemHeight * state.carWidth) / itemWidth

    // 确保 carWidth 已计算
    if (!state.carWidth || state.carWidth <= 0) {
      console.warn('carWidth not calculated yet, using default')
      state.carPos[globalIndex] = {
        width: 200,
        height: 200,
        x: 0,
        y: 0
      }
      return
    }
    if (globalIndex < state.column) {
      state.carPos[globalIndex] = {
        width: state.carWidth,
        height: cardHeight + 80,
        x: globalIndex * (state.carWidth + state.gap) + 15,
        y: 10
      }
      state.columnHeight[globalIndex] = cardHeight + state.gap + 10
    } else {
      const { minIndex, minHeight } = minColumn.value
      if (minIndex >= 0 && minIndex < state.columnHeight.length) {
        state.carPos[globalIndex] = {
          width: state.carWidth,
          height: cardHeight + 80,
          x: minIndex * (state.carWidth + state.gap) + 15,
          y: minHeight
        }
        state.columnHeight[minIndex] += cardHeight + state.gap + 80
      } else {
        // 安全的后备方案
        state.carPos[globalIndex] = {
          width: state.carWidth,
          height: cardHeight + 80,
          x: 15,
          y: state.columnHeight[0] || 10
        }
        state.columnHeight[0] += cardHeight + state.gap + 80
      }
    }
  })
}

const minColumn = computed(()=>{
  let minIndex = 0
  let minHeight = state.columnHeight[0] || 0

  state.columnHeight.forEach((item, index) => {
    if (item < minHeight) {
      minHeight = item
      minIndex = index
    }
  })
  return{
    minIndex,
    minHeight: minHeight + 80
  }
})

const init = async (num) => {
  // 确保窗口尺寸已获取
  const containerWidth = window.innerWidth
  state.carWidth = (containerWidth - state.gap * (state.column - 1)) / state.column - 10

  if (num === 0) {
    // 首次加载，如果有初始数据就加载，否则等待外部数据
    if (allPosts.value && allPosts.value.length > 0) {
      await getData()
    } else {
      console.log('等待外部数据...')
    }
  } else {
    // 窗口大小变化，重新计算位置
    state.carPos = []
    state.columnHeight = [0, 0, 0, 0, 0]
    computedCarPos(state.cardList)
    createObserverForLastCard()
  }
}

let eventListenersAdded = false
onMounted(async ()=>{

  if (!eventListenersAdded) {
    if(props.title === 'main') {
      emitter.on('post-data', receivePostData)
    }else if(props.title === 'searchP'){
      emitter.on('search-data', receivePostData)
    }else if(props.title === 'userPosts'){
      console.log("我是~~~~~~~~~~~~~~~~"+props.title)
      emitter.on('user-posts-data',receivePostData)
    }


    if(props.title === 'main') {
      emitter.on('more-posts-data', handleMorePostsReceived)
    }else if(props.title === 'searchP'){
      emitter.on('more-search-data', handleMorePostsReceived)
    }
    eventListenersAdded = true
    console.log('mainPage事件监听器已添加')
  }
  await init(0)

  window.addEventListener('resize', handleResize)
})
const handleResize=()=>{
  init(1)
}
onBeforeUnmount(() => {
  if (eventListenersAdded) {
    emitter.off('post-data', receivePostData)
    emitter.off('more-posts-data', handleMorePostsReceived)
    eventListenersAdded = false
    console.log('mainPage事件监听器已移除')
  }
  if (currentObserver.value) {
    currentObserver.value.disconnect()
    currentObserver.value = null
  }

  window.removeEventListener('resize', handleResize)
});
</script>

<style scoped>
/* 内容显示整体框架*/
main {
  margin: 0 auto;
  position: relative;
  top: 10px;
  width: 100%;
  height: 100%;
  left: 0;
  box-sizing: border-box;

}
/*快捷内容显示盒*/
.text_menu{
  margin: 0 0 10px 0;
  break-inside: avoid;
  position:absolute;
  top:0;
  left: 0;
  border-radius:20px 20px 5px 20px;
  background-color:rgb(40,40,40);
  min-height:120px;
  z-index:1;
  transition:transform 0.5s;
  overflow:hidden;
}
/*快捷盒图片*/
.the_image{
  position: relative;
  margin: 0 0 80px 0;
  z-index: 2;
  width:100%;
  object-fit: contain;
}
.text_menu:hover{
  transform:translateY(-5px);
  box-shadow:0 10px 20px rgba(50,50,50,0.2)
}
.under_tm {
  position: absolute;
  bottom: 0;
  border-radius: 0 0 5px 20px;
  background-color:rgb(30,30,30);
  width:100%;
  height:80px;
  z-index: 2;
}
.under_c {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background-color: rgb(30,30,30);
  position: absolute;
  top: -25px;
  left: 5px;
  overflow: hidden;

}
.under_c img{
  width: 100%;
}
.up_name {
  position: absolute;
  bottom: 2px;
  left: 1px;
  font-size: 10pt;
  color: rgb(50,50,50);
  background-color: rgb(30,30,30);
}
.menu_line{
  height:1px;
  width:140px;
  background-color:rgb(50,50,50);
  position:absolute;
  top:20px;
  left:55px;
}
.in_text {
  background-color: rgb(30,30,30);
  height: 52px;
  width: 90%;
  position: absolute;
  bottom: 6px;
  left: 5%;
  margin: 0;
  color: gray;
  font-size: 8pt;
  overflow: hidden;
  white-space: normal;
  text-overflow: ellipsis;
}
.menu_pf{
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: rgb(50,50,50);
  position: absolute;
  top:-20px;
  left:10px;
  overflow: hidden;
  transition:transform 0.5s;
}
.menu_pf img{
  width: 100%;
}
.menu_pf:hover {
  transform: scale(1.2)
}
.title{
  font-size:13pt;
  color:white;
}


button{
  display: flow;
  position: fixed;
  bottom: 15%;
  right: 5%;
  border-radius:50%;
  background: white;
  width: 50px;height: 50px;
  font-size: 50px;
  display: flex;
  justify-content: center; /* 水平居中 */
  align-items: center; /* 垂直居中 */
  z-index: 9;
  /* 可选：增强一下视觉效果 */
  border: none; /* 移除默认边框 */
  cursor: pointer; /* 鼠标悬停时显示手指形状 */
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* 添加一点阴影，显得立体 */
  transition: transform 0.5s
}
button:hover{
  transform:translateY(-5px);
  box-shadow:0 10px 20px rgba(50,50,50,0.2)
}

.cards-container {
  position: relative;
  min-height: 100vh;
  width: 100%;
}
</style>