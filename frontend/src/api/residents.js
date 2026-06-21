import request from './request'

export function getResidents(params) {
  return request({
    url: '/residents',
    method: 'get',
    params
  })
}

export function getResident(id) {
  return request({
    url: `/residents/${id}`,
    method: 'get'
  })
}

export function createResident(data) {
  return request({
    url: '/residents',
    method: 'post',
    data
  })
}

export function updateResident(id, data) {
  return request({
    url: `/residents/${id}`,
    method: 'put',
    data
  })
}

export function deleteResident(id) {
  return request({
    url: `/residents/${id}`,
    method: 'delete'
  })
}

export function getResidentSummary(id) {
  return request({
    url: `/residents/${id}/arrears-summary`,
    method: 'get'
  })
}

export function getResidentQualification(id) {
  return request({
    url: `/residents/${id}/qualification-status`,
    method: 'get'
  })
}

export function getFamilyMembers(residentId) {
  return request({
    url: `/residents/${residentId}/family`,
    method: 'get'
  })
}

export function addFamilyMember(residentId, data) {
  return request({
    url: `/residents/${residentId}/family`,
    method: 'post',
    data
  })
}

export function updateFamilyMember(residentId, memberId, data) {
  return request({
    url: `/residents/${residentId}/family-members/${memberId}`,
    method: 'put',
    data
  })
}

export function deleteFamilyMember(residentId, memberId) {
  return request({
    url: `/resident-family/${memberId}`,
    method: 'delete'
  })
}

export function getResidentLeases(id) {
  return request({
    url: `/residents/${id}/leases`,
    method: 'get'
  })
}

export function getResidentArrears(id, params) {
  return request({
    url: '/arrears',
    method: 'get',
    params: { resident_id: id, ...params }
  })
}

export function getResidentMaintenanceOrders(id, limit) {
  return request({
    url: `/residents/${id}/maintenance-orders`,
    method: 'get',
    params: { limit: limit || 5 }
  })
}

export function getResidentQualificationRecords(id) {
  return request({
    url: `/residents/${id}/qualification-records`,
    method: 'get'
  })
}

export function recordResidentPayment(residentId, data) {
  return request({
    url: `/residents/${residentId}/payment`,
    method: 'post',
    data
  })
}
