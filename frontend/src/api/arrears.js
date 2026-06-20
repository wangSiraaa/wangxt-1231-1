import request from './request'

export function getArrears(params) {
  return request({
    url: '/arrears',
    method: 'get',
    params
  })
}

export function getArrear(id) {
  return request({
    url: `/arrears/${id}`,
    method: 'get'
  })
}

export function createArrear(data) {
  return request({
    url: '/arrears',
    method: 'post',
    data
  })
}

export function updateArrear(id, data) {
  return request({
    url: `/arrears/${id}`,
    method: 'put',
    data
  })
}

export function deleteArrear(id) {
  return request({
    url: `/arrears/${id}`,
    method: 'delete'
  })
}

export function recordPayment(id, data) {
  return request({
    url: `/arrears/${id}/payment`,
    method: 'post',
    data
  })
}

export function getPaymentRecords(arrearId) {
  return request({
    url: `/arrears/${arrearId}/payments`,
    method: 'get'
  })
}

export function getArrearsSummary() {
  return request({
    url: '/arrears/summary',
    method: 'get'
  })
}
