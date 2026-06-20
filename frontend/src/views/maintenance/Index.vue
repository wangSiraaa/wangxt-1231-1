<template>
  <div class="maintenance-page">
    <el-card class="search-card">
      <el-form :model="searchForm" inline @submit.prevent>
        <el-form-item label="工单号">
          <el-input v-model="searchForm.order_no" placeholder="请输入工单号" clearable />
        </el-form-item>
        <el-form-item label="住户姓名">
          <el-input v-model="searchForm.resident_name" placeholder="请输入住户姓名" clearable />
        </el-form-item>
        <el-form-item label="工单状态">
          <el-select v-model="searchForm.status" placeholder="请选择" clearable>
            <el-option v-for="(item, key) in MAINTENANCE_STATUS" :key="key" :label="item.label" :value="Number(key)" />
          </el-select>
        </el-form-item>
        <el-form-item label="工单类型">
          <el-select v-model="searchForm.type" placeholder="请选择" clearable>
            <el-option v-for="(item, key) in MAINTENANCE_TYPE" :key="key" :label="item.label" :value="Number(key)" />
          </el-select>
        </el-form-item>
        <el-form-item label="报修日期">
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
          <span>维修工单列表</span>
          <div class="header-actions">
            <el-button type="primary" @click="handleAdd">
              <el-icon><Plus /></el-icon>
              新增工单
            </el-button>
          </div>
        </div>
      </template>

      <el-table :data="tableData" v-loading="loading" stripe @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="50" />
        <el-table-column prop="order_no" label="工单号" width="140" />
        <el-table-column label="住户信息" min-width="180">
          <template #default="{ row }">
            <div class="resident-info">
              <div class="name">{{ row.resident?.name || '-' }}</div>
              <div class="id-card">{{ row.resident?.phone || row.resident?.id_card || '-' }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="房屋地址" min-width="200">
          <template #default="{ row }">
            {{ row.lease?.house_address || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="title" label="故障描述" min-width="180" show-overflow-tooltip />
        <el-table-column label="工单类型" width="100">
          <template #default="{ row }">
            <el-tag :type="MAINTENANCE_TYPE[row.type]?.type">
              {{ MAINTENANCE_TYPE[row.type]?.label || '-' }}
            </el-tag>
            <el-tag
              v-if="row.type !== 2 && checkEmergencyOnly(row.resident?.total_unpaid_amount || 0)"
              type="danger"
              effect="dark"
              size="small"
              style="margin-left: 4px"
            >
              违规
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="紧急程度" width="100">
          <template #default="{ row }">
            <el-tag :type="MAINTENANCE_URGENCY[row.urgency]?.type">
              {{ MAINTENANCE_URGENCY[row.urgency]?.label || '-' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status, MAINTENANCE_STATUS)">
              {{ getStatusLabel(row.status, MAINTENANCE_STATUS) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="照片" width="80">
          <template #default="{ row }">
            <div v-if="row.has_photos" class="photo-indicator">
              <el-icon><Picture /></el-icon>
            </div>
            <span v-else class="no-photo">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="report_date" label="报修日期" width="120">
          <template #default="{ row }">
            {{ formatDate(row.report_date) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleView(row)">详情</el-button>
            <el-button
              v-if="row.status === 1"
              type="warning"
              link
              @click="handleAssign(row)"
            >
              派单
            </el-button>
            <el-button
              v-if="row.status === 2"
              type="primary"
              link
              @click="handleAccept(row)"
            >
              接单
            </el-button>
            <el-button
              v-if="row.status === 3"
              type="success"
              link
              @click="handleComplete(row)"
            >
              完工
            </el-button>
            <el-button
              v-if="row.status === 4"
              type="success"
              link
              @click="handleClose(row)"
            >
              结案
            </el-button>
            <el-button
              v-if="row.status < 5"
              type="danger"
              link
              @click="handleCancel(row)"
            >
              取消
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

    <el-dialog v-model="createDialogVisible" title="新增维修工单" width="700px" destroy-on-close>
      <el-alert
        v-if="isEmergencyOnly"
        title="该住户欠费金额已超过阈值，仅允许登记紧急维修事项"
        type="warning"
        show-icon
        :closable="false"
        style="margin-bottom: 20px"
      />
      <el-form :model="createForm" :rules="createRules" ref="createFormRef" label-width="100px">
        <el-form-item label="住户" prop="resident_id">
          <el-select
            v-model="createForm.resident_id"
            placeholder="请选择住户"
            filterable
            remote
            :remote-method="searchResidents"
            @change="handleResidentChange"
            style="width: 100%"
          >
            <el-option
              v-for="item in residentOptions"
              :key="item.id"
              :label="`${item.name} - ${item.id_card}`"
              :value="item.id"
            >
              <span>{{ item.name }} - {{ item.id_card }}</span>
              <el-tag
                v-if="checkEmergencyOnly(item.total_unpaid_amount || 0)"
                type="danger"
                size="small"
                effect="dark"
                style="margin-left: 8px"
              >
                欠费超阈值，仅允许紧急维修
              </el-tag>
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="租约" prop="lease_id" v-if="residentLeases.length > 0">
          <el-select v-model="createForm.lease_id" placeholder="请选择租约" style="width: 100%">
            <el-option
              v-for="item in residentLeases"
              :key="item.id"
              :label="item.house_address"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="故障标题" prop="title">
          <el-input v-model="createForm.title" placeholder="请输入故障标题" />
        </el-form-item>
        <el-form-item label="故障描述" prop="description">
          <el-input v-model="createForm.description" type="textarea" :rows="3" placeholder="请详细描述故障情况" />
        </el-form-item>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="工单类型" prop="type">
              <el-select v-model="createForm.type" placeholder="请选择" style="width: 100%">
                <el-option
                  v-for="(item, key) in MAINTENANCE_TYPE"
                  :key="key"
                  :label="item.label"
                  :value="Number(key)"
                  :disabled="isEmergencyOnly && Number(key) !== 2"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="紧急程度" prop="urgency">
              <el-select v-model="createForm.urgency" placeholder="请选择" style="width: 100%">
                <el-option v-for="(item, key) in MAINTENANCE_URGENCY" :key="key" :label="item.label" :value="Number(key)" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="报修人联系方式" prop="contact_phone">
          <el-input v-model="createForm.contact_phone" placeholder="请输入联系电话" />
        </el-form-item>
        <el-form-item label="预约时间">
          <el-date-picker
            v-model="createForm.appointment_time"
            type="datetime"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="createDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleCreateSubmit">提交</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="detailDialogVisible" title="工单详情" width="900px" destroy-on-close>
      <el-descriptions :column="2" border v-if="currentOrder">
        <el-descriptions-item label="工单号">
          {{ currentOrder.order_no }}
        </el-descriptions-item>
        <el-descriptions-item label="工单状态">
          <el-tag :type="getStatusType(currentOrder.status, MAINTENANCE_STATUS)">
            {{ getStatusLabel(currentOrder.status, MAINTENANCE_STATUS) }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="住户姓名">
          {{ currentOrder.resident?.name || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="联系电话">
          {{ currentOrder.resident?.phone || currentOrder.contact_phone || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="房屋地址">
          {{ currentOrder.lease?.house_address || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="工单类型">
          <el-tag :type="MAINTENANCE_TYPE[currentOrder.type]?.type">
            {{ MAINTENANCE_TYPE[currentOrder.type]?.label || '-' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="紧急程度">
          <el-tag :type="MAINTENANCE_URGENCY[currentOrder.urgency]?.type">
            {{ MAINTENANCE_URGENCY[currentOrder.urgency]?.label || '-' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="报修日期">
          {{ formatDate(currentOrder.report_date) }}
        </el-descriptions-item>
        <el-descriptions-item label="故障标题" :span="2">
          {{ currentOrder.title }}
        </el-descriptions-item>
        <el-descriptions-item label="故障描述" :span="2">
          {{ currentOrder.description || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="维修人员">
          {{ currentOrder.assigned_to || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="预约时间">
          {{ formatDateTime(currentOrder.appointment_time) }}
        </el-descriptions-item>
        <el-descriptions-item label="开始时间">
          {{ formatDateTime(currentOrder.start_time) }}
        </el-descriptions-item>
        <el-descriptions-item label="完成时间">
          {{ formatDateTime(currentOrder.complete_time) }}
        </el-descriptions-item>
        <el-descriptions-item label="结案时间">
          {{ formatDateTime(currentOrder.close_time) }}
        </el-descriptions-item>
        <el-descriptions-item label="维修备注" :span="2">
          {{ currentOrder.complete_remark || '-' }}
        </el-descriptions-item>
      </el-descriptions>

      <el-divider>维修照片</el-divider>
      <div class="photo-section">
        <div v-if="photos.length === 0" class="no-photos">
          <el-empty description="暂无照片" :image-size="80" />
        </div>
        <div v-else class="photo-grid">
          <div v-for="(photo, index) in photos" :key="photo.id || index" class="photo-item">
            <img :src="photo.photo_url || `https://picsum.photos/200/200?random=${index}`" class="photo-img" />
            <div class="photo-actions">
              <el-tag size="small">{{ photo.photo_type === 'completion' ? '完工照' : '现场照' }}</el-tag>
              <el-button
                v-if="currentOrder?.status < 5"
                type="danger"
                link
                size="small"
                @click="handleDeletePhoto(photo)"
              >
                删除
              </el-button>
            </div>
          </div>
        </div>
        <div v-if="currentOrder?.status >= 3 && currentOrder?.status < 5" class="upload-action">
          <el-upload
            :auto-upload="false"
            :show-file-list="false"
            accept="image/*"
            :on-change="handlePhotoChange"
          >
            <el-button type="primary">
              <el-icon><Upload /></el-icon>
              上传照片
            </el-button>
          </el-upload>
          <div class="photo-tip">
            <el-icon v-if="!hasCompletionPhoto" class="warning-icon"><Warning /></el-icon>
            <span :class="{ 'text-warning': !hasCompletionPhoto }">
              {{ hasCompletionPhoto ? '已上传完工照片，可以结案' : '请上传完工照片后再结案' }}
            </span>
          </div>
        </div>
      </div>

      <el-divider>维修材料</el-divider>
      <div class="material-section">
        <el-table v-if="materials.length > 0" :data="materials" size="small">
          <el-table-column prop="material_name" label="材料名称" />
          <el-table-column prop="specification" label="规格" width="120" />
          <el-table-column prop="quantity" label="数量" width="80" />
          <el-table-column prop="unit" label="单位" width="80" />
          <el-table-column prop="unit_price" label="单价" width="100">
            <template #default="{ row }">
              {{ formatCurrency(row.unit_price) }}
            </template>
          </el-table-column>
          <el-table-column label="小计" width="120">
            <template #default="{ row }">
              {{ formatCurrency(row.quantity * row.unit_price) }}
            </template>
          </el-table-column>
          <el-table-column v-if="currentOrder?.status < 5" label="操作" width="80">
            <template #default="{ row }">
              <el-button type="danger" link size="small" @click="handleDeleteMaterial(row)">
                删除
              </el-button>
            </template>
          </el-table-column>
        </el-table>
        <el-empty v-else description="暂无维修材料" :image-size="80" />
        <div v-if="currentOrder?.status >= 3 && currentOrder?.status < 5" class="material-action">
          <el-button type="primary" size="small" @click="materialDialogVisible = true">
            <el-icon><Plus /></el-icon>
            添加材料
          </el-button>
        </div>
      </div>

      <template #footer>
        <el-button @click="detailDialogVisible = false">关闭</el-button>
        <el-button
          v-if="currentOrder?.status === 4"
          type="success"
          @click="handleClose(currentOrder)"
          :disabled="!hasCompletionPhoto"
        >
          结案
        </el-button>
        <el-button
          v-if="currentOrder?.status === 3"
          type="success"
          @click="handleComplete(currentOrder)"
        >
          完工登记
        </el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="materialDialogVisible" title="添加维修材料" width="500px">
      <el-form :model="materialForm" :rules="materialRules" ref="materialFormRef" label-width="100px">
        <el-form-item label="材料名称" prop="material_name">
          <el-input v-model="materialForm.material_name" placeholder="请输入材料名称" />
        </el-form-item>
        <el-form-item label="规格" prop="specification">
          <el-input v-model="materialForm.specification" placeholder="请输入规格" />
        </el-form-item>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="数量" prop="quantity">
              <el-input-number v-model="materialForm.quantity" :min="1" :precision="2" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="单位" prop="unit">
              <el-input v-model="materialForm.unit" placeholder="如：个、米" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="单价" prop="unit_price">
          <el-input-number v-model="materialForm.unit_price" :min="0" :precision="2" style="width: 100%" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="materialForm.remark" type="textarea" :rows="2" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="materialDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleAddMaterial">添加</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="assignDialogVisible" title="派单" width="500px">
      <el-form :model="assignForm" :rules="assignRules" ref="assignFormRef" label-width="100px">
        <el-form-item label="维修人员" prop="assigned_to">
          <el-input v-model="assignForm.assigned_to" placeholder="请输入维修人员姓名" />
        </el-form-item>
        <el-form-item label="联系电话">
          <el-input v-model="assignForm.worker_phone" placeholder="请输入联系电话" />
        </el-form-item>
        <el-form-item label="预约时间">
          <el-date-picker
            v-model="assignForm.appointment_time"
            type="datetime"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="派单备注">
          <el-input v-model="assignForm.remark" type="textarea" :rows="2" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="assignDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmAssign">确认派单</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="completeDialogVisible" title="完工登记" width="500px">
      <el-form :model="completeForm" :rules="completeRules" ref="completeFormRef" label-width="100px">
        <el-form-item label="完工备注" prop="complete_remark">
          <el-input v-model="completeForm.complete_remark" type="textarea" :rows="4" placeholder="请输入维修情况说明" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="completeDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmComplete">确认完工</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="cancelDialogVisible" title="取消工单" width="500px">
      <el-form :model="cancelForm" :rules="cancelRules" ref="cancelFormRef" label-width="100px">
        <el-form-item label="取消原因" prop="reason">
          <el-input v-model="cancelForm.reason" type="textarea" :rows="3" placeholder="请输入取消原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="cancelDialogVisible = false">取消</el-button>
        <el-button type="danger" @click="confirmCancel">确认取消</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Plus, Picture, Upload, Warning } from '@element-plus/icons-vue'
import {
  getMaintenanceOrders,
  getMaintenanceOrder,
  createMaintenanceOrder,
  assignOrder,
  acceptOrder,
  completeOrder,
  closeOrder,
  cancelOrder,
  uploadPhoto,
  deletePhoto,
  addMaterial,
  deleteMaterial
} from '@/api/maintenance'
import { getResidents } from '@/api/residents'
import {
  MAINTENANCE_STATUS,
  MAINTENANCE_TYPE,
  MAINTENANCE_URGENCY,
  formatDate,
  formatDateTime,
  formatCurrency,
  getStatusLabel,
  getStatusType
} from '@/utils/constants'
import { validateMaintenanceType, validateCloseOrder, checkEmergencyOnly } from '@/utils/businessRules'

const loading = ref(false)
const createDialogVisible = ref(false)
const detailDialogVisible = ref(false)
const materialDialogVisible = ref(false)
const assignDialogVisible = ref(false)
const completeDialogVisible = ref(false)
const cancelDialogVisible = ref(false)
const currentOrder = ref(null)
const currentOrderId = ref(null)
const selectedRows = ref([])
const residentOptions = ref([])
const residentLeases = ref([])
const photos = ref([])
const materials = ref([])
const createFormRef = ref(null)
const materialFormRef = ref(null)
const assignFormRef = ref(null)
const completeFormRef = ref(null)
const cancelFormRef = ref(null)

const searchForm = reactive({
  order_no: '',
  resident_name: '',
  status: null,
  type: null,
  date_range: []
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const tableData = ref([])

const createForm = reactive({
  resident_id: null,
  lease_id: null,
  title: '',
  description: '',
  type: 1,
  urgency: 2,
  contact_phone: '',
  appointment_time: ''
})

const createRules = {
  resident_id: [{ required: true, message: '请选择住户', trigger: 'change' }],
  lease_id: [{ required: true, message: '请选择租约', trigger: 'change' }],
  title: [{ required: true, message: '请输入故障标题', trigger: 'blur' }],
  description: [{ required: true, message: '请输入故障描述', trigger: 'blur' }],
  type: [{ required: true, message: '请选择工单类型', trigger: 'change' }],
  urgency: [{ required: true, message: '请选择紧急程度', trigger: 'change' }]
}

const assignForm = reactive({
  assigned_to: '',
  worker_phone: '',
  appointment_time: '',
  remark: ''
})

const assignRules = {
  assigned_to: [{ required: true, message: '请输入维修人员', trigger: 'blur' }]
}

const completeForm = reactive({
  complete_remark: ''
})

const completeRules = {
  complete_remark: [{ required: true, message: '请输入完工备注', trigger: 'blur' }]
}

const cancelForm = reactive({
  reason: ''
})

const cancelRules = {
  reason: [{ required: true, message: '请输入取消原因', trigger: 'blur' }]
}

const materialForm = reactive({
  material_name: '',
  specification: '',
  quantity: 1,
  unit: '',
  unit_price: 0,
  remark: ''
})

const materialRules = {
  material_name: [{ required: true, message: '请输入材料名称', trigger: 'blur' }],
  quantity: [{ required: true, message: '请输入数量', trigger: 'blur' }],
  unit: [{ required: true, message: '请输入单位', trigger: 'blur' }],
  unit_price: [{ required: true, message: '请输入单价', trigger: 'blur' }]
}

const selectedResident = computed(() => {
  return residentOptions.value.find(r => r.id === createForm.resident_id)
})

const isEmergencyOnly = computed(() => {
  if (!selectedResident.value) return false
  return checkEmergencyOnly(selectedResident.value.total_unpaid_amount || 0)
})

const hasCompletionPhoto = computed(() => {
  return photos.value.some(p => p.photo_type === 'completion')
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
    const res = await getMaintenanceOrders(params)
    tableData.value = res.data.data || []
    pagination.total = res.data.total || 0
  } catch (error) {
    ElMessage.error('获取工单列表失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  pagination.page = 1
  fetchData()
}

const handleReset = () => {
  searchForm.order_no = ''
  searchForm.resident_name = ''
  searchForm.status = null
  searchForm.type = null
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

const handleSelectionChange = (val) => {
  selectedRows.value = val
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

const handleResidentChange = (residentId) => {
  const resident = residentOptions.value.find(r => r.id === residentId)
  if (resident?.leases) {
    residentLeases.value = resident.leases.filter(l => l.status === 1)
  } else {
    residentLeases.value = []
  }
  createForm.lease_id = residentLeases.value[0]?.id || null

  if (isEmergencyOnly.value) {
    createForm.type = 2
  }
}

const handleAdd = () => {
  createForm.resident_id = null
  createForm.lease_id = null
  createForm.title = ''
  createForm.description = ''
  createForm.type = 1
  createForm.urgency = 2
  createForm.contact_phone = ''
  createForm.appointment_time = ''
  residentLeases.value = []
  createFormRef.value?.resetFields()
  createDialogVisible.value = true
}

const handleCreateSubmit = async () => {
  if (!createFormRef.value) return
  
  const validation = validateMaintenanceType(selectedResident.value, createForm.type)
  if (!validation.valid) {
    ElMessage.warning(validation.message)
    return
  }

  await createFormRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      await createMaintenanceOrder(createForm)
      ElMessage.success('工单创建成功')
      createDialogVisible.value = false
      fetchData()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '创建失败')
    }
  })
}

const handleView = async (row) => {
  try {
    currentOrderId.value = row.id
    const res = await getMaintenanceOrder(row.id)
    currentOrder.value = res.data
    photos.value = res.data.photos || []
    materials.value = res.data.materials || []
    detailDialogVisible.value = true
  } catch (error) {
    ElMessage.error('获取工单详情失败')
  }
}

const handlePhotoChange = async (file) => {
  try {
    const res = await uploadPhoto(currentOrderId.value, file.raw, 'completion')
    photos.value.push(res.data)
    ElMessage.success('照片上传成功')
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '上传失败')
  }
}

const handleDeletePhoto = async (photo) => {
  ElMessageBox.confirm('确定要删除这张照片吗？', '确认', {
    type: 'warning'
  }).then(async () => {
    try {
      await deletePhoto(currentOrderId.value, photo.id)
      photos.value = photos.value.filter(p => p.id !== photo.id)
      ElMessage.success('删除成功')
    } catch (error) {
      ElMessage.error('删除失败')
    }
  }).catch(() => {})
}

const handleAddMaterial = async () => {
  if (!materialFormRef.value) return
  await materialFormRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      await addMaterial(currentOrderId.value, materialForm)
      materials.value.push({
        ...materialForm,
        id: Date.now()
      })
      materialDialogVisible.value = false
      ElMessage.success('材料添加成功')
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '添加失败')
    }
  })
}

const handleDeleteMaterial = async (material) => {
  ElMessageBox.confirm('确定要删除这个材料吗？', '确认', {
    type: 'warning'
  }).then(async () => {
    try {
      await deleteMaterial(currentOrderId.value, material.id)
      materials.value = materials.value.filter(m => m.id !== material.id)
      ElMessage.success('删除成功')
    } catch (error) {
      ElMessage.error('删除失败')
    }
  }).catch(() => {})
}

const handleAssign = (row) => {
  currentOrderId.value = row.id
  assignForm.assigned_to = ''
  assignForm.worker_phone = ''
  assignForm.appointment_time = ''
  assignForm.remark = ''
  assignDialogVisible.value = true
}

const confirmAssign = async () => {
  if (!assignFormRef.value) return
  await assignFormRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      await assignOrder(currentOrderId.value, assignForm)
      ElMessage.success('派单成功')
      assignDialogVisible.value = false
      fetchData()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '派单失败')
    }
  })
}

const handleAccept = (row) => {
  ElMessageBox.confirm('确定要接这个工单吗？', '确认', {
    type: 'warning'
  }).then(async () => {
    try {
      await acceptOrder(row.id)
      ElMessage.success('接单成功')
      fetchData()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '接单失败')
    }
  }).catch(() => {})
}

const handleComplete = (row) => {
  currentOrderId.value = row.id
  completeForm.complete_remark = ''
  completeDialogVisible.value = true
}

const confirmComplete = async () => {
  if (!completeFormRef.value) return
  await completeFormRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      await completeOrder(currentOrderId.value, completeForm)
      ElMessage.success('完工登记成功')
      completeDialogVisible.value = false
      detailDialogVisible.value = false
      fetchData()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '登记失败')
    }
  })
}

const handleClose = (row) => {
  const validation = validateCloseOrder({
    ...row,
    photos: photos.value
  })
  if (!validation.valid) {
    ElMessage.warning(validation.message)
    return
  }

  ElMessageBox.confirm('确定要结案吗？结案后将无法修改。', '确认', {
    type: 'warning'
  }).then(async () => {
    try {
      await closeOrder(row.id)
      ElMessage.success('结案成功')
      detailDialogVisible.value = false
      fetchData()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '结案失败')
    }
  }).catch(() => {})
}

const handleCancel = (row) => {
  currentOrderId.value = row.id
  cancelForm.reason = ''
  cancelDialogVisible.value = true
}

const confirmCancel = async () => {
  if (!cancelFormRef.value) return
  await cancelFormRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      await cancelOrder(currentOrderId.value, cancelForm)
      ElMessage.success('工单已取消')
      cancelDialogVisible.value = false
      detailDialogVisible.value = false
      fetchData()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '取消失败')
    }
  })
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.maintenance-page {
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

.photo-indicator {
  color: #409eff;
  font-size: 18px;
}

.no-photo {
  color: #909399;
}

.pagination-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}

.photo-section {
  margin-bottom: 20px;
}

.no-photos {
  text-align: center;
  padding: 20px;
}

.photo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 16px;
  margin-bottom: 16px;
}

.photo-item {
  position: relative;
  border: 1px solid #e4e7ed;
  border-radius: 4px;
  overflow: hidden;
}

.photo-img {
  width: 100%;
  height: 150px;
  object-fit: cover;
  display: block;
}

.photo-actions {
  padding: 8px;
  background: #f5f7fa;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.upload-action {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-start;
}

.photo-tip {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
}

.warning-icon {
  color: #e6a23c;
}

.text-warning {
  color: #e6a23c;
}

.material-section {
  margin-bottom: 20px;
}

.material-action {
  margin-top: 12px;
}
</style>
