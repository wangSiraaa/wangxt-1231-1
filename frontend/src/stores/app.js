import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAppStore = defineStore('app', () => {
  const sidebarCollapsed = ref(false)
  const currentPageTitle = ref('')

  const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }

  const setPageTitle = (title) => {
    currentPageTitle.value = title
    document.title = title ? `${title} - 公租房运营平台` : '公租房运营平台'
  }

  return {
    sidebarCollapsed,
    currentPageTitle,
    toggleSidebar,
    setPageTitle
  }
})
