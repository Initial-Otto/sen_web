<script setup>
import MainPage from "@/components/mainPage.vue";
import {onMounted, ref} from "vue";
import emitter from "@/components/event-bus";
const myPosts = ref([])
const isLoading = ref(false)
const hasMore = ref(true)
const nextId = ref(0)
const fetchUserPosts = async () => {
  if (isLoading.value) return

  isLoading.value = true
  try {
    const response = await fetch('http://localhost:8000/src/php/get_user_posts.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include'
    })

    const result = await response.json()

    if (result.success) {
      myPosts.value = result.posts || []
      hasMore.value = result.has_more || false
      nextId.value = result.next_id || 0

      // 发送数据到mainpage
      sendUserPostsToMainPage(true)
    } else {
      console.error('获取用户帖子失败:', result.message)
      hasMore.value = false
    }
  } catch (error) {
    console.error('请求用户帖子失败:', error)
    hasMore.value = false
  } finally {
    isLoading.value = false
  }
}
const sendUserPostsToMainPage = () => {
  if (myPosts.value.length === 0) {
    const eventData = {
      pagination: {
        has_more: hasMore.value,
        dont_to_load: true
      }
    };
    setTimeout(() => {
      emitter.emit('user-posts-data', eventData);
    }, 100)
    return;
  }

  const formattedPosts = myPosts.value.map(post => ({
    id: String(post.post_id),
    img: post.image_path || '/img/back.jpeg',
    width: post.width || 200,
    height: post.height || 150,
    avatar_path: post.avatar_path || '/img/default-avatar.png',
    UPName: post.username || '匿名用户',
    title: post.title || '无标题',
    text: post.content_text ?? '暂无内容',
    level: post.level || 0
  }))

  const eventData = {
    posts: formattedPosts,
    source: 'userPosts',
    sectionId: -2, // 使用特殊ID表示用户帖子
    userId: formattedPosts.user_id, // 从帖子数据中获取用户ID
    pagination: {
      has_more: hasMore.value,
      next_id: nextId.value
    }
  }
  console.log(eventData)
  console.log("::::::::::::::::::::::")

  setTimeout(() => {
    emitter.emit('user-posts-data', eventData);
  }, 100)

}
onMounted(() => {
  // 获取用户帖子数据
  fetchUserPosts()

})

</script>

<template>
<div class="myPost">
  <MainPage title="userPosts"/>
</div>
</template>

<style scoped>
</style>