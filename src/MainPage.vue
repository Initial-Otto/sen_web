<script setup>
import TopBar from "@/components/topBar.vue";
import SearchPage from "@/components/searchPage.vue";
import MainPageContent from "@/components/mainPage.vue"; // 重命名组件
import ClassifyPost from "@/components/classifyPost.vue";
import { onMounted, onUnmounted, ref } from "vue";
import emitter from "@/components/event-bus";

const currentPage = ref('main')
const isLoading = ref(false)
const noMore = ref(false)
const activeSection = ref(0)
const nextId = ref(0)
const piposts = ref([])
const posts = ref([])

const switchPage = (pageName) => {
  currentPage.value = pageName
}
// 处理更多数据请求
const handleRequestMorePosts = ()=> {
  console.log('收到更多数据请求');
  console.log(isLoading.value)
  console.log(noMore.value)
  if (!isLoading.value && !noMore.value) {
    loadPosts(false);
  }else{
    const eventData = {
      pagination: {
        has_more: true,
        dont_to_load: true
      }
    };
    emitter.emit('more-posts-data', eventData);
    console.log('没有数据需要发送')
  }
}

// 加载帖子
const loadPosts=async (isReset = false,at = activeSection.value) => {
    // 防止重复加载
    if (isLoading.value) {
      console.log('正在加载中，跳过');
      return;
    }
    activeSection.value=at
    isLoading.value = true;

    try {
      // 准备请求参数
      const params = {
        section_id: at,
      };

      // 如果不是重置且有下一页，添加last_id
      if (!isReset && nextId.value) {
        params.last_id = nextId.value;
      }
      console.log(params.last_id ?? "无")


      // 发送请求
      const presponse = await fetch('http://localhost:8000/src/php/get_pinned_posts.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(params)
      });
      params.limit = 10
      const response = await fetch('http://localhost:8000/src/php/get_normal_posts.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(params)
      });

      const result = await response.json();
      const presult = await presponse.json();
      if (presult.success) {
        piposts.value = presult.data.posts || [];
        sendToMainPage(piposts.value, isReset)
      }
      if (result.success) {
        posts.value = result.data.posts || [];
        nextId.value = result.data.pagination.next_last_id;
        noMore.value = !result.data.pagination.has_more;

        // 发送到mainPage
        setTimeout(()=>{
          sendToMainPage(posts.value, false);
        },100)
      } else {
        console.error('获取数据失败:', result.message);
        noMore.value = false;
      }
    } catch (error) {
      console.error('请求失败:', error);
      noMore.value = true;
    } finally {
      isLoading.value = false;
    }
}

// 发送数据到mainPage
const sendToMainPage=(posts, isReset = false)=> {
  if (posts.length === 0) {
    const eventData = {
      pagination: {
        has_more: noMore.value,
        dont_to_load: true
      }
    };
    emitter.emit('more-posts-data', eventData);
    console.log('没有数据需要发送')
    return
  }

  // 转换数据格式
  const formattedPosts = posts.map(post => ({
    id: String(post.post_id),
    img: post.image_path || '/img/back.jpeg',
    width: post.width || 200,
    height: post.height || 150,
    avatar_path: post.avatar_path || '/img/default-avatar.png',
    UPName: post.username || '匿名用户',
    title: post.title || '无标题',
    text: post.content_text ?? '暂无内容',
    level: post.level || 0
  }));

  console.log(`发送 ${formattedPosts.length} 条数据到 mainPage`);

  // 添加分页信息
  const eventData = {
    posts: formattedPosts,
    source: 'classify',
    sectionId: activeSection.value,
    pagination: {
      has_more: !noMore.value,
      next_id: nextId.value
    }
  };

  // 发送事件
  if (isReset) {
    emitter.emit('post-data', eventData);
  } else {
    emitter.emit('more-posts-data', eventData);
  }
}



onMounted(() => {
  console.log('根组件挂载')

  emitter.on('mainPage', () => {
    console.log('切换到主页')
    switchPage('main')
  })

  emitter.on('searchPage', () => {
    console.log('切换到搜索页')
    switchPage('search')
  })

  // 记录ClassifyPost实例
  emitter.on('classify-mounted', () => {
    console.log('ClassifyPost实例已记录')
  })
  emitter.on('request-more-posts', handleRequestMorePosts);
  emitter.on('changeac',(ac)=>{
    activeSection.value=ac
    console.log(ac)
    loadPosts(true,ac)
  })

  // 立即滚动到顶部
  window.scrollTo(0, 0)
  setTimeout(() => {
    window.scrollTo(0, 0)
  }, 10)
})

onUnmounted(() => {
  console.log('根组件卸载')

  emitter.off('mainPage')
  emitter.off('searchPage')
  emitter.off('classify-mounted')

  // 清理ClassifyPost实例

})
</script>

<template>
  <div v-show="currentPage !== 'selfPage'">
    <TopBar />
  </div>

  <!-- 主页 -->
  <div v-show="currentPage === 'main'">
    <ClassifyPost />
    <MainPageContent title="main"/>
  </div>

  <!-- 搜索页 -->
  <template v-if="currentPage === 'search'">
    <SearchPage />

  </template>
  <!-- 加载状态 -->
  <div v-if="isLoading" class="loading-indicator">
    加载中...
  </div>

  <div v-if="noMore" class="no-more">
    没有更多内容了
  </div>

</template>

<style scoped>
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