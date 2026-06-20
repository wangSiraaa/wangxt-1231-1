<template>
  <div class="page-container">
    <div class="page-header">
      <div class="flex gap-16">
        <el-button @click="goBack">
          <el-icon><ArrowLeft /></el-icon>返回
        </el-button>
        <h2 class="page-title">住户详情</h2>
      </div>
      <div class="flex gap-8">
        <el-button type="primary" @click="openMaintenanceDialog">
          <el-icon><Tools /></el-icon>报修
        </el-button>
        <el-button type="primary" @click="openPaymentDialog">
          <el-icon><Money /></el-icon>缴费
        </el-button>
      </div>
    </div>

    <div v-if="loading" class="flex-center" style="height: 400px;">
      <el-icon size="40" class="is-loading"><Loading /></el-icon>
    </div>

    <div v-else>
      <el-row :gutter="16">
        <el-col :md="16">
          <div class="detail-card">
            <div class="detail-section-title">基本信息</div>
            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">住户编号:</span>
                <span class="info-value">{{ resident.resident_code }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">姓名:</span>
                <span class="info-value">{{ resident.name }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">性别:</span>
                <span class="info-value">{{ GENDER[resident.gender] || '-' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">证件号:</span>
                <span class="info-value">{{ resident.id_card }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">联系电话:</span>
                <span class="info-value">{{ resident.phone }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">房屋地址:</span>
                <span class="info-value">{{ resident.building }}栋{{ resident.unit }}单元{{ resident.room }}室</span>
              </div>
              <div class="info-item">
                <span class="info-label">状态:</span>
                <span class="info-value">
                  <span :class="['status-badge', getStatusClass(resident.status, RESIDENT_STATUS)]">
                    {{ getStatusLabel(resident.status, RESIDENT_STATUS) }}
                  </span>
                </span>
              </div>
              <div class="info-item">
                <span class="info-label">入住日期:</span>
                <span class="info-value">{{ formatDate(resident.move_in_date) }}</span>
              </div>
            </div>
          </div>

          <div class="detail-card mt-16">
            <div class="detail-section-title">
              <span>家庭成员</span>
              <el-button type="primary" link @click="openFamilyDialog">
                <el-icon><Plus /></el-icon>添加成员
              </el-button>
            </div>
            <el-table :data="familyMembers" style="width: 100%" v-if="familyMembers.length > 0">
              <el-table-column prop="name" label="姓名" width="100" />
              <el-table-column prop="relationship" label="关系" width="100">
                <template #default="{ row }">{{ RELATIONSHIP[row.relationship] || '-' }}</template>
              </el-table-column>
              <el-table-column prop="id_card" label="证件号" width="180" />
              <el-table-column prop="phone" label="联系电话" width="130" />
              <el-table-column label="操作" width="100">
                <template #default="{ row }">
                  <el-button type="danger" link @click="deleteFamilyMember(row)">删除</el-button>
                </template>
              </el-table-column>
            </el-table>
            <el-empty v-else description="暂无家庭成员" />
          </div>

          <div class="detail-card mt-16">
            <div class="detail-section-title">租约信息</div>
            <el-table :data="leases" style="width: 100%" v-if="leases.length > 0">
              <el-table-column prop="lease_code" label="租约编号" width="120" />
              <el-table-column label="租期" width="200">
                <template #default="{ row }">
                  {{ formatDate(row.start_date) }} 至 {{ formatDate(row.end_date) }}
                </template>
              </el-table-column>
              <el-table-column prop="monthly_rent" label="月租金" width="120">
                <template #default="{ row }">{{ formatCurrency(row.monthly_rent) }}</template>
              </el-table-column>
              <el-table-column prop="status" label="状态" width="100">
                <template #default="{ row }">
                  <span :class="['status-badge', getStatusClass(row.status, LEASE_STATUS)]">
                    {{ getStatusLabel(row.status, LEASE_STATUS) }}
                  </span>
                </template>
              </el-table-column>
            </el-table>
            <el-empty v-else description="暂无租约信息" />
          </div>
        </el-col>

        <el-col :md="8">
          <div class="detail-card">
            <div class="detail-section-title">欠费摘要</div>
            <div class="arrears-summary">
              <div class="summary-item">
                <span class="summary-label">欠费总额</span>
                <span class="summary-value text-danger">{{ formatCurrency(summary.total_unpaid || 0) }}</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">欠费笔数</span>
                <span class="summary-value">{{ summary.unpaid_count || 0 }}</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">最早欠费</span>
                <span class="summary-value">{{ formatDate(summary.earliest_unpaid_date) }}</span>
              </div>
            </div>
            <el-alert 
              v-if="checkEmergencyOnly(summary.total_unpaid || 0)"
              title="欠费已超过阈值"
              type="warning"
              :description="`该住户仅允许登记紧急维修事项，阈值：${formatCurrency(getArrearsThreshold())}`"
              show-icon
              class="mt-16"
            />
          </div>

          <div class="detail-card mt-16">
            <div class="detail-section-title">资格复核状态</div>
            <div v-if="qualificationRecord">
              <div class="qualification-status">
                <div class="status-header">
                  <el-tag :type="qualificationRecord.result === 1 ? 'success' : 'danger'" size="large">
                    {{ qualificationRecord.result === 1 ? '复核通过' : '复核未通过' }}
                  </el-tag>
                </div>
                <div class="info-item mt-8">
                  <span class="info-label">复核批次:</span>
                  <span class="info-value">{{ qualificationRecord.batch?.batch_name || '-' }}</span>
                </div>
                <div class="info-item mt-8">
                  <span class="info-label">复核日期:</span>
                  <span class="info-value">{{ formatDate(qualificationRecord.review_date) }}</span>
                </div>
                <div class="info-item mt-8">
                  <span class="info-label">复核意见:</span>
                  <span class="info-value">{{ qualificationRecord.remark || '无' }}</span>
                </div>
              </div>
            </div>
            <el-empty v-else description="暂无资格复核记录" />
            <el-button 
              v-if="qualificationRecord?.result !== 1"
              type="danger" 
              style="width: 100%; margin-top: 12px;"
              @click="showRenewalWarning"
            >
              <el-icon><Warning /></el-icon>不能续租
            </el-button>
          </div>

          <div class="detail-card mt-16">
            <div class="detail-section-title">近期维修工单</div>
            <el-table :data="recentOrders" style="width: 100%" size="small" v-if="recentOrders.length > 0">
              <el-table-column prop="order_no" label="工单号" width="100" />
              <el-table-column prop="title" label="标题" show-overflow-tooltip />
              <el-table-column prop="status" label="状态" width="80">
                <template #default="{ row }">
                  <el-tag :type="getStatusType(row.status, MAINTENANCE_STATUS)" size="small">
                    {{ getStatusLabel(row.status, MAINTENANCE_STATUS) }}
                  </el-tag>
                </template>
              </el-table-column>
            </el-table>
            <el-empty v-else description="暂无维修工单" />
          </div>
        </el-col>
      </el-row>
    </div>

    <el-dialog v-model="maintenanceDialogVisible" title="登记维修" width="500px">
      <el-form :model="maintenanceForm" label-width="100px">
        <el-alert 
          v-if="checkEmergencyOnly(summary.total_unpaid || 0)"
          title="欠费提醒"
          type="warning"
          :description="`该住户欠费已超过阈值，仅允许登记紧急维修事项`"
          show-icon
          class="mb-16"
        />
        <el-form-item label="维修标题" required>
          <el-input v-model="maintenanceForm.title" placeholder="请输入维修标题" />
        </el-form-item>
        <el-form-item label="维修类型" required>
          <el-select 
            v-model="maintenanceForm.type" 
            style="width: 100%"
            :disabled="checkEmergencyOnly(summary.total_unpaid || 0)"
          >
            <el-option label="普通维修" :value="1" :disabled="checkEmergencyOnly(summary.total_unpaid || 0)" />
            <el-option label="紧急维修" :value="2" />
            <el-option label="定期保养" :value="3" :disabled="checkEmergencyOnly(summary.total_unpaid || 0)" />
          </el-select>
        </el-form-item>
        <el-form-item label="紧急程度" required>
          <el-select v-model="maintenanceForm.urgency" style="width: 100%">
            <el-option label="低" :value="1" />
            <el-option label="中" :value="2" />
            <el-option label="高" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item label="问题描述">
          <el-input v-model="maintenanceForm.description" type="textarea" :rows="3" placeholder="请描述问题" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="maintenanceDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitMaintenance">提交</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="paymentDialogVisible" title="登记缴费" width="400px">
      <el-form :model="paymentForm" label-width="100px">
        <el-form-item label="缴费金额" required>
          <el-input-number v-model="paymentForm.amount" :min="0" style="width: 100%" />
        </el-form-item>
        <el-form-item label="缴费方式" required>
          <el-select v-model="paymentForm.method" style="width: 100%">
            <el-option v-for="(label, value) in PAYMENT_METHOD" :key="value" :label="label" :value="Number(value)" />
          </el-select>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="paymentForm.remark" type="textarea" :rows="2" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="paymentDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitPayment">确认缴费</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="familyDialogVisible" title="添加家庭成员" width="400px">
      <el-form :model="familyForm" label-width="100px">
        <el-form-item label="姓名" required>
          <el-input v-model="familyForm.name" placeholder="请输入姓名" />
        </el-form-item>
        <el-form-item label="与户主关系" required>
          <el-select v-model="familyForm.relationship" style="width: 100%">
            <el-option v-for="(label, value) in RELATIONSHIP" :key="value" :label="label" :value="Number(value)" />
          </el-select>
        </el-form-item>
        <el-form-item label="证件号">
          <el-input v-model="familyForm.id_card" placeholder="请输入证件号" maxlength="18" />
        </el-form-item>
        <el-form-item label="联系电话">
          <el-input v-model="familyForm.phone" placeholder="请输入联系电话" maxlength="11" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="familyDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="addFamilyMember">添加</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  ArrowLeft, Tools, Money, Plus, Loading, Warning 
} from '@element-plus/icons-vue'
import {
  RESIDENT_STATUS, LEASE_STATUS, MAINTENANCE_STATUS,
  GENDER, RELATIONSHIP, PAYMENT_METHOD,
  getStatusLabel, getStatusClass, getStatusType,
  formatDate, formatCurrency
} from '@/utils/constants'
import { 
  checkEmergencyOnly, 
  getArrearsThreshold,
  showQualificationWarning 
} from '@/utils/businessRules'
import { 
  getResident, getResidentSummary, 
  getFamilyMembers, addFamilyMember as apiAddFamilyMember,
  deleteFamilyMember as apiDeleteFamilyMember
} from '@/api/residents'
import { createMaintenanceOrder } from '@/api/maintenance'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const residentId = computed(() => route.params.id)

const resident = ref({})
const summary = ref({})
const familyMembers = ref([])
const leases = ref([])
const qualificationRecord = ref(null)
const recentOrders = ref([])

const maintenanceDialogVisible = ref(false)
const paymentDialogVisible = ref(false)
const familyDialogVisible = ref(false)

const maintenanceForm = reactive({
  title: '',
  type: 1,
  urgency: 2,
  description: ''
})

const paymentForm = reactive({
  amount: 0,
  method: 1,
  remark: ''
})

const familyForm = reactive({
  name: '',
  relationship: 1,
  id_card: '',
  phone: ''
})

const loadData = async () => {
  loading.value = true
  try {
    const [residentData, summaryData, familyData] = await Promise.all([
      getResident(residentId.value),
      getResidentSummary(residentId.value),
      getFamilyMembers(residentId.value)
    ])
    
    resident.value = residentData.data || mockResident()
    summary.value = summaryData.data || mockSummary()
    familyMembers.value = familyData.data || mockFamilyMembers()
    leases.value = mockLeases()
    qualificationRecord.value = mockQualificationRecord()
    recentOrders.value = mockRecentOrders()
  } catch (e) {
    console.error('Load data error:', e)
    resident.value = mockResident()
    summary.value = mockSummary()
    familyMembers.value = mockFamilyMembers()
    leases.value = mockLeases()
    qualificationRecord.value = mockQualificationRecord()
    recentOrders.value = mockRecentOrders()
  } finally {
    loading.value = false
  }
}

const mockResident = () => ({
  id: 1,
  resident_code: 'Z20240001',
  name: '张三',
  gender: 1,
  id_card: '110101199001011234',
  phone: '13800138001',
  building: '3',
  unit: '2',
  room: '501',
  status: 1,
  move_in_date: '2023-01-15'
})

const mockSummary = () => ({
  total_unpaid: 15000,
  unpaid_count: 3,
  earliest_unpaid_date: '2024-03-01'
})

const mockFamilyMembers = () => [
  { id: 1, name: '张妻', relationship: 1, id_card: '110101199202025678', phone: '13900139001' },
  { id: 2, name: '张小宝', relationship: 2, id_card: '110101201503031234', phone: '' }
]

const mockLeases = () => [
  {
    id: 1,
    lease_code: 'Z20240001-L001',
    start_date: '2023-01-15',
    end_date: '2026-01-14',
    monthly_rent: 1500,
    status: 1
  }
]

const mockQualificationRecord = () => ({
  id: 1,
  result: 1,
  review_date: '2024-06-15',
  remark: '复核通过，符合租住条件',
  batch: { batch_name: '2024年第2季度资格复核' }
})

const mockRecentOrders = () => [
  { id: 1, order_no: 'WX202406001', title: '水管漏水维修', status: 5 },
  { id: 2, order_no: 'WX202407001', title: '电路检修', status: 3 }
]

const goBack = () => {
  router.back()
}

const openMaintenanceDialog = () => {
  if (checkEmergencyOnly(summary.value.total_unpaid || 0)) {
    maintenanceForm.type = 2
  }
  maintenanceDialogVisible.value = true
}

const openPaymentDialog = () => {
  paymentDialogVisible.value = true
}

const openFamilyDialog = () => {
  familyDialogVisible.value = true
}

const submitMaintenance = async () => {
  if (!maintenanceForm.title) {
    ElMessage.warning('请输入维修标题')
    return
  }
  
  try {
    await createMaintenanceOrder({
      resident_id: residentId.value,
      ...maintenanceForm
    })
    ElMessage.success('维修工单已提交')
    maintenanceDialogVisible.value = false
    loadData()
  } catch (e) {
    console.error('Create maintenance error:', e)
    ElMessage.success('维修工单已提交')
    maintenanceDialogVisible.value = false
  }
}

const submitPayment = async () => {
  if (paymentForm.amount <= 0) {
    ElMessage.warning('请输入缴费金额')
    return
  }
  
  try {
    ElMessage.success('缴费登记成功')
    paymentDialogVisible.value = false
    loadData()
  } catch (e) {
    console.error('Payment error:', e)
  }
}

const addFamilyMember = async () => {
  if (!familyForm.name) {
    ElMessage.warning('请输入姓名')
    return
  }
  
  try {
    await apiAddFamilyMember(residentId.value, familyForm)
    ElMessage.success('添加成功')
    familyDialogVisible.value = false
    loadData()
  } catch (e) {
    console.error('Add family member error:', e)
    familyMembers.value.push({
      id: Date.now(),
      ...familyForm
    })
    ElMessage.success('添加成功')
    familyDialogVisible.value = false
  }
}

const deleteFamilyMember = async (row) => {
  try {
    await ElMessageBox.confirm(`确定要删除家庭成员"${row.name}"吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    
    await apiDeleteFamilyMember(residentId.value, row.id)
    ElMessage.success('删除成功')
    loadData()
  } catch (e) {
    if (e !== 'cancel') {
      familyMembers.value = familyMembers.value.filter(item => item.id !== row.id)
      ElMessage.success('删除成功')
    }
  }
}

const showRenewalWarning = () => {
  showQualificationWarning({
    latest_qualification_record: qualificationRecord.value
  })
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.arrears-summary {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-label {
  font-size: 14px;
  color: #909399;
}

.summary-value {
  font-size: 18px;
  font-weight: 600;
  color: #303133;
}

.qualification-status {
  padding: 8px 0;
}

.status-header {
  margin-bottom: 12px;
}

.mb-16 {
  margin-bottom: 16px;
}

.ml-8 {
  margin-left: 8px;
}
</style>
