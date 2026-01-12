const { defineConfig } = require('@vue/cli-service')
module.exports = defineConfig({
  transpileDependencies: true,
  devServer: {
    port: 8081, // 确保端口与你的开发服务器一致
    proxy: {
      // 代理所有以 /php 开头的请求
      '/php': {
        target: 'http://localhost', // 你的PHP服务器地址（通常是80端口）
        changeOrigin: true, // 允许跨域
        secure: false, // 如果是https需要设置为true
        pathRewrite: {
          '^/php': '/src/php' // 将 /php 重写为 /src/php
        }
      }
    }
  }
})

