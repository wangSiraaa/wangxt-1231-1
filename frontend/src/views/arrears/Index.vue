<template>
  <div class="arrears-page">
    <el-card class="summary-card">
      <el-row :gutter="20">
        <el-col :span="6">
          <div class="summary-item">
            <div class="label">欠费总额</div>
            <div class="value danger">{{ formatCurrency(summary.total_arrears) }}</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="summary-item">
            <div class="label">欠费户数</div>
            <div class="value">{{ summary.total_households }}</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="summary-item">
            <div class="label">超阈值户数</div>
            <div class="value danger">{{ summary.over_threshold_households }}</div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="summary-item">
            <div class="label">本月新增欠费</div>
            <div class="value warning">{{ formatCurrency(summary.month_new) }}</div>
          </div>
        </el-col>
      </el-row>
    </el-card>

    <el-card class="search-card">
      <el-form :model="searchForm" inline @submit.prevent>
        <el-form-item label="住户姓名">
          <el-input v-model="searchForm.resident_name" placeholder="请输入住户姓名" clearable />
        </el-form-item>
        <el-form-item label="身份证号">
          <el-input v-model="searchForm.id_card" placeholder="请输入身份证号" clearable />
        </el-form-item>
        <el-form-item label="欠费状态">
          <el-select v-model="searchForm.status" placeholder="请选择" clearable>
            <el-option v-for="(item, key) in ARREARS_STATUS" :key="key" :label="item.label" :value="Number(key)" />
          </el-select>
        </el-form-item>
        <el-form-item label="账单月份">
          <el-date-picker v-model="searchForm.bill_month" type="month" value-format="YYYY-MM" />
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
          <span>欠费列表</span>
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
        <el-table-column prop="bill_month" label="账单月份" width="110" />
        <el-table-column label="费用明细" min-width="180">
          <template #default="{ row }">
            <div>租金: {{ formatCurrency(row.rent_amount) }}</div>
            <div v-if="row.overdue_amount > 0" class="muted">
              违约金: {{ formatCurrency(row.overdue_amount) }}
            </div>
          </template>
        </el-table-column>
        <el-table-column label="应缴金额" width="120">
          <template #default="{ row }">
            <span class="currency">{{ formatCurrency(row.total_amount) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="已缴金额" width="120">
          <template #default="{ row }">
            <span class="paid">{{ formatCurrency(row.paid_amount) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="欠缴金额" width="120">
          <template #default="{ row }">
            <span :class="row.unpaid_amount > 0 ? 'unpaid' : 'paid'">
              {{ formatCurrency(row.unpaid_amount) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status, ARREARS_STATUS)">
              {{ getStatusLabel(row.status, ARREARS_STATUS) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="欠费预警" width="120">
          <template #default="{ row }">
            <el-tag v-if="checkEmergencyOnly(row.resident?.total_unpaid_amount || 0)" type="danger" effect="dark">
              超阈值
            </el-tag>
            <el-tag v-else-if="row.unpaid_amount > 0" type="warning">
              欠费中
            </el-tag>
            <el-tag v-else type="success">正常</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="due_date" label="缴费截止日" width="120">
          <template #default="{ row }">
            {{ formatDate(row.due_date) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleView(row)">详情</el-button>
            <el-button
              v-if="row.unpaid_amount > 0"
              type="success"
              link
              @click="handlePayment(row)"
            >
              登记缴费
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

    <el-dialog v-model="detailDialogVisible" title="欠费详情" width="700px">
      <el-descriptions :column="2" border v-if="currentArrear">
        <el-descriptions-item label="住户姓名">
          {{ currentArrear.resident?.name || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="身份证号">
          {{ currentArrear.resident?.id_card || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="联系电话">
          {{ currentArrear.resident?.phone || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="房屋地址">
          {{ currentArrear.lease?.house_address || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="账单月份">
          {{ currentArrear.bill_month || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="缴费截止日">
          {{ formatDate(currentArrear.due_date) }}
        </el-descriptions-item>
        <el-descriptions-item label="租金金额">
          {{ formatCurrency(currentArrear.rent_amount) }}
        </el-descriptions-item>
        <el-descriptions-item label="物业服务费">
          {{ formatCurrency(currentArrear.service_fee || 0) }}
        </el-descriptions-item>
        <el-descriptions-item label="违约金">
          {{ formatCurrency(currentArrear.overdue_amount || 0) }}
        </el-descriptions-item>
        <el-descriptions-item label="其他费用">
          {{ formatCurrency(currentArrear.other_amount || 0) }}
        </el-descriptions-item>
        <el-descriptions-item label="应缴总额">
          <span class="currency">{{ formatCurrency(currentArrear.total_amount) }}</span>
        </el-descriptions-item>
        <el-descriptions-item label="已缴金额">
          <span class="paid">{{ formatCurrency(currentArrear.paid_amount) }}</span>
        </el-descriptions-item>
        <el-descriptions-item label="欠缴金额">
          <span class="unpaid">{{ formatCurrency(currentArrear.unpaid_amount) }}</span>
        </el-descriptions-item>
        <el-descriptions-item label="欠费状态">
          <el-tag :type="getStatusType(currentArrear.status, ARREARS_STATUS)">
            {{ getStatusLabel(currentArrear.status, ARREARS_STATUS) }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="欠费预警" :span="2">
          <el-tag v-if="checkEmergencyOnly(currentArrear.resident?.total_unpaid_amount || 0)" type="danger" effect="dark">
            已超过欠费阈值，该住户仅允许登记紧急维修事项
          </el-tag>
          <el-tag v-else type="success">正常</el-tag>
        </el-descriptions-item>
      </el-descriptions>

      <el-divider v-if="currentArrear">缴费记录</el-divider>
      <el-table v-if="paymentRecords.length > 0" :data="paymentRecords" size="small">
        <el-table-column prop="payment_date" label="缴费日期" width="120">
          <template #default="{ row }">
            {{ formatDate(row.payment_date) }}
          </template>
        </el-table-column>
        <el-table-column prop="amount" label="缴费金额" width="120">
          <template #default="{ row }">
            {{ formatCurrency(row.amount) }}
          </template>
        </el-table-column>
        <el-table-column prop="payment_method" label="缴费方式" width="120">
          <template #default="{ row }">
            {{ PAYMENT_METHOD[row.payment_method] || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="remark" label="备注" />
      </el-table>
      <el-empty v-else description="暂无缴费记录" :image-size="100" />

      <template #footer>
        <el-button @click="detailDialogVisible = false">关闭</el-button>
        <el-button
          v-if="currentArrear?.unpaid_amount > 0"
          type="success"
          @click="handlePayment(currentArrear)"
        >
          登记缴费
        </el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="paymentDialogVisible" title="登记缴费" width="500px">
      <el-form :model="paymentForm" :rules="paymentRules" ref="paymentFormRef" label-width="100px">
        <el-form-item label="欠费金额">
          <span class="unpaid">{{ formatCurrency(currentArrear?.unpaid_amount || 0) }}</span>
        </el-form-item>
        <el-form-item label="缴费金额" prop="amount">
          <el-input-number
            v-model="paymentForm.amount"
            :min="0"
            :max="currentArrear?.unpaid_amount || 0"
            :precision="2"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="缴费方式" prop="payment_method">
          <el-select v-model="paymentForm.payment_method" placeholder="请选择" style="width: 100%">
            <el-option v-for="(label, key) in PAYMENT_METHOD" :key="key" :label="label" :value="Number(key)" />
          </el-select>
        </el-form-item>
        <el-form-item label="缴费日期" prop="payment_date">
          <el-date-picker
            v-model="paymentForm.payment_date"
            type="date"
            value-format="YYYY-MM-DD"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="paymentForm.remark" type="textarea" :rows="2" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="paymentDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmPayment">确认缴费</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Search, Refresh } from '@element-plus/icons-vue'
import { getArrears, getArrear, recordPayment, getPaymentRecords, getArrearsSummary } from '@/api/arrears'
import { ARREARS_STATUS, PAYMENT_METHOD, formatDate, formatCurrency, getStatusLabel, getStatusType } from '@/utils/constants'
import { checkEmergencyOnly } from '@/utils/businessRules'

const loading = ref(false)
const detailDialogVisible = ref(false)
const paymentDialogVisible = ref(false)
const currentArrear = ref(null)
const currentArrearId = ref(null)
const paymentRecords = ref([])
const paymentFormRef = ref(null)

const summary = reactive({
  total_arrears: 0,
  total_households: 0,
  over_threshold_households: 0,
  month_new: 0
})

const searchForm = reactive({
  resident_name: '',
  id_card: '',
  status: null,
  bill_month: ''
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const tableData = ref([])

const paymentForm = reactive({
  amount: 0,
  payment_method: 2,
  payment_date: '',
  remark: ''
})

const paymentRules = {
  amount: [{ required: true, message: '请输入缴费金额', trigger: 'blur' }],
  payment_method: [{ required: true, message: '请选择缴费方式', trigger: 'change' }],
  payment_date: [{ required: true, message: '请选择缴费日期', trigger: 'change' }]
}

const fetchSummary = async () => {
  try {
    const res = await getArrearsSummary()
    Object.assign(summary, res.data || {})
  } catch (error) {
    console.error('获取欠费统计失败', error)
  }
}

const fetchData = async () => {
  loading.value = true
  try {
    const params = {
      ...searchForm,
      page: pagination.page,
      page_size: pagination.pageSize
    }
    const res = await getArrears(params)
    tableData.value = res.data.data || []
    pagination.total = res.data.total || 0
  } catch (error) {
    ElMessage.error('获取欠费列表失败')
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
  searchForm.status = null
  searchForm.bill_month = ''
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

const handleView = async (row) => {
  try {
    currentArrearId.value = row.id
    const [arrearRes, paymentsRes] = await Promise.all([
      getArrear(row.id),
      getPaymentRecords(row.id)
    ])
    currentArrear.value = arrearRes.data
    paymentRecords.value = paymentsRes.data || []
    detailDialogVisible.value = true
  } catch (error) {
    ElMessage.error('获取详情失败')
  }
}

const handlePayment = (row) => {
  currentArrear.value = row
  currentArrearId.value = row.id
  paymentForm.amount = row.unpaid_amount
  paymentForm.payment_method = 2
  paymentForm.payment_date = new Date().toISOString().split('T')[0]
  paymentForm.remark = ''
  paymentDialogVisible.value = true
}

const confirmPayment = async () => {
  if (!paymentFormRef.value) return
  await paymentFormRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      await recordPayment(currentArrearId.value, paymentForm)
      ElMessage.success('缴费登记成功')
      paymentDialogVisible.value = false
      detailDialogVisible.value = false
      fetchData()
      fetchSummary()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '登记失败')
    }
  })
}

onMounted(() => {
  fetchSummary()
  fetchData()
})
</script>

<style scoped>
.arrears-page {
  padding: 20px;
}

.summary-card {
  margin-bottom: 20px;
}

.summary-item {
  text-align: center;
  padding: 20px;
}

.summary-item .label {
  font-size: 14px;
  color: #909399;
  margin-bottom: 8px;
}

.summary-item .value {
  font-size: 28px;
  font-weight: 600;
  color: #303133;
}

.summary-item .value.danger {
  color: #f56c6c;
}

.summary-item .value.warning {
  color: #e6a23c;
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
}

.currency {
  font-weight: 500;
  color: #f56c6c;
}

.paid {
  color: #67c23a;
  font-weight: 500;
}

.unpaid {
  color: #f56c6c;
  font-weight: 500;
}

.pagination-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}
</style>
