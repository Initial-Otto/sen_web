<script>
import MainPage from "@/components/mainPage.vue";
import emitter from "@/components/event-bus";
export default {
  components: {
    MainPage // 注册组件
  },
  data(){
    return{
      first:true,
      mainPageKey:0,
      searchKeyword: '',
      searchNextId: 0,
      searchNoMore: false,
      searchIsLoading: false,
      allSearchPosts: [] // 存储所有搜索到的帖子
    }
  },
  methods:{
    async searching() {
      const keyword = this.searchKeyword;
      if (!keyword || !keyword.trim()) {
        alert('请输入搜索关键词');
        return;
      }

      this.searchKeyword = keyword.trim();
      this.searchNextId = 0;
      this.searchNoMore = false;
      this.allSearchPosts = [];
      this.first = false
      await this.performSearch(true);
    },
    async performSearch(isReset = false) {
      if (this.searchIsLoading) {
        return;
      }

      this.searchIsLoading = true;

      try {
        const params = {
          keyword: this.searchKeyword,
          limit: 10
        };

        if (!isReset && this.searchNextId) {
          params.last_id = this.searchNextId;
        }

        const response = await fetch('http://localhost:8000/src/php/search_posts.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(params)
        });

        const result = await response.json();

        if (result.success) {
          const posts = result.data.posts || [];
          this.searchNextId = result.data.pagination.next_last_id;
          this.searchNoMore = !result.data.pagination.has_more;
          console.log(posts)

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

          if (isReset) {
            this.allSearchPosts = formattedPosts;
            this.sendSearchData(formattedPosts, true);
          } else {
            this.allSearchPosts = [...this.allSearchPosts, ...formattedPosts];
            this.sendSearchData(formattedPosts, false);
          }
        } else {
          console.error('搜索失败:', result.message);
          this.searchNoMore = true;
        }
      } catch (error) {
        console.error('搜索请求失败:', error);
        this.searchNoMore = true;
      } finally {
        this.searchIsLoading = false;
      }
    },
    sendSearchData(posts, isReset = false) {
      if (posts.length === 0) {
        const eventData = {
          pagination: {
            has_more: this.searchNoMore,
            dont_to_load: true
          }
        };
        setTimeout(()=>{
          emitter.emit('search-data', eventData);
        },1000)
        return;
      }
      const eventData = {
        posts: posts,
        source: 'search',
        sectionId: -1,
        pagination: {
          has_more: !this.searchNoMore,
          next_id: this.searchNextId
        }
      };

      if (isReset) {
        setTimeout(()=>{
          emitter.emit('search-data', eventData);
        },100)
      } else {
        setTimeout(()=>{
          emitter.emit('more-search-data', eventData);
        },100)
      }
    },
    handleLoadMoreSearch() {
      if (!this.searchIsLoading && !this.searchNoMore && this.searchKeyword) {
        this.performSearch(false);
      } else {
        const eventData = {
          pagination: {
            has_more: true,
            dont_to_load: true
          }
        };
        emitter.emit('more-search-data', eventData);
      }
    },
    handleKeyPress(event) {
      if (event.key === 'Enter') {
        this.searching();
      }
    }
  },
  mounted() {
    this.$el.addEventListener('keypress', this.handleKeyPress);

    // 监听加载更多搜索数据的请求
    emitter.on('request-more-search', this.handleLoadMoreSearch);
  },
  beforeUnmount() {
    this.$el.removeEventListener('keypress', this.handleKeyPress);
    emitter.off('request-more-search', this.handleLoadMoreSearch);
  }
}
</script>

<template>
  <div v-if="first" class="first">
    <h1>谜森档案馆</h1>
    <div class="MainSearchBox">
      <input class="search" type="text" name="search" v-model="searchKeyword">
      <div class="searchButton" @click="searching" >🔍</div>
    </div>
  </div>
  <div v-else class="searchedBox">
    <input class="searched" type="text" name="search" v-model="searchKeyword">
    <div class="searchedButton" @click="searching" >🔍</div>
  </div>
  <MainPage v-if="!first" :key="mainPageKey" style="margin: 20px 0 0 0" title="searchP"/>

</template>

<style scoped>
.first{
  position: relative;
  margin: 0 auto;
  width: 80%;
  top: 40%
}
.first h1{
  text-align: center;
  font-size: 40px;
  color: white;
}
.MainSearchBox, .searchedBox{
  width: 80%;
  position: relative;
  margin: 0 auto;
  background-color: rgb(140,140,140);
}
.MainSearchBox{
  height: 60px;
  border-radius: 30px;
}
.searchedBox{
  height: 40px;
  top: 10px;
  border-radius: 20px;
  display: flex;
}
.MainSearchBox input, .searchedBox input{
  width: 80%;
  background-color: rgba(0,0,0,0);
  border: 0;
  outline: none;
}
.MainSearchBox input{
  height: 60px;
  margin: 0 0 0 20px;
  font-size: 30px;
}
.searchedBox input{
  height: 40px;
  margin: 0 0 0 15px;
  font-size: 20px;
}
.searchedButton, .searchButton{
  position: absolute;
  right: 5px;
  top:5px;
  background-color: rgba(0,0,0,0);
  border:2px black solid;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor:pointer;
  transition: background-color 0.3s ease, color 0.3s ease;
}
.searchButton{
  width: 70px;
  height: 50px;
  border-radius: 25px;
}
.searchedButton{
  width: 50px;
  height: 30px;
  border-radius: 15px;
}
.searchButton:hover, .searchedButton:hover{
  background-color: rgb(90,90,90);
}
</style>