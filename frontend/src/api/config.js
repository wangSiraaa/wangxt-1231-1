import request from './request'

export function getConfigs() {
  return request({
    url: '/system-configs',
    method: 'get'
  })
}

export function getConfig(key) {
  return request({
    url: `/system-configs/${key}`,
    method: 'get'
  })
}

export function updateConfig(key, data) {
  return request({
    url: `/system-configs/${key}`,
    method: 'put',
    data
  })
}

export function batchUpdateConfigs(data) {
  return request({
    url: '/system-configs/batch',
    method: 'post',
    data
  })
}

export function getArrearsThreshold() {
  return request({
    url: '/system-configs/arrears-threshold',
    method: 'get'
  })
}
