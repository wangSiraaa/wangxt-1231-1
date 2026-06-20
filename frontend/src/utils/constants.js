export const MAINTENANCE_STATUS = {
  1: { label: '待派单', type: 'warning', class: 'status-pending' },
  2: { label: '待接单', type: 'warning', class: 'status-pending' },
  3: { label: '维修中', type: 'primary', class: 'status-processing' },
  4: { label: '待结案', type: 'warning', class: 'status-pending' },
  5: { label: '已结案', type: 'success', class: 'status-completed' },
  6: { label: '已取消', type: 'info', class: 'status-cancelled' }
}

export const MAINTENANCE_TYPE = {
  1: { label: '普通维修', type: 'info' },
  2: { label: '紧急维修', type: 'danger' },
  3: { label: '定期保养', type: 'success' }
}

export const MAINTENANCE_URGENCY = {
  1: { label: '低', type: 'info' },
  2: { label: '中', type: 'warning' },
  3: { label: '高', type: 'danger' }
}

export const QUALIFICATION_RESULT = {
  0: { label: '待复核', type: 'warning', class: 'status-pending' },
  1: { label: '复核通过', type: 'success', class: 'status-completed' },
  2: { label: '复核不通过', type: 'danger', class: 'status-failed' }
}

export const QUALIFICATION_BATCH_STATUS = {
  1: { label: '待导入', type: 'warning', class: 'status-pending' },
  2: { label: '复核中', type: 'primary', class: 'status-processing' },
  3: { label: '已完成', type: 'success', class: 'status-completed' }
}

export const LEASE_STATUS = {
  1: { label: '有效', type: 'success', class: 'status-completed' },
  2: { label: '已到期', type: 'warning', class: 'status-pending' },
  3: { label: '已终止', type: 'danger', class: 'status-failed' },
  4: { label: '续租中', type: 'primary', class: 'status-processing' }
}

export const LEASE_RENEWAL_STATUS = {
  0: { label: '待审核', type: 'warning', class: 'status-pending' },
  1: { label: '审核通过', type: 'success', class: 'status-completed' },
  2: { label: '审核不通过', type: 'danger', class: 'status-failed' }
}

export const ARREARS_STATUS = {
  1: { label: '未缴清', type: 'danger', class: 'status-failed' },
  2: { label: '部分缴纳', type: 'warning', class: 'status-pending' },
  3: { label: '已缴清', type: 'success', class: 'status-completed' }
}

export const PAYMENT_METHOD = {
  1: '现金',
  2: '银行转账',
  3: '微信支付',
  4: '支付宝',
  5: 'POS机刷卡'
}

export const RESIDENT_STATUS = {
  1: { label: '正常居住', type: 'success', class: 'status-completed' },
  2: { label: '资格待复核', type: 'warning', class: 'status-pending' },
  3: { label: '资格未通过', type: 'danger', class: 'status-failed' },
  4: { label: '已搬离', type: 'info', class: 'status-cancelled' }
}

export const GENDER = {
  1: '男',
  2: '女'
}

export const RELATIONSHIP = {
  1: '配偶',
  2: '子女',
  3: '父母',
  4: '兄弟姐妹',
  5: '其他'
}

export function formatDate(date) {
  if (!date) return '-'
  const d = new Date(date)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

export function formatDateTime(date) {
  if (!date) return '-'
  const d = new Date(date)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day} ${hours}:${minutes}`
}

export function formatCurrency(amount) {
  if (amount === null || amount === undefined || isNaN(amount)) return '-'
  return `¥${Number(amount).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

export function getStatusLabel(status, statusMap) {
  return statusMap[status]?.label || '未知'
}

export function getStatusType(status, statusMap) {
  return statusMap[status]?.type || 'info'
}

export function getStatusClass(status, statusMap) {
  return statusMap[status]?.class || 'status-pending'
}
