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
    url: `/residents/${id}/summary`,
    method: 'get'
  })
}

export function getFamilyMembers(residentId) {
  return request({
    url: `/residents/${residentId}/family-members`,
    method: 'get'
  })
}

export function addFamilyMember(residentId, data) {
  return request({
    url: `/residents/${residentId}/family-members`,
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
    url: `/residents/${residentId}/family-members/${memberId}`,
    method: 'delete'
  })
}
