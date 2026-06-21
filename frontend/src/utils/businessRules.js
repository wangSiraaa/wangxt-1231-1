import { ElMessage, ElMessageBox } from 'element-plus'
import { formatCurrency } from './constants'

export function getArrearsThreshold() {
  const saved = localStorage.getItem('arrears_threshold')
  return saved ? Number(saved) : 10000
}

export function checkEmergencyOnly(totalUnpaid) {
  const threshold = getArrearsThreshold()
  return Number(totalUnpaid) > threshold
}

export function validateMaintenanceType(resident, type) {
  if (!resident) return { valid: true }
  
  const totalUnpaid = resident.total_unpaid_amount || 0
  const isEmergencyOnly = checkEmergencyOnly(totalUnpaid)
  
  if (isEmergencyOnly && type !== 2) {
    const threshold = getArrearsThreshold()
    return {
      valid: false,
      message: `该住户欠费金额 ${formatCurrency(totalUnpaid)} 已超过阈值 ${formatCurrency(threshold)}，仅允许登记紧急维修事项`
    }
  }
  
  return { valid: true }
}

export function validateRenewalQualification(resident) {
  if (!resident) return { valid: true }
  
  const latestQualification = resident.latest_qualification_record
  if (!latestQualification) {
    return {
      valid: false,
      message: '该住户暂无资格复核记录，请先完成资格复核'
    }
  }
  
  if (latestQualification.result !== 1) {
    return {
      valid: false,
      message: '该住户资格复核未通过，不能申请续租'
    }
  }
  
  return { valid: true }
}

export function validateCloseOrder(order) {
  if (!order) return { valid: true }
  
  if (order.status !== 4) {
    return {
      valid: false,
      message: '工单状态不正确，当前状态不允许结案'
    }
  }
  
  const hasPhotos = order.has_photos || 
    (order.photos && order.photos.filter(p => p.photo_type === 'completion').length > 0)
  
  if (!hasPhotos) {
    return {
      valid: false,
      message: '请先上传完工照片后再结案'
    }
  }
  
  return { valid: true }
}

export function showEmergencyWarning(resident) {
  if (!resident) return Promise.resolve(true)
  
  const totalUnpaid = resident.total_unpaid_amount || 0
  const isEmergencyOnly = checkEmergencyOnly(totalUnpaid)
  
  if (isEmergencyOnly) {
    const threshold = getArrearsThreshold()
    return ElMessageBox.confirm(
      `该住户欠费金额 ${formatCurrency(totalUnpaid)} 已超过阈值 ${formatCurrency(threshold)}，仅允许登记紧急维修事项，是否继续？`,
      '欠费提醒',
      {
        confirmButtonText: '继续登记紧急维修',
        cancelButtonText: '取消',
        type: 'warning',
        showCancelButton: true
      }
    ).then(() => true).catch(() => false)
  }
  
  return Promise.resolve(true)
}

export function showQualificationWarning(resident) {
  const validation = validateRenewalQualification(resident)
  if (!validation.valid) {
    ElMessage.warning(validation.message)
    return false
  }
  return true
}

export function showCloseOrderWarning(order) {
  const validation = validateCloseOrder(order)
  if (!validation.valid) {
    ElMessage.warning(validation.message)
    return false
  }
  return true
}

export function getStatusBadge(status, statusMap) {
  const info = statusMap[status] || { label: '未知', class: 'status-pending' }
  return {
    label: info.label,
    class: info.class
  }
}

function formatCurrency(amount) {
  if (amount === null || amount === undefined || isNaN(amount)) return '¥0.00'
  return `¥${Number(amount).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

export const businessRules = {
  checkEmergencyOnly,
  validateMaintenanceType,
  validateRenewalQualification,
  validateCloseOrder,
  showEmergencyWarning,
  showQualificationWarning,
  showCloseOrderWarning,
  getArrearsThreshold
}

export default businessRules
