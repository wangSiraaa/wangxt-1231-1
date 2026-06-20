<template>
  <div class="renewals-page">
    <el-card class="search-card">
      <el-form :model="searchForm" inline @submit.prevent>
        <el-form-item label="住户姓名">
          <el-input v-model="searchForm.resident_name" placeholder="请输入住户姓名" clearable />
        </el-form-item>
        <el-form-item label="申请状态">
          <el-select v-model="searchForm.status" placeholder="请选择" clearable>
            <el-option v-for="(item, key) in LEASE_RENEWAL_STATUS" :key="key" :label="item.label" :value="Number(key)" />
          </el-select>
        </el-form-item>
        <el-form-item label="申请日期">
          <el-date-picker
            v-model="searchForm.date_range"
            type="daterange"
            value-format="YYYY-MM-DD"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>
            查询
          </el-button>
          <el-button @click="handleReset">
            <el-icon><Refresh /></el-icon>
            重置
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card class="table-card">
      <template #header>
        <div class="card-header">
          <span>续租申请列表</span>
        </div>
      </template>

      <el-table :data="tableData" v-loading="loading" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="住户信息" min-width="180">
          <template #default="{ row }">
            <div class="resident-info">
              <div class="name">{{ row.resident?.name || '-' }}</div>
              <div class="id-card">{{ row.resident?.id_card || '-' }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="原租约" min-width="200">
          <template #default="{ row }">
            <div>{{ row.lease?.house_address || '-' }}</div>
            <div class="muted">原租期: {{ formatDate(row.lease?.start_date) }} 至 {{ formatDate(row.lease?.end_date) }}</div>
          </template>
        </el-table-column>
        <el-table-column label="申请租期" width="220">
          <template #default="{ row }">
            <div>{{ formatDate(row.new_start_date) }} 至 {{ formatDate(row.new_end_date) }}</div>
            <div class="muted">新月租: {{ formatCurrency(row.new_monthly_rent) }}</div>
          </template>
        </el-table-column>
        <el-table-column label="资格复核状态" width="120">
          <template #default="{ row }">
            <div v-if="row.qualification_warning" class="qualification-warning">
              <el-icon><Warning /></el-icon>
              <span>资格未通过</span>
            </div>
            <el-tag v-else type="success" size="small">资格正常</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="审核状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status, LEASE_RENEWAL_STATUS)">
              {{ getStatusLabel(row.status, LEASE_RENEWAL_STATUS) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="application_date" label="申请日期" width="120">
          <template #default="{ row }">
            {{ formatDate(row.application_date) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleView(row)">查看</el-button>
            <el-button
              v-if="row.status === 0"
              type="success"
              link
              @click="handleApprove(row)"
              :disabled="row.qualification_warning"
            >
              通过
            </el-button>
            <el-button v-if="row.status === 0" type="danger" link @click="handleReject(row)">
              驳回
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrapper">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>

    <el-dialog v-model="detailDialogVisible" title="续租申请详情" width="700px">
      <el-descriptions :column="2" border v-if="currentApplication">
        <el-descriptions-item label="申请人">
          {{ currentApplication.resident?.name || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="身份证号">
          {{ currentApplication.resident?.id_card || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="联系电话">
          {{ currentApplication.resident?.phone || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="房屋地址">
          {{ currentApplication.lease?.house_address || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="原月租金">
          {{ formatCurrency(currentApplication.lease?.monthly_rent) }}
        </el-descriptions-item>
        <el-descriptions-item label="新月租金">
          {{ formatCurrency(currentApplication.new_monthly_rent) }}
        </el-descriptions-item>
        <el-descriptions-item label="原租期">
          {{ formatDate(currentApplication.lease?.start_date) }} 至 {{ formatDate(currentApplication.lease?.end_date) }}
        </el-descriptions-item>
        <el-descriptions-item label="新租期">
          {{ formatDate(currentApplication.new_start_date) }} 至 {{ formatDate(currentApplication.new_end_date) }}
        </el-descriptions-item>
        <el-descriptions-item label="申请日期">
          {{ formatDate(currentApplication.application_date) }}
        </el-descriptions-item>
        <el-descriptions-item label="审核状态">
          <el-tag :type="getStatusType(currentApplication.status, LEASE_RENEWAL_STATUS)">
            {{ getStatusLabel(currentApplication.status, LEASE_RENEWAL_STATUS) }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="资格复核状态" :span="2">
          <div v-if="currentApplication.qualification_warning" class="qualification-warning">
            <el-icon><Warning /></el-icon>
            <span>该住户最新资格复核未通过，按照业务规则不能续租</span>
          </div>
          <el-tag v-else type="success">资格复核通过</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="申请原因" :span="2">
          {{ currentApplication.reason || '-' }}
        </el-descriptions-item>
        <el-descriptions-item v-if="currentApplication.approval_remark" label="审核备注" :span="2">
          {{ currentApplication.approval_remark }}
        </el-descriptions-item>
      </el-descriptions>
      <template #footer>
        <el-button @click="detailDialogVisible = false">关闭</el-button>
        <el-button
          v-if="currentApplication?.status === 0"
          type="success"
          @click="handleApprove(currentApplication)"
          :disabled="currentApplication?.qualification_warning"
        >
          审核通过
        </el-button>
        <el-button v-if="currentApplication?.status === 0" type="danger" @click="handleReject(currentApplication)">
          审核驳回
        </el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="rejectDialogVisible" title="驳回申请" width="500px">
      <el-form :model="rejectForm" label-width="100px">
        <el-form-item label="驳回原因" required>
          <el-input v-model="rejectForm.remark" type="textarea" :rows="4" placeholder="请输入驳回原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectDialogVisible = false">取消</el-button>
        <el-button type="danger" @click="confirmReject">确认驳回</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Warning } from '@element-plus/icons-vue'
import { getRenewalApplications, approveRenewal, rejectRenewal } from '@/api/leases'
import { LEASE_RENEWAL_STATUS, formatDate, formatCurrency, getStatusLabel, getStatusType } from '@/utils/constants'
import { validateRenewalQualification } from '@/utils/businessRules'

const loading = ref(false)
const detailDialogVisible = ref(false)
const rejectDialogVisible = ref(false)
const currentApplication = ref(null)
const currentApplicationId = ref(null)

const searchForm = reactive({
  resident_name: '',
  status: null,
  date_range: []
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const tableData = ref([])

const rejectForm = reactive({
  remark: ''
})

const fetchData = async () => {
  loading.value = true
  try {
    const params = {
      ...searchForm,
      start_date: searchForm.date_range?.[0] || '',
      end_date: searchForm.date_range?.[1] || '',
      page: pagination.page,
      page_size: pagination.pageSize
    }
    delete params.date_range
    const res = await getRenewalApplications(params)
    const data = res.data.data || []
    tableData.value = data.map(item => ({
      ...item,
      qualification_warning: !validateRenewalQualification(item).valid
    }))
    pagination.total = res.data.total || 0
  } catch (error) {
    ElMessage.error('获取续租申请列表失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  pagination.page = 1
  fetchData()
}

const handleReset = () => {
  searchForm.resident_name = ''
  searchForm.status = null
  searchForm.date_range = []
  handleSearch()
}

const handleSizeChange = (val) => {
  pagination.pageSize = val
  fetchData()
}

const handlePageChange = (val) => {
  pagination.page = val
  fetchData()
}

const handleView = (row) => {
  currentApplication.value = row
  detailDialogVisible.value = true
}

const handleApprove = (row) => {
  const validation = validateRenewalQualification(row)
  if (!validation.valid) {
    ElMessage.warning(validation.message)
    return
  }
  
  ElMessageBox.confirm('确定要通过该续租申请吗？通过后将自动生成新租约。', '确认', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      await approveRenewal(row.id, { remark: '审核通过' })
      ElMessage.success('审核通过，新租约已生成')
      fetchData()
      detailDialogVisible.value = false
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '审核失败')
    }
  }).catch(() => {})
}

const handleReject = (row) => {
  currentApplicationId.value = row.id
  rejectForm.remark = ''
  rejectDialogVisible.value = true
}

const confirmReject = async () => {
  if (!rejectForm.remark) {
    ElMessage.warning('请输入驳回原因')
    return
  }
  try {
    await rejectRenewal(currentApplicationId.value, rejectForm)
    ElMessage.success('已驳回申请')
    rejectDialogVisible.value = false
    detailDialogVisible.value = false
    fetchData()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败')
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.renewals-page {
  padding: 20px;
}

.search-card {
  margin-bottom: 20px;
}

.table-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.resident-info .name {
  font-weight: 500;
}

.resident-info .id-card {
  font-size: 12px;
  color: #909399;
}

.muted {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.qualification-warning {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #f56c6c;
  font-size: 12px;
}

.pagination-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}
</style>
