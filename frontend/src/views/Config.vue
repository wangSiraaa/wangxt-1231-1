<template>
  <div class="page-container">
    <div class="page-header">
      <h2 class="page-title">系统配置</h2>
    </div>

    <el-row :gutter="16">
      <el-col :md="12">
        <div class="detail-card">
          <div class="detail-section-title">业务参数配置</div>
          
          <el-form :model="configForm" label-width="160px">
            <el-form-item label="欠费阈值(元)">
              <el-input-number 
                v-model="configForm.arrears_threshold" 
                :min="0" 
                :step="1000"
                style="width: 100%"
              />
              <div class="form-tip">欠费超过此金额的住户仅允许登记紧急维修</div>
            </el-form-item>

            <el-form-item label="租约提醒天数">
              <el-input-number 
                v-model="configForm.lease_reminder_days" 
                :min="1" 
                :max="180"
                style="width: 100%"
              />
              <div class="form-tip">租约到期前多少天开始提醒</div>
            </el-form-item>

            <el-form-item label="资格复核周期(月)">
              <el-input-number 
                v-model="configForm.qualification_cycle_months" 
                :min="1" 
                :max="24"
                style="width: 100%"
              />
              <div class="form-tip">自动创建资格复核批次的周期</div>
            </el-form-item>

            <el-form-item label="维修响应时限(小时)">
              <el-input-number 
                v-model="configForm.maintenance_response_hours" 
                :min="1" 
                :max="72"
                style="width: 100%"
              />
              <div class="form-tip">维修工单接单的响应时限</div>
            </el-form-item>

            <el-form-item>
              <el-button type="primary" :loading="saving" @click="saveConfig">
                <el-icon><Check /></el-icon>保存配置
              </el-button>
            </el-form-item>
          </el-form>
        </div>
      </el-col>

      <el-col :md="12">
        <div class="detail-card">
          <div class="detail-section-title">业务规则说明</div>
          
          <el-timeline>
            <el-timeline-item
              v-for="(rule, index) in rules"
              :key="index"
              :timestamp="rule.timestamp"
              :type="rule.type"
              :hollow="rule.hollow"
            >
              <h4>{{ rule.title }}</h4>
              <p>{{ rule.description }}</p>
              <el-tag :type="rule.tagType" size="small">{{ rule.tag }}</el-tag>
            </el-timeline-item>
          </el-timeline>
        </div>

        <div class="detail-card mt-16">
          <div class="detail-section-title">系统信息</div>
          <el-descriptions :column="1" border size="small">
            <el-descriptions-item label="系统版本">v1.0.0</el-descriptions-item>
            <el-descriptions-item label="前端框架">Vue 3 + Element Plus</el-descriptions-item>
            <el-descriptions-item label="后端框架">Laravel 11</el-descriptions-item>
            <el-descriptions-item label="数据库">MySQL 8.0+</el-descriptions-item>
            <el-descriptions-item label="最后更新">{{ formatDateTime(new Date()) }}</el-descriptions-item>
          </el-descriptions>
        </div>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Check } from '@element-plus/icons-vue'
import { formatDateTime } from '@/utils/constants'

const saving = ref(false)

const configForm = reactive({
  arrears_threshold: 10000,
  lease_reminder_days: 30,
  qualification_cycle_months: 3,
  maintenance_response_hours: 24
})

const rules = [
  {
    title: '欠费阈值限制',
    description: '住户欠费超过设定阈值时，只能登记紧急维修事项，普通维修将被拒绝。',
    type: 'warning',
    hollow: false,
    timestamp: '核心规则',
    tag: '强制生效',
    tagType: 'danger'
  },
  {
    title: '资格复核联动',
    description: '资格复核失败的住户，系统将自动标记为不能续租，并在续租申请时自动拦截。',
    type: 'danger',
    hollow: false,
    timestamp: '核心规则',
    tag: '强制生效',
    tagType: 'danger'
  },
  {
    title: '完工照片校验',
    description: '维修工单结案前必须上传完工照片，无照片的工单无法执行结案操作。',
    type: 'primary',
    hollow: false,
    timestamp: '核心规则',
    tag: '强制生效',
    tagType: 'danger'
  },
  {
    title: '续租申请审核',
    description: '续租申请需审核通过后才能生成新租约，审核不通过时需注明原因。',
    type: 'success',
    hollow: false,
    timestamp: '业务规则',
    tag: '标准流程',
    tagType: 'info'
  }
]

const loadConfig = async () => {
  try {
    const savedThreshold = localStorage.getItem('arrears_threshold')
    if (savedThreshold) {
      configForm.arrears_threshold = Number(savedThreshold)
    }
  } catch (e) {
    console.error('Load config error:', e)
  }
}

const saveConfig = async () => {
  saving.value = true
  try {
    localStorage.setItem('arrears_threshold', String(configForm.arrears_threshold))
    ElMessage.success('配置保存成功')
  } catch (e) {
    ElMessage.error('保存失败，请重试')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadConfig()
})
</script>

<style scoped>
.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

:deep(.el-timeline-item__timestamp) {
  color: #409eff;
  font-weight: 500;
}

:deep(.el-descriptions__label) {
  width: 120px;
}
</style>
