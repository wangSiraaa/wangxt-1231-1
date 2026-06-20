<template>
  <div class="page-container">
    <div class="page-header">
      <h2 class="page-title">运营概览</h2>
      <el-button type="primary" @click="loadData">
        <el-icon><Refresh /></el-icon>刷新数据
      </el-button>
    </div>

    <el-row :gutter="16" class="mb-16">
      <el-col :xs="12" :sm="12" :md="6" v-for="stat in stats" :key="stat.key">
        <div class="stat-card">
          <div class="flex-between">
            <div>
              <div class="label">{{ stat.label }}</div>
              <div class="value" :class="stat.color">{{ stat.value }}</div>
            </div>
            <el-icon :size="40" :color="stat.iconColor"><component :is="stat.icon" /></el-icon>
          </div>
          <div v-if="stat.trend" class="trend" :class="stat.trend > 0 ? 'text-success' : 'text-danger'">
            <el-icon v-if="stat.trend > 0"><Top /></el-icon>
            <el-icon v-else><Bottom /></el-icon>
            {{ Math.abs(stat.trend) }}% 较上月
          </div>
        </div>
      </el-col>
    </el-row>

    <el-row :gutter="16">
      <el-col :md="16">
        <div class="detail-card">
          <div class="detail-section-title">近期动态</div>
          <el-table :data="activities" style="width: 100%" v-loading="loading">
            <el-table-column prop="type" label="类型" width="100">
              <template #default="{ row }">
                <el-tag :type="row.tagType" size="small">{{ row.type }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="title" label="内容" />
            <el-table-column prop="time" label="时间" width="160">
              <template #default="{ row }">{{ formatDateTime(row.time) }}</template>
            </el-table-column>
            <el-table-column label="操作" width="80">
              <template #default="{ row }">
                <el-button type="primary" link @click="goToDetail(row)">查看</el-button>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </el-col>

      <el-col :md="8">
        <div class="detail-card">
          <div class="detail-section-title">待处理事项</div>
          <div class="todo-list">
            <div 
              v-for="item in todoList" 
              :key="item.key" 
              class="todo-item"
              @click="goToTodo(item)"
            >
              <div class="todo-icon" :style="{ background: item.color }">
                <el-icon :size="20" color="#fff"><component :is="item.icon" /></el-icon>
              </div>
              <div class="todo-content">
                <div class="todo-title">{{ item.label }}</div>
                <div class="todo-count">待处理: <span class="text-danger">{{ item.count }}</span></div>
              </div>
              <el-icon><ArrowRight /></el-icon>
            </div>
          </div>
        </div>

        <div class="detail-card mt-16">
          <div class="detail-section-title">业务规则提醒</div>
          <div class="rule-list">
            <div class="rule-item">
              <el-icon color="#e6a23c"><Warning /></el-icon>
              <span>欠费超过阈值的住户仅允许登记紧急维修</span>
            </div>
            <div class="rule-item">
              <el-icon color="#f56c6c"><CircleClose /></el-icon>
              <span>资格复核失败的住户不能续租</span>
            </div>
            <div class="rule-item">
              <el-icon color="#409eff"><Picture /></el-icon>
              <span>完工照片缺失不能结案</span>
            </div>
          </div>
        </div>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted, markRaw } from 'vue'
import { useRouter } from 'vue-router'
import { 
  User, Document, Money, Tools, 
  Refresh, Top, Bottom, ArrowRight,
  Warning, CircleClose, Picture
} from '@element-plus/icons-vue'
import { formatDateTime } from '@/utils/constants'

const router = useRouter()
const loading = ref(false)

const stats = ref([
  { key: 'residents', label: '在租住户数', value: '1,234', color: 'text-info', iconColor: '#409eff', icon: markRaw(User), trend: 2.5 },
  { key: 'leases', label: '有效租约数', value: '1,189', color: 'text-success', iconColor: '#67c23a', icon: markRaw(Document), trend: 1.2 },
  { key: 'arrears', label: '欠费总额', value: '¥128.5万', color: 'text-danger', iconColor: '#f56c6c', icon: markRaw(Money), trend: -3.5 },
  { key: 'maintenance', label: '本月工单', value: '156', color: 'text-warning', iconColor: '#e6a23c', icon: markRaw(Tools), trend: 8.3 }
])

const activities = ref([
  { type: '维修', tagType: 'warning', title: '3栋501室水管爆裂维修工单已派单', time: new Date(Date.now() - 1000 * 60 * 30) },
  { type: '欠费', tagType: 'danger', title: '住户张三欠费已超过阈值，限制非紧急维修', time: new Date(Date.now() - 1000 * 60 * 60 * 2) },
  { type: '资格', tagType: 'success', title: '2024年第2季度资格复核批次已完成', time: new Date(Date.now() - 1000 * 60 * 60 * 5) },
  { type: '租约', tagType: 'info', title: '住户李四的续租申请已审核通过', time: new Date(Date.now() - 1000 * 60 * 60 * 8) },
  { type: '维修', tagType: 'success', title: '5栋203室电路维修已完成并结案', time: new Date(Date.now() - 1000 * 60 * 60 * 12) }
])

const todoList = ref([
  { key: 'pending_orders', label: '待派单工单', count: 12, color: '#e6a23c', icon: markRaw(Tools), path: '/maintenance' },
  { key: 'pending_renewals', label: '待审核续租', count: 8, color: '#409eff', icon: markRaw(Document), path: '/renewals' },
  { key: 'pending_qualification', label: '待资格复核', count: 45, color: '#67c23a', icon: markRaw(User), path: '/qualification' },
  { key: 'overdue_arrears', label: '逾期欠费', count: 23, color: '#f56c6c', icon: markRaw(Money), path: '/arrears' }
])

const loadData = () => {
  loading.value = true
  setTimeout(() => {
    loading.value = false
  }, 500)
}

const goToDetail = (row) => {
  if (row.type === '维修') {
    router.push('/maintenance')
  } else if (row.type === '租约') {
    router.push('/leases')
  } else if (row.type === '欠费') {
    router.push('/arrears')
  } else if (row.type === '资格') {
    router.push('/qualification')
  }
}

const goToTodo = (item) => {
  router.push(item.path)
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.todo-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.todo-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 8px;
  background: #f5f7fa;
  cursor: pointer;
  transition: all 0.3s;
}

.todo-item:hover {
  background: #ecf5ff;
  transform: translateX(4px);
}

.todo-icon {
  width: 44px;
  height: 44px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.todo-content {
  flex: 1;
}

.todo-title {
  font-size: 14px;
  color: #303133;
  margin-bottom: 4px;
}

.todo-count {
  font-size: 12px;
  color: #909399;
}

.rule-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.rule-item {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  font-size: 13px;
  color: #606266;
  line-height: 1.6;
}
</style>
