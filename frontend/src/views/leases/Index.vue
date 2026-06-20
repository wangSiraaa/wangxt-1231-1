<template>
  <div class="leases-page">
    <el-card class="search-card">
      <el-form :model="searchForm" inline @submit.prevent>
        <el-form-item label="住户姓名">
          <el-input v-model="searchForm.resident_name" placeholder="请输入住户姓名" clearable />
        </el-form-item>
        <el-form-item label="身份证号">
          <el-input v-model="searchForm.id_card" placeholder="请输入身份证号" clearable />
        </el-form-item>
        <el-form-item label="房屋地址">
          <el-input v-model="searchForm.house_address" placeholder="请输入房屋地址" clearable />
        </el-form-item>
        <el-form-item label="租约状态">
          <el-select v-model="searchForm.status" placeholder="请选择" clearable>
            <el-option v-for="(item, key) in LEASE_STATUS" :key="key" :label="item.label" :value="Number(key)" />
          </el-select>
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
          <span>租约列表</span>
          <div class="header-actions">
            <el-button type="primary" @click="handleAdd">
              <el-icon><Plus /></el-icon>
              新增租约
            </el-button>
            <el-button type="success" @click="handleBatchGenerateBills">
              <el-icon><DocumentAdd /></el-icon>
              批量生成账单
            </el-button>
          </div>
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
        <el-table-column prop="house_address" label="房屋地址" min-width="200" />
        <el-table-column prop="monthly_rent" label="月租金" width="120">
          <template #default="{ row }">
            <span class="currency">{{ formatCurrency(row.monthly_rent) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="租期" width="220">
          <template #default="{ row }">
            <div>{{ formatDate(row.start_date) }} 至 {{ formatDate(row.end_date) }}</div>
            <div v-if="row.is_expiring" class="expiring-warning">
              <el-icon><Warning /></el-icon>
              即将到期
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status, LEASE_STATUS)">
              {{ getStatusLabel(row.status, LEASE_STATUS) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="欠费状态" width="120">
          <template #default="{ row }">
            <div v-if="row.total_unpaid > 0" class="arrears-info">
              <el-tag type="danger" size="small">
                欠费 {{ formatCurrency(row.total_unpaid) }}
              </el-tag>
              <el-tag v-if="checkEmergencyOnly(row.total_unpaid)" type="danger" size="small" effect="dark">
                超阈值
              </el-tag>
            </div>
            <el-tag v-else type="success" size="small">无欠费</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleView(row)">查看</el-button>
            <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
            <el-button type="success" link @click="handleGenerateBills(row)" :disabled="row.status !== 1">
              生成账单
            </el-button>
            <el-button type="warning" link @click="handleTerminate(row)" :disabled="row.status !== 1">
              终止
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

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="800px" destroy-on-close>
      <el-form :model="formData" :rules="formRules" ref="formRef" label-width="120px">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="住户" prop="resident_id">
              <el-select v-model="formData.resident_id" placeholder="请选择住户" filterable remote :remote-method="searchResidents">
                <el-option v-for="item in residentOptions" :key="item.id" :label="`${item.name} - ${item.id_card}`" :value="item.id" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="房屋地址" prop="house_address">
              <el-input v-model="formData.house_address" placeholder="请输入房屋地址" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="月租金" prop="monthly_rent">
              <el-input-number v-model="formData.monthly_rent" :min="0" :precision="2" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="押金" prop="deposit">
              <el-input-number v-model="formData.deposit" :min="0" :precision="2" style="width: 100%" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="开始日期" prop="start_date">
              <el-date-picker v-model="formData.start_date" type="date" value-format="YYYY-MM-DD" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="结束日期" prop="end_date">
              <el-date-picker v-model="formData.end_date" type="date" value-format="YYYY-MM-DD" style="width: 100%" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="备注">
          <el-input v-model="formData.remark" type="textarea" :rows="3" placeholder="请输入备注" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="terminateDialogVisible" title="终止租约" width="500px">
      <el-form :model="terminateForm" label-width="100px">
        <el-form-item label="终止原因" required>
          <el-input v-model="terminateForm.reason" type="textarea" :rows="3" placeholder="请输入终止原因" />
        </el-form-item>
        <el-form-item label="终止日期">
          <el-date-picker v-model="terminateForm.terminate_date" type="date" value-format="YYYY-MM-DD" style="width: 100%" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="terminateDialogVisible = false">取消</el-button>
        <el-button type="danger" @click="confirmTerminate">确认终止</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="generateBillsDialogVisible" title="生成账单" width="500px">
      <el-form :model="billsForm" label-width="100px">
        <el-form-item label="账单月份">
          <el-date-picker v-model="billsForm.bill_month" type="month" value-format="YYYY-MM" style="width: 100%" />
        </el-form-item>
        <el-form-item label="逾期天数">
          <el-input-number v-model="billsForm.overdue_days" :min="0" style="width: 100%" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="generateBillsDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmGenerateBills">生成</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Plus, DocumentAdd, Warning } from '@element-plus/icons-vue'
import { getLeases, createLease, updateLease, terminateLease, generateBills, batchGenerateBills } from '@/api/leases'
import { getResidents } from '@/api/residents'
import { LEASE_STATUS, formatDate, formatCurrency, getStatusLabel, getStatusType } from '@/utils/constants'
import { checkEmergencyOnly } from '@/utils/businessRules'

const loading = ref(false)
const dialogVisible = ref(false)
const terminateDialogVisible = ref(false)
const generateBillsDialogVisible = ref(false)
const dialogMode = ref('add')
const currentLeaseId = ref(null)
const formRef = ref(null)
const residentOptions = ref([])

const searchForm = reactive({
  resident_name: '',
  id_card: '',
  house_address: '',
  status: null
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const tableData = ref([])

const formData = reactive({
  resident_id: null,
  house_address: '',
  monthly_rent: 0,
  deposit: 0,
  start_date: '',
  end_date: '',
  remark: ''
})

const formRules = {
  resident_id: [{ required: true, message: '请选择住户', trigger: 'change' }],
  house_address: [{ required: true, message: '请输入房屋地址', trigger: 'blur' }],
  monthly_rent: [{ required: true, message: '请输入月租金', trigger: 'blur' }],
  start_date: [{ required: true, message: '请选择开始日期', trigger: 'change' }],
  end_date: [{ required: true, message: '请选择结束日期', trigger: 'change' }]
}

const terminateForm = reactive({
  reason: '',
  terminate_date: ''
})

const billsForm = reactive({
  bill_month: '',
  overdue_days: 0
})

const dialogTitle = computed(() => {
  return dialogMode.value === 'add' ? '新增租约' : '编辑租约'
})

const fetchData = async () => {
  loading.value = true
  try {
    const params = {
      ...searchForm,
      page: pagination.page,
      page_size: pagination.pageSize
    }
    const res = await getLeases(params)
    tableData.value = res.data.data || []
    pagination.total = res.data.total || 0
  } catch (error) {
    ElMessage.error('获取租约列表失败')
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
  searchForm.id_card = ''
  searchForm.house_address = ''
  searchForm.status = null
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

const searchResidents = async (query) => {
  if (!query) return
  try {
    const res = await getResidents({ keyword: query, page_size: 20 })
    residentOptions.value = res.data.data || []
  } catch (error) {
    ElMessage.error('搜索住户失败')
  }
}

const handleAdd = () => {
  dialogMode.value = 'add'
  resetForm()
  dialogVisible.value = true
}

const handleEdit = (row) => {
  dialogMode.value = 'edit'
  currentLeaseId.value = row.id
  formData.resident_id = row.resident_id
  formData.house_address = row.house_address
  formData.monthly_rent = row.monthly_rent
  formData.deposit = row.deposit
  formData.start_date = row.start_date
  formData.end_date = row.end_date
  formData.remark = row.remark
  dialogVisible.value = true
}

const handleView = (row) => {
  // 可以跳转到详情页或显示详情对话框
  ElMessage.info(`查看租约 ID: ${row.id}`)
}

const resetForm = () => {
  formData.resident_id = null
  formData.house_address = ''
  formData.monthly_rent = 0
  formData.deposit = 0
  formData.start_date = ''
  formData.end_date = ''
  formData.remark = ''
  formRef.value?.resetFields()
}

const handleSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      if (dialogMode.value === 'add') {
        await createLease(formData)
        ElMessage.success('新增租约成功')
      } else {
        await updateLease(currentLeaseId.value, formData)
        ElMessage.success('编辑租约成功')
      }
      dialogVisible.value = false
      fetchData()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '操作失败')
    }
  })
}

const handleTerminate = (row) => {
  currentLeaseId.value = row.id
  terminateForm.reason = ''
  terminateForm.terminate_date = new Date().toISOString().split('T')[0]
  terminateDialogVisible.value = true
}

const confirmTerminate = async () => {
  if (!terminateForm.reason) {
    ElMessage.warning('请输入终止原因')
    return
  }
  try {
    await terminateLease(currentLeaseId.value, terminateForm)
    ElMessage.success('租约终止成功')
    terminateDialogVisible.value = false
    fetchData()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '终止失败')
  }
}

