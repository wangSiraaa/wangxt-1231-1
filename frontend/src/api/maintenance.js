import request from './request'

export function getMaintenanceOrders(params) {
  return request({
    url: '/maintenance-orders',
    method: 'get',
    params
  })
}

export function getMaintenanceOrder(id) {
  return request({
    url: `/maintenance-orders/${id}`,
    method: 'get'
  })
}

export function createMaintenanceOrder(data) {
  return request({
    url: '/maintenance-orders',
    method: 'post',
    data
  })
}

export function updateMaintenanceOrder(id, data) {
  return request({
    url: `/maintenance-orders/${id}`,
    method: 'put',
    data
  })
}

export function assignOrder(id, data) {
  return request({
    url: `/maintenance-orders/${id}/assign`,
    method: 'post',
    data
  })
}

export function acceptOrder(id) {
  return request({
    url: `/maintenance-orders/${id}/accept`,
    method: 'post'
  })
}

export function startOrder(id) {
  return request({
    url: `/maintenance-orders/${id}/start`,
    method: 'post'
  })
}

export function completeOrder(id, data) {
  return request({
    url: `/maintenance-orders/${id}/complete`,
    method: 'post',
    data
  })
}

export function closeOrder(id) {
  return request({
    url: `/maintenance-orders/${id}/close`,
    method: 'post'
  })
}

export function cancelOrder(id, data) {
  return request({
    url: `/maintenance-orders/${id}/cancel`,
    method: 'post',
    data
  })
}

export function uploadPhoto(id, file, type = 'completion') {
  const formData = new FormData()
  formData.append('photo', file)
  formData.append('photo_type', type)
  
  return request({
    url: `/maintenance-orders/${id}/photos`,
    method: 'post',
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

export function deletePhoto(orderId, photoId) {
  return request({
    url: `/maintenance-orders/${orderId}/photos/${photoId}`,
    method: 'delete'
  })
}

export function addMaterial(id, data) {
  return request({
    url: `/maintenance-orders/${id}/materials`,
    method: 'post',
    data
  })
}

export function updateMaterial(orderId, materialId, data) {
  return request({
    url: `/maintenance-orders/${orderId}/materials/${materialId}`,
    method: 'put',
    data
  })
}

export function deleteMaterial(orderId, materialId) {
  return request({
    url: `/maintenance-orders/${orderId}/materials/${materialId}`,
    method: 'delete'
  })
}
