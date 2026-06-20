<template>
  <div class="qualification-page">
    <el-tabs v-model="activeTab" type="card">
      <el-tab-pane label="复核批次" name="batch">
        <el-card class="search-card">
          <el-form :model="batchSearchForm" inline @submit.prevent>
            <el-form-item label="批次名称">
              <el-input v-model="batchSearchForm.batch_name" placeholder="请输入批次名称" clearable />
            </el-form-item>
            <el-form-item label="批次状态">
              <el-select v-model="batchSearchForm.status" placeholder="请选择" clearable>
                <el-option v-for="(item, key) in QUALIFICATION_BATCH_STATUS" :key="key" :label="item.label" :value="Number(key)" />
              </el-select>
            </el-form-item>
            <el-form-item label="复核年份">
              <el-date-picker v-model="batchSearchForm.year" type="year" value-format="YYYY" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="fetchBatches">
                <el-icon><Search /></el-icon>
                查询
              </el-button>
              <el-button @click="handleBatchReset">
                <el-icon><Refresh /></el-icon>
                重置
              </el-button>
            </el-form-item>
          </el-form>
        </el-card>

        <el-card class="table-card">
          <template #header>
            <div class="card-header">
              <span>复核批次列表</span>
              <div class="header-actions">
                <el-button type="primary" @click="handleCreateBatch">
                  <el-icon><Plus /></el-icon>
                  新建批次
                </el-button>
              </div>
            </div>
          </template>

          <el-table :data="batchList" v-loading="batchLoading" stripe>
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column prop="batch_name" label="批次名称" min-width="180" />
            <el-table-column prop="batch_year" label="复核年份" width="100" />
            <el-table-column label="统计信息" width="200">
              <template #default="{ row }">
                <div class="batch-stats">
                  <span>总计: {{ row.total_count || 0 }}</span>
                  <span class="pass">通过: {{ row.pass_count || 0 }}</span>
                  <span class="fail">未过: {{ row.fail_count || 0 }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="status" label="状态" width="100">
              <template #default="{ row }">
                <el-tag :type="getStatusType(row.status, QUALIFICATION_BATCH_STATUS)">
                  {{ getStatusLabel(row.status, QUALIFICATION_BATCH_STATUS) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="导入状态" width="100">
              <template #default="{ row }">
                <el-tag v-if="row.import_completed" type="success" size="small">已导入</el-tag>
                <el-tag v-else type="warning" size="small">待导入</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="created_at" label="创建时间" width="160">
              <template #default="{ row }">
                {{ formatDateTime(row.created_at) }}
              </template>
            </el-table-column>
            <el-table-column label="操作" width="280" fixed="right">
              <template #default="{ row }">
                <el-button type="primary" link @click="handleViewBatch(row)">查看记录</el-button>
                <el-button
                  v-if="row.status === 1"
                  type="success"
                  link
                  @click="handleImport(row)"
                >
                  导入清单
                </el-button>
                <el-button
                  v-if="row.status === 2"
                  type="warning"
                  link
                  @click="handleStartBatch(row)"
                >
                  开始复核
                </el-button>
                <el-button
                  v-if="row.status === 2"
                  type="success"
                  link
                  @click="handleCompleteBatch(row)"
                >
                  完成批次
                </el-button>
                <el-button
                  v-if="row.status < 3"
                  type="danger"
                  link
                  @click="handleDeleteBatch(row)"
                >
                  删除
                </el-button>
              </template>
            </el-table-column>
          </el-table>

          <div class="pagination-wrapper">
            <el-pagination
              v-model:current-page="batchPagination.page"
              v-model:page-size="batchPagination.pageSize"
              :page-sizes="[10, 20, 50]"
              :total="batchPagination.total"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleBatchSizeChange"
              @current-change="handleBatchPageChange"
            />
          </div>
        </el-card>
      </el-tab-pane>

      <el-tab-pane label="复核记录" name="record">
        <el-card class="search-card">
          <el-form :model="recordSearchForm" inline @submit.prevent>
            <el-form-item label="住户姓名">
              <el-input v-model="recordSearchForm.resident_name" placeholder="请输入住户姓名" clearable />
            </el-form-item>
            <el-form-item label="身份证号">
              <el-input v-model="recordSearchForm.id_card" placeholder="请输入身份证号" clearable />
            </el-form-item>
            <el-form-item label="复核结果">
              <el-select v-model="recordSearchForm.result" placeholder="请选择" clearable>
                <el-option v-for="(item, key) in QUALIFICATION_RESULT" :key="key" :label="item.label" :value="Number(key)" />
              </el-select>
            </el-form-item>
            <el-form-item label="所属批次">
              <el-select v-model="recordSearchForm.batch_id" placeholder="请选择" clearable>
                <el-option v-for="item in batchOptions" :key="item.id" :label="item.batch_name" :value="item.id" />
              </el-select>
            </el-form-item>
            <el-form-item label="复核日期">
              <el-date-picker
                v-model="recordSearchForm.date_range"
                type="daterange"
                value-format="YYYY-MM-DD"
                range-separator="至"
                start-placeholder="开始日期"
                end-placeholder="结束日期"
              />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="fetchRecords">
                <el-icon><Search /></el-icon>
                查询
              </el-button>
              <el-button @click="handleRecordReset">
                <el-icon><Refresh /></el-icon>
                重置
              </el-button>
            </el-form-item>
          </el-form>
        </el-card>

        <el-card class="table-card">
          <template #header>
            <div class="card-header">
              <span>复核记录列表</span>
              <div class="header-actions" v-if="selectedRecords.length > 0">
                <el-button type="success" size="small" @click="handleBatchPass">
                  <el-icon><Check /></el-icon>
                  批量通过 ({{ selectedRecords.length }})
                </el-button>
                <el-button type="danger" size="small" @click="handleBatchFail">
                  <el-icon><Close /></el-icon>
                  批量不通过 ({{ selectedRecords.length }})
                </el-button>
              </div>
            </div>
          </template>

          <el-table
            :data="recordList"
            v-loading="recordLoading"
            stripe
            @selection-change="handleRecordSelectionChange"
          >
            <el-table-column type="selection" width="50" :selectable="checkRecordSelectable" />
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column label="住户信息" min-width="180">
              <template #default="{ row }">
                <div class="resident-info">
                  <div class="name">{{ row.resident?.name || '-' }}</div>
                  <div class="id-card">{{ row.id_card || row.resident?.id_card || '-' }}</div>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="所属批次" min-width="150">
              <template #default="{ row }">
                {{ row.batch?.batch_name || '-' }}
              </template>
            </el-table-column>
            <el-table-column label="复核项目" min-width="180">
              <template #default="{ row }">
                <div v-if="row.items && row.items.length > 0" class="check-items">
                  <el-tag
                    v-for="(item, idx) in row.items.slice(0, 3)"
                    :key="idx"
                    size="small"
                    :type="item.passed ? 'success' : 'danger'"
                    style="margin-right: 4px; margin-bottom: 4px"
                  >
                    {{ item.name }}
                  </el-tag>
                  <el-tag v-if="row.items.length > 3" size="small">
                    +{{ row.items.length - 3 }}
                  </el-tag>
                </div>
                <span v-else>-</span>
              </template>
            </el-table-column>
            <el-table-column label="复核结果" width="120">
              <template #default="{ row }">
                <el-tag :type="getStatusType(row.result, QUALIFICATION_RESULT)">
                  {{ getStatusLabel(row.result, QUALIFICATION_RESULT) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="影响租约" width="100">
              <template #default="{ row }">
                <el-tag v-if="row.result === 2" type="danger" size="small">
                  不能续租
                </el-tag>
                <el-tag v-else-if="row.result === 1" type="success" size="small">
                  可续租
                </el-tag>
                <span v-else>-</span>
              </template>
            </el-table-column>
            <el-table-column prop="review_date" label="复核日期" width="120">
              <template #default="{ row }">
                {{ formatDate(row.review_date) }}
              </template>
            </el-table-column>
            <el-table-column label="操作" width="200" fixed="right">
              <template #default="{ row }">
                <el-button type="primary" link @click="handleViewRecord(row)">详情</el-button>
                <el-button
                  v-if="row.result === 0"
                  type="success"
                  link
                  @click="handlePassRecord(row)"
                >
                  通过
                </el-button>
                <el-button
                  v-if="row.result === 0"
                  type="danger"
                  link
                  @click="handleFailRecord(row)"
                >
                  不通过
                </el-button>
              </template>
            </el-table-column>
          </el-table>

          <div class="pagination-wrapper">
            <el-pagination
              v-model:current-page="recordPagination.page"
              v-model:page-size="recordPagination.pageSize"
              :page-sizes="[10, 20, 50, 100]"
              :total="recordPagination.total"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleRecordSizeChange"
              @current-change="handleRecordPageChange"
            />
          </div>
        </el-card>
      </el-tab-pane>
    </el-tabs>

    <el-dialog v-model="batchDialogVisible" :title="batchDialogTitle" width="600px">
      <el-form :model="batchForm" :rules="batchRules" ref="batchFormRef" label-width="100px">
        <el-form-item label="批次名称" prop="batch_name">
          <el-input v-model="batchForm.batch_name" placeholder="请输入批次名称，如：2024年度第一季度复核" />
        </el-form-item>
        <el-form-item label="复核年份" prop="batch_year">
          <el-date-picker v-model="batchForm.batch_year" type="year" value-format="YYYY" style="width: 100%" />
        </el-form-item>
        <el-form-item label="复核说明">
          <el-input v-model="batchForm.description" type="textarea" :rows="3" placeholder="请输入复核说明" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="batchDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleBatchSubmit">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="importDialogVisible" title="导入资格清单" width="500px">
      <el-alert
        title="请按照模板格式导入Excel文件，包含列：姓名、身份证号、复核项目、复核结果"
        type="info"
        show-icon
        :closable="false"
        style="margin-bottom: 20px"
      />
      <div class="import-area">
        <el-upload
          drag
          :auto-upload="false"
          :show-file-list="true"
          accept=".xlsx,.xls"
          :on-change="handleFileChange"
          :on-remove="handleFileRemove"
        >
          <el-icon class="el-icon--upload"><UploadFilled /></el-icon>
          <div class="el-upload__text">
            将Excel文件拖到此处，或<em>点击上传</em>
          </div>
          <template #tip>
            <div class="el-upload__tip">支持 xlsx、xls 格式文件</div>
          </template>
        </el-upload>
      </div>
      <div v-if="importFile" class="file-info">
        <el-tag type="info">{{ importFile.name }}</el-tag>
        <span class="file-size">{{ formatFileSize(importFile.size) }}</span>
      </div>
      <template #footer>
        <el-button @click="importDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmImport" :loading="importing" :disabled="!importFile">
          开始导入
        </el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="recordDetailVisible" title="复核记录详情" width="700px" destroy-on-close>
      <el-descriptions :column="2" border v-if="currentRecord">
        <el-descriptions-item label="住户姓名">
          {{ currentRecord.resident?.name || currentRecord.name || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="身份证号">
          {{ currentRecord.id_card || currentRecord.resident?.id_card || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="联系电话">
          {{ currentRecord.resident?.phone || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="所属批次">
          {{ currentRecord.batch?.batch_name || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="复核年份">
          {{ currentRecord.batch?.batch_year || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="复核日期">
          {{ formatDate(currentRecord.review_date) }}
        </el-descriptions-item>
        <el-descriptions-item label="复核结果">
          <el-tag :type="getStatusType(currentRecord.result, QUALIFICATION_RESULT)">
            {{ getStatusLabel(currentRecord.result, QUALIFICATION_RESULT) }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="复核人员">
          {{ currentRecord.reviewer || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="复核项目" :span="2">
          <div v-if="currentRecord.items && currentRecord.items.length > 0" class="check-items-detail">
            <div v-for="(item, idx) in currentRecord.items" :key="idx" class="check-item">
              <span class="item-name">{{ item.name }}</span>
              <span class="item-result">
                <el-tag :type="item.passed ? 'success' : 'danger'" size="small">
                  {{ item.passed ? '通过' : '不通过' }}
                </el-tag>
              </span>
              <span v-if="item.remark" class="item-remark">（{{ item.remark }}）</span>
            </div>
          </div>
          <span v-else>-</span>
        </el-descriptions-item>
        <el-descriptions-item v-if="currentRecord.result === 2" label="不通过原因" :span="2">
          <el-tag type="danger" effect="dark">
            该住户资格复核未通过，按照业务规则不能续租
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="复核备注" :span="2">
          {{ currentRecord.review_remark || '-' }}
        </el-descriptions-item>
      </el-descriptions>
      <template #footer>
        <el-button @click="recordDetailVisible = false">关闭</el-button>
        <el-button
          v-if="currentRecord?.result === 0"
          type="success"
          @click="handlePassRecord(currentRecord)"
        >
          复核通过
        </el-button>
        <el-button
          v-if="currentRecord?.result === 0"
          type="danger"
          @click="handleFailRecord(currentRecord)"
        >
          复核不通过
        </el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="reviewDialogVisible" :title="reviewDialogTitle" width="500px">
      <el-form :model="reviewForm" :rules="reviewRules" ref="reviewFormRef" label-width="100px">
        <el-form-item label="复核项目">
          <div v-for="(item, idx) in reviewForm.items" :key="idx" class="review-item">
            <el-checkbox v-model="item.passed" :label="item.name">
              {{ item.name }}
            </el-checkbox>
            <el-input
              v-if="!item.passed"
              v-model="item.remark"
              placeholder="请输入不通过原因"
              size="small"
              style="margin-top: 4px"
            />
          </div>
        </el-form-item>
        <el-form-item label="复核备注">
          <el-input v-model="reviewForm.review_remark" type="textarea" :rows="2" placeholder="请输入复核备注" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="reviewDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmReview">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Plus, Check, Close, UploadFilled } from '@element-plus/icons-vue'
import {
  getBatches,
  getBatch,
  createBatch,
  updateBatch,
  deleteBatch,
  importRecords,
  getRecords,
  passRecord,
  failRecord,
  batchPass,
  batchFail
} from '@/api/qualification'
import {
  QUALIFICATION_RESULT,
  QUALIFICATION_BATCH_STATUS,
  formatDate,
  formatDateTime,
  getStatusLabel,
  getStatusType
} from '@/utils/constants'

const activeTab = ref('batch')

const batchLoading = ref(false)
const recordLoading = ref(false)
const batchDialogVisible = ref(false)
const importDialogVisible = ref(false)
const recordDetailVisible = ref(false)
const reviewDialogVisible = ref(false)
const batchFormRef = ref(null)
const reviewFormRef = ref(null)
const currentBatch = ref(null)
const currentRecord = ref(null)
const selectedRecords = ref([])
const importFile = ref(null)
const importing = ref(false)
const reviewMode = ref('pass')

const batchSearchForm = reactive({
  batch_name: '',
  status: null,
  year: ''
})

const batchPagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const batchList = ref([])
const batchOptions = ref([])

const recordSearchForm = reactive({
  resident_name: '',
  id_card: '',
  result: null,
  batch_id: null,
  date_range: []
})

const recordPagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

const recordList = ref([])

const batchForm = reactive({
  batch_name: '',
  batch_year: '',
  description: ''
})

const batchRules = {
  batch_name: [{ required: true, message: '请输入批次名称', trigger: 'blur' }],
  batch_year: [{ required: true, message: '请选择复核年份', trigger: 'change' }]
}

const reviewForm = reactive({
  items: [],
  review_remark: ''
})

const reviewRules = {
  review_remark: [{ required: true, message: '请输入复核备注', trigger: 'blur' }]
}

const batchDialogTitle = computed(() => {
  return currentBatch.value ? '编辑批次' : '新建批次'
})

const reviewDialogTitle = computed(() => {
  return reviewMode.value === 'pass' ? '复核通过' : '复核不通过'
})

const fetchBatches = async () => {
  batchLoading.value = true
  try {
    const params = {
      ...batchSearchForm,
      page: batchPagination.page,
      page_size: batchPagination.pageSize
    }
    const res = await getBatches(params)
    batchList.value = res.data.data || []
    batchPagination.total = res.data.total || 0
    if (batchList.value.length > 0) {
      batchOptions.value = batchList.value
    }
  } catch (error) {
    ElMessage.error('获取批次列表失败')
  } finally {
    batchLoading.value = false
  }
}

const fetchAllBatches = async () => {
  try {
    const res = await getBatches({ page_size: 1000 })
    batchOptions.value = res.data.data || []
  } catch (error) {
    console.error('获取所有批次失败', error)
  }
}

const handleBatchReset = () => {
  batchSearchForm.batch_name = ''
  batchSearchForm.status = null
  batchSearchForm.year = ''
  batchPagination.page = 1
  fetchBatches()
}

const handleBatchSizeChange = (val) => {
  batchPagination.pageSize = val
  fetchBatches()
}

const handleBatchPageChange = (val) => {
  batchPagination.page = val
  fetchBatches()
}

const fetchRecords = async () => {
  recordLoading.value = true
  try {
    const params = {
      ...recordSearchForm,
      start_date: recordSearchForm.date_range?.[0] || '',
      end_date: recordSearchForm.date_range?.[1] || '',
      page: recordPagination.page,
      page_size: recordPagination.pageSize
    }
    delete params.date_range
    const res = await getRecords(params)
    recordList.value = res.data.data || []
    recordPagination.total = res.data.total || 0
  } catch (error) {
    ElMessage.error('获取记录列表失败')
  } finally {
    recordLoading.value = false
  }
}

const handleRecordReset = () => {
  recordSearchForm.resident_name = ''
  recordSearchForm.id_card = ''
  recordSearchForm.result = null
  recordSearchForm.batch_id = null
  recordSearchForm.date_range = []
  recordPagination.page = 1
  fetchRecords()
}

const handleRecordSizeChange = (val) => {
  recordPagination.pageSize = val
  fetchRecords()
}

const handleRecordPageChange = (val) => {
  recordPagination.page = val
  fetchRecords()
}

const handleRecordSelectionChange = (val) => {
  selectedRecords.value = val
}

const checkRecordSelectable = (row) => {
  return row.result === 0
}

const handleCreateBatch = () => {
  currentBatch.value = null
  batchForm.batch_name = ''
  batchForm.batch_year = new Date().getFullYear().toString()
  batchForm.description = ''
  batchFormRef.value?.resetFields()
  batchDialogVisible.value = true
}

const handleBatchSubmit = async () => {
  if (!batchFormRef.value) return
  await batchFormRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      if (currentBatch.value) {
        await updateBatch(currentBatch.value.id, batchForm)
        ElMessage.success('批次更新成功')
      } else {
        await createBatch(batchForm)
        ElMessage.success('批次创建成功')
      }
      batchDialogVisible.value = false
      fetchBatches()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '操作失败')
    }
  })
}

const handleViewBatch = (row) => {
  recordSearchForm.batch_id = row.id
  activeTab.value = 'record'
  fetchRecords()
}

const handleImport = (row) => {
  currentBatch.value = row
  importFile.value = null
  importDialogVisible.value = true
}

const handleFileChange = (file) => {
  importFile.value = file.raw
}

const handleFileRemove = () => {
  importFile.value = null
}

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
}

const confirmImport = async () => {
  if (!importFile.value) {
    ElMessage.warning('请选择要导入的文件')
    return
  }
  importing.value = true
  try {
    await importRecords(currentBatch.value.id, importFile.value)
    ElMessage.success('导入成功')
    importDialogVisible.value = false
    fetchBatches()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '导入失败')
  } finally {
    importing.value = false
  }
}

const handleStartBatch = async (row) => {
  ElMessageBox.confirm('确定要开始该批次的复核吗？', '确认', {
    type: 'warning'
  }).then(async () => {
    try {
      await updateBatch(row.id, { status: 2 })
      ElMessage.success('已开始复核')
      fetchBatches()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '操作失败')
    }
  }).catch(() => {})
}

const handleCompleteBatch = async (row) => {
  ElMessageBox.confirm('确定要完成该批次的复核吗？完成后将更新相关住户的资格状态。', '确认', {
    type: 'warning'
  }).then(async () => {
    try {
      await updateBatch(row.id, { status: 3 })
      ElMessage.success('批次已完成，住户资格状态已更新')
      fetchBatches()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '操作失败')
    }
  }).catch(() => {})
}

const handleDeleteBatch = (row) => {
  ElMessageBox.confirm('确定要删除该批次吗？删除后将无法恢复。', '确认', {
    type: 'warning'
  }).then(async () => {
    try {
      await deleteBatch(row.id)
      ElMessage.success('删除成功')
      fetchBatches()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '删除失败')
    }
  }).catch(() => {})
}

const handleViewRecord = async (row) => {
  try {
    currentRecord.value = row
    recordDetailVisible.value = true
  } catch (error) {
    ElMessage.error('获取详情失败')
  }
}

const handlePassRecord = (row) => {
  reviewMode.value = 'pass'
  currentRecord.value = row
  reviewForm.items = [
    { name: '收入标准', passed: true, remark: '' },
    { name: '住房情况', passed: true, remark: '' },
    { name: '家庭人口', passed: true, remark: '' },
    { name: '资产状况', passed: true, remark: '' }
  ]
  reviewForm.review_remark = ''
  reviewDialogVisible.value = true
}

const handleFailRecord = (row) => {
  reviewMode.value = 'fail'
  currentRecord.value = row
  reviewForm.items = [
    { name: '收入标准', passed: false, remark: '' },
    { name: '住房情况', passed: true, remark: '' },
    { name: '家庭人口', passed: true, remark: '' },
    { name: '资产状况', passed: true, remark: '' }
  ]
  reviewForm.review_remark = ''
  reviewDialogVisible.value = true
}

const confirmReview = async () => {
  if (!reviewFormRef.value) return
  await reviewFormRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      const data = {
        review_remark: reviewForm.review_remark,
        items: reviewForm.items
      }
      if (reviewMode.value === 'pass') {
        await passRecord(currentRecord.value.id, data)
        ElMessage.success('复核通过')
      } else {
        await failRecord(currentRecord.value.id, data)
        ElMessage.success('复核不通过已记录')
      }
      reviewDialogVisible.value = false
      recordDetailVisible.value = false
      fetchRecords()
      fetchBatches()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '操作失败')
    }
  })
}

const handleBatchPass = () => {
  const ids = selectedRecords.value.map(r => r.id)
  ElMessageBox.confirm(`确定要将选中的 ${ids.length} 条记录批量设为通过吗？`, '确认', {
    type: 'warning'
  }).then(async () => {
    try {
      await batchPass(ids, { review_remark: '批量复核通过' })
      ElMessage.success('批量通过成功')
      fetchRecords()
      fetchBatches()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '操作失败')
    }
  }).catch(() => {})
}

const handleBatchFail = () => {
  const ids = selectedRecords.value.map(r => r.id)
  ElMessageBox.confirm(`确定要将选中的 ${ids.length} 条记录批量设为不通过吗？`, '确认', {
    type: 'warning'
  }).then(async () => {
    try {
      await batchFail(ids, { review_remark: '批量复核不通过' })
      ElMessage.success('批量不通过成功')
      fetchRecords()
      fetchBatches()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '操作失败')
    }
  }).catch(() => {})
}

onMounted(() => {
  fetchBatches()
  fetchAllBatches()
  fetchRecords()
})
</script>

<style scoped>
.qualification-page {
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

.batch-stats {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: 12px;
}

.batch-stats .pass {
  color: #67c23a;
}

.batch-stats .fail {
  color: #f56c6c;
}

.resident-info .name {
  font-weight: 500;
}

.resident-info .id-card {
  font-size: 12px;
  color: #909399;
}

.check-items {
  display: flex;
  flex-wrap: wrap;
}

.pagination-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}

.import-area {
  margin-bottom: 16px;
}

.file-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #f5f7fa;
  border-radius: 4px;
}

.file-size {
  font-size: 12px;
  color: #909399;
}

.check-items-detail {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.check-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  background: #f5f7fa;
  border-radius: 4px;
}

.check-item .item-name {
  font-weight: 500;
  min-width: 100px;
}

.check-item .item-remark {
  font-size: 12px;
  color: #909399;
}

.review-item {
  margin-bottom: 12px;
}
</style>
