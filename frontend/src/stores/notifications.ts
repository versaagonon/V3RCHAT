import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { ChatifyNotification } from '../types'
import { useConfigStore } from './config'
import { useConversationsStore } from './conversations'
import { useToastStore } from './toast'
import { useUiStore } from './ui'

export const useNotificationsStore = defineStore('notifications', () => {
  const configStore = useConfigStore()
  const uiStore = useUiStore()
  const conversationsStore = useConversationsStore()
  const toastStore = useToastStore()

  const notifications = ref<ChatifyNotification[]>([])
  const loading = ref(false)

  const unreadInfoCount = computed(() =>
    notifications.value.filter((n) => n.type === 'information' && n.status === 'unread').length,
  )

  const pendingRequestsCount = computed(() =>
    notifications.value.filter((n) => n.type === 'request' && n.status === 'pending').length,
  )

  const totalBadgeCount = computed(() => unreadInfoCount.value + pendingRequestsCount.value)

  async function fetchNotifications() {
    if (!configStore.api) return
    loading.value = true
    try {
      const res = await configStore.api.getNotifications()
      notifications.value = res.data.data
    } catch (err) {
      console.error('Failed to fetch notifications', err)
    } finally {
      loading.value = false
    }
  }

  async function markRead(id: string) {
    if (!configStore.api) return
    try {
      await configStore.api.markNotificationRead(id)
      const target = notifications.value.find((n) => n.id === id)
      if (target) {
        target.status = 'read'
      }
    } catch (err) {
      console.error('Failed to mark notification as read', err)
    }
  }

  async function markAllRead() {
    if (!configStore.api) return
    try {
      await configStore.api.markAllNotificationsRead()
      for (const n of notifications.value) {
        if (n.type === 'information') {
          n.status = 'read'
        }
      }
    } catch (err) {
      console.error('Failed to mark all as read', err)
    }
  }

  async function confirmRequest(id: string) {
    if (!configStore.api) return null
    try {
      const res = await configStore.api.confirmChatRequest(id)
      const target = notifications.value.find((n) => n.id === id)
      if (target) {
        target.status = 'accepted'
      }

      // The request card is terminal once accepted: drop it from the local list so
      // the sidebar/badge collapse immediately, then resync with the server.
      notifications.value = notifications.value.filter(
        (n) => !(n.type === 'request' && n.status !== 'pending'),
      )

      // Refresh conversations so new chat appears in list
      await conversationsStore.fetchAll()

      if (res.data.conversation_id) {
        await conversationsStore.select(res.data.conversation_id)
        uiStore.closeModal()
        uiStore.showThread()
      }

      void fetchNotifications()

      return res.data
    } catch (err) {
      console.error('Failed to confirm chat request', err)
      return null
    }
  }

  async function deleteRequest(id: string) {
    if (!configStore.api) return false
    try {
      const target = notifications.value.find((n) => String(n.id) === String(id))
      const convId = target?.data?.conversation_id
      await configStore.api.deleteChatRequest(id)
      notifications.value = notifications.value.filter((n) => String(n.id) !== String(id))
      if (convId) {
        conversationsStore.removeConversation(String(convId))
      }
      return true
    } catch (err) {
      console.error('Failed to delete chat request', err)
      return false
    }
  }

  async function removeNotification(id: string) {
    if (!configStore.api) return false
    try {
      await configStore.api.deleteNotification(id)
      notifications.value = notifications.value.filter((n) => n.id !== id)
      return true
    } catch (err) {
      console.error('Failed to remove notification', err)
      return false
    }
  }

  async function removeAllNotifications() {
    if (!configStore.api) return false
    try {
      await configStore.api.deleteAllNotifications()
      notifications.value = []
      // Pending requests hide their conversations server-side; resync the inbox.
      await conversationsStore.fetchAll()
      return true
    } catch (err) {
      console.error('Failed to remove all notifications', err)
      return false
    }
  }

  function handleIncomingNotification(payload: any) {
    if (!payload) return
    const incoming: ChatifyNotification = payload.notification || (payload.id && payload.type ? payload : null)
    if (!incoming) return

    const exists = notifications.value.some((n) => n.id === incoming.id)
    if (!exists) {
      notifications.value = [incoming, ...notifications.value]
    } else {
      const idx = notifications.value.findIndex((n) => n.id === incoming.id)
      if (idx !== -1) {
        notifications.value[idx] = incoming
      }
    }

    toastStore.show({
      message: incoming.title || (incoming.type === 'request' ? 'New chat request received' : 'New notification received'),
      icon: 'info',
      placement: 'top',
      duration: 4000,
    })
  }

  return {
    notifications,
    loading,
    unreadInfoCount,
    pendingRequestsCount,
    totalBadgeCount,
    fetchNotifications,
    handleIncomingNotification,
    markRead,
    markAllRead,
    confirmRequest,
    deleteRequest,
    removeNotification,
    removeAllNotifications,
  }
})
