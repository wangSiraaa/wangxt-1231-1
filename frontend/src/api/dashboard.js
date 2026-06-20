import request from './request'

export function getStats() {
  return request({
    url: '/dashboard/stats',
    method: 'get'
  })
}

export function getRecentActivities() {
  return request({
    url: '/dashboard/activities',
    method: 'get'
  })
}
