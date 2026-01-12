<template>
  <div class="admin-container">
    <header>
      <h1>帖子管理系统</h1>
      <p>管理帖子审核与置顶</p>
    </header>

    <div class="controls">
      <button
          @click="setFilter('all')"
          :class="{ active: currentFilter === 'all' }"
      >
        所有帖子（正常）
      </button>
      <button
          @click="setFilter('pending')"
          :class="{ active: currentFilter === 'pending' }"
      >
        待审核
      </button>
      <button
          @click="setFilter('top')"
          :class="{ active: currentFilter === 'top' }"
      >
        已置顶
      </button>
    </div>

    <div class="posts-container">
      <div v-if="loading" class="loading">加载中...</div>
      <div v-else-if="posts.length === 0" class="empty">暂无帖子</div>

      <div v-else>
        <div v-for="post in posts" :key="post.post_id" class="post">
          <div class="post-header">
            <div class="post-title">{{ post.title }}</div>
            <div class="post-meta">
              <span :class="'status status-' + getStatusClass(post.status)">
                {{ getStatusText(post.status) }}
              </span>
              <span>ID: {{ post.post_id }} | 作者: {{ post.username }} | 时间: {{ formatDate(post.created_at) }}</span>
            </div>
          </div>
          <div class="post-content">{{ post.content_text || '无内容' }}</div>
          <div class="post-actions">
            <!-- 待审核页面：显示通过和拒绝按钮 -->
            <template v-if="currentFilter === 'pending'">
              <button @click="approvePost(post.post_id)">通过审核</button>
              <button @click="rejectPost(post.post_id)">拒绝</button>
            </template>

            <!-- 已置顶页面：显示取消置顶按钮 -->
            <template v-else-if="currentFilter === 'top'">
              <button @click="setNormalPost(post.post_id)">取消置顶</button>
            </template>

            <!-- 所有帖子（正常）页面：显示置顶按钮 -->
            <template v-else-if="currentFilter === 'all'">
              <button @click="setTopPost(post.post_id)">置顶</button>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminPosts',
  data() {
    return {
      currentFilter: 'all',
      posts: [],
      loading: false
    }
  },
  mounted() {
    this.loadPosts();
  },
  methods: {
    setFilter(filter) {
      this.currentFilter = filter;
      this.loadPosts();
    },

    async loadPosts() {
      this.loading = true;
      try {
        const response = await fetch(`http://localhost:8000/src/php/admin_posts.php?action=get_posts&filter=${this.currentFilter}`);
        const result = await response.json();

        if (result.success) {
          this.posts = result.posts || [];
        } else {
          console.error('加载失败:', result.message);
          alert('加载失败: ' + result.message);
        }
      } catch (error) {
        console.error('网络错误:', error);
        alert('网络错误，请稍后重试');
      } finally {
        this.loading = false;
      }
    },

    getStatusText(status) {
      const statusMap = {
        1: '已置顶',
        2: '正常',
        4: '待审核'
      };
      return statusMap[status] || '未知';
    },

    getStatusClass(status) {
      const classMap = {
        1: 'top',
        2: 'normal',
        4: 'pending'
      };
      return classMap[status] || 'unknown';
    },

    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleString('zh-CN');
    },

    async approvePost(postId) {
      if (!confirm('确定要通过此帖子的审核吗？')) return;

      try {
        const response = await fetch(`http://localhost:8000/src/php/admin_posts.php?action=approve_post&post_id=${postId}`);
        const result = await response.json();

        if (result.success) {
          alert('帖子审核通过成功');
          this.loadPosts();
        } else {
          alert('操作失败: ' + result.message);
        }
      } catch (error) {
        alert('网络错误，请稍后重试');
        console.error('审核帖子失败:', error);
      }
    },

    async rejectPost(postId) {
      if (!confirm('确定要拒绝此帖子吗？帖子将被删除。')) return;

      try {
        const response = await fetch(`http://localhost:8000/src/php/admin_posts.php?action=reject_post&post_id=${postId}`);
        const result = await response.json();

        if (result.success) {
          alert('帖子已拒绝并删除');
          this.loadPosts();
        } else {
          alert('操作失败: ' + result.message);
        }
      } catch (error) {
        alert('网络错误，请稍后重试');
        console.error('拒绝帖子失败:', error);
      }
    },

    async setTopPost(postId) {
      if (!confirm('确定要置顶此帖子吗？')) return;

      try {
        const response = await fetch(`http://localhost:8000/src/php/admin_posts.php?action=set_top&post_id=${postId}`);
        const result = await response.json();

        if (result.success) {
          alert('帖子置顶成功');
          this.loadPosts();
        } else {
          alert('操作失败: ' + result.message);
        }
      } catch (error) {
        alert('网络错误，请稍后重试');
        console.error('置顶操作失败:', error);
      }
    },

    async setNormalPost(postId) {
      if (!confirm('确定要取消置顶此帖子吗？')) return;

      try {
        const response = await fetch(`http://localhost:8000/src/php/admin_posts.php?action=set_normal&post_id=${postId}`);
        const result = await response.json();

        if (result.success) {
          alert('帖子取消置顶成功');
          this.loadPosts();
        } else {
          alert('操作失败: ' + result.message);
        }
      } catch (error) {
        alert('网络错误，请稍后重试');
        console.error('取消置顶操作失败:', error);
      }
    }
  }
}
</script>

<style scoped>
.admin-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
  background-color: #121212;
  color: #e0e0e0;
  font-family: Arial, sans-serif;
}

header {
  text-align: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #333;
}

h1 {
  color: #4a9cff;
  margin-bottom: 10px;
}

.controls {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

button {
  background-color: #2a2a2a;
  color: #e0e0e0;
  border: 1px solid #444;
  padding: 8px 15px;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s;
  font-size: 14px;
}

button:hover {
  background-color: #3a3a3a;
}

button.active {
  background-color: #4a9cff;
  color: #121212;
}

.post {
  background-color: #1e1e1e;
  border: 1px solid #333;
  border-radius: 4px;
  padding: 15px;
  margin-bottom: 15px;
}

.post-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  flex-wrap: wrap;
  gap: 10px;
}

.post-title {
  font-size: 1.1em;
  color: #4a9cff;
  font-weight: bold;
}

.post-meta {
  color: #888;
  font-size: 0.9em;
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.post-content {
  margin-bottom: 10px;
  color: #ccc;
  max-height: 100px;
  overflow: hidden;
  line-height: 1.4;
}

.post-actions {
  display: flex;
  gap: 10px;
  margin-top: 10px;
}

.status {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 3px;
  font-size: 0.8em;
  font-weight: bold;
}

.status-pending {
  background-color: #ffa500;
  color: #000;
}

.status-normal {
  background-color: #4CAF50;
  color: #fff;
}

.status-top {
  background-color: #f44336;
  color: #fff;
}

.loading, .empty {
  text-align: center;
  padding: 20px;
  color: #888;
}
</style>
