import request from './request'

export function getLeases(params) {
  return request({
    url: '/leases',
    method: 'get',
    params
  })
}

export function getLease(id) {
  return request({
    url: `/leases/${id}`,
    method: 'get'
  })
}

export function createLease(data) {
  return request({
    url: '/leases',
    method: 'post',
    data
  })
}

export function updateLease(id, data) {
  return request({
    url: `/leases/${id}`,
    method: 'put',
    data
  })
}

export function deleteLease(id) {
  return request({
    url: `/leases/${id}`,
    method: 'delete'
  })
}

export function terminateLease(id, data) {
  return request({
    url: `/leases/${id}/terminate`,
    method: 'post',
    data
  })
}

export function generateBills(id, data) {
  return request({
    url: `/leases/${id}/generate-bills`,
    method: 'post',
    data
  })
}

export function batchGenerateBills(data) {
  return request({
    url: '/leases/batch-generate-bills',
    method: 'post',
    data
  })
}

export function getRenewalApplications(params) {
  return request({
    url: '/lease-renewals',
    method: 'get',
    params
  })
}

export function approveRenewal(id, data) {
  return request({
    url: `/lease-renewals/${id}/approve`,
    method: 'post',
    data
  })
}

export function rejectRenewal(id, data) {
  return request({
    url: `/lease-renewals/${id}/reject`,
    method: 'post',
    data
  })
}

export function submitRenewalApplication(data) {
  return request({
    url: '/lease-renewals',
    method: 'post',
    data
  })
}
