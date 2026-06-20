<template>
  <div class="not-found">
    <div class="code">404</div>
    <div class="message">页面不存在</div>
    <div class="description">您访问的页面不存在或已被移除</div>
    <el-button type="primary" size="large" @click="goHome">
      <el-icon><HomeFilled /></el-icon>返回首页
    </el-button>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { HomeFilled } from '@element-plus/icons-vue'

const router = useRouter()

const goHome = () => {
  router.push('/')
}
</script>

<style scoped>
.not-found {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f5f7fa;
}

.code {
  font-size: 120px;
  font-weight: bold;
  color: #409eff;
  line-height: 1;
  margin-bottom: 16px;
}

.message {
  font-size: 24px;
  color: #303133;
  margin-bottom: 8px;
}

.description {
  font-size: 14px;
  color: #909399;
  margin-bottom: 32px;
}
</style>