const handleGenerateBills = (row) => {
  currentLeaseId.value = row.id
  billsForm.bill_month = new Date().toISOString().slice(0, 7)
  billsForm.overdue_days = 0
  generateBillsDialogVisible.value = true
}

const confirmGenerateBills = async () => {
  if (!billsForm.bill_month) {
    ElMessage.warning('请选择账单月份')
    return
  }
  try {
    await generateBills(currentLeaseId.value, billsForm)
    ElMessage.success('账单生成成功')
    generateBillsDialogVisible.value = false
    fetchData()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '生成失败')
  }
}

const handleBatchGenerateBills = () => {
  ElMessageBox.confirm('确定要为所有有效租约生成当月账单吗？', '确认', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      await batchGenerateBills({
        bill_month: new Date().toISOString().slice(0, 7)
      })
      ElMessage.success('批量生成账单成功')
      fetchData()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '批量生成失败')
    }
  }).catch(() => {})
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.leases-page {
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

.header-actions {
  display: flex;
  gap: 10px;
}

.resident-info .name {
  font-weight: 500;
}

.resident-info .id-card {
  font-size: 12px;
  color: #909399;
}

.currency {
  font-weight: 500;
  color: #f56c6c;
}

.expiring-warning {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #e6a23c;
  margin-top: 4px;
}

.arrears-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.pagination-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}
</style>
