import request from './request'

export function getBatches(params) {
  return request({
    url: '/qualification-batches',
    method: 'get',
    params
  })
}

export function getBatch(id) {
  return request({
    url: `/qualification-batches/${id}`,
    method: 'get'
  })
}

export function createBatch(data) {
  return request({
    url: '/qualification-batches',
    method: 'post',
    data
  })
}

export function updateBatch(id, data) {
  return request({
    url: `/qualification-batches/${id}`,
    method: 'put',
    data
  })
}

export function deleteBatch(id) {
  return request({
    url: `/qualification-batches/${id}`,
    method: 'delete'
  })
}

export function importRecords(batchId, file) {
  const formData = new FormData()
  formData.append('file', file)
  
  return request({
    url: `/qualification-batches/${batchId}/import`,
    method: 'post',
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

export function getRecords(params) {
  return request({
    url: '/qualification-records',
    method: 'get',
    params
  })
}

export function getRecord(id) {
  return request({
    url: `/qualification-records/${id}`,
    method: 'get'
  })
}

export function passRecord(id, data) {
  return request({
    url: `/qualification-records/${id}/pass`,
    method: 'post',
    data
  })
}

export function failRecord(id, data) {
  return request({
    url: `/qualification-records/${id}/fail`,
    method: 'post',
    data
  })
}

export function batchPass(ids, data) {
  return request({
    url: '/qualification-records/batch-pass',
    method: 'post',
    data: { ids, ...data }
  })
}

export function batchFail(ids, data) {
  return request({
    url: '/qualification-records/batch-fail',
    method: 'post',
    data: { ids, ...data }
  })
}
