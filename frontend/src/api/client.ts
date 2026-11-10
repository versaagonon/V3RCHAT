import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios'
import type {
  AvatarBorder,
  BootConfig,
  ChatifyConversation,
  ChatifyMessage,
  ChatifyParticipant,
  ChatifyUser,
  GroupPermissionKey,
  LinkPreview,
  PaginatedResponse,
  SharedAttachment,
  SingleResponse,
  UserSettings,
} from '../types'

export type ApiClient = AxiosInstance

export function createApiClient(config: BootConfig): ApiClient {
  const client = axios.create({
    baseURL: config.apiBase,
    withCredentials: true,
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': config.csrfToken,
    },
  })

  return client
}

export function createChatifyApi(client: ApiClient) {
  return {
    getConversations(params?: { per_page?: number }) {
      return client.get<PaginatedResponse<ChatifyConversation>>('/conversations', { params })
    },

    getConversation(id: string) {
      return client.get<SingleResponse<ChatifyConversation>>(`/conversations/${id}`)
    },

    createDirectConversation(userId: number | string) {
      return client.post<SingleResponse<ChatifyConversation>>('/conversations/direct', {
        user_id: userId,
      })
    },

    createGroupConversation(name: string, userIds: Array<number | string>) {
      return client.post<SingleResponse<ChatifyConversation>>('/conversations/group', {
        name,
        user_ids: userIds,
      })
    },

    updateConversation(id: string, payload: { name?: string; description?: string | null }) {
      return client.patch<SingleResponse<ChatifyConversation>>(`/conversations/${id}`, payload)
    },

    uploadGroupAvatar(
      id: string,
      file: File,
      config?: Pick<AxiosRequestConfig, 'onUploadProgress'>,
    ) {
      const form = new FormData()
      form.append('avatar', file)
      return client.post<SingleResponse<ChatifyConversation>>(`/conversations/${id}/avatar`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
        ...config,
      })
    },

    deleteConversation(id: string) {
      return client.delete(`/conversations/${id}`)
    },

    hideConversation(id: string) {
      return client.post(`/conversations/${id}/hide`)
    },

    clearConversation(id: string) {
      return client.post<SingleResponse<ChatifyConversation>>(`/conversations/${id}/clear`)
    },

    markConversationRead(id: string) {
      return client.post(`/conversations/${id}/read`)
    },

    pinConversation(id: string, pinned: boolean) {
      return client.patch<SingleResponse<ChatifyConversation>>(`/conversations/${id}/pin`, { pinned })
    },

    updatePinOrder(conversationIds: string[]) {
      return client.put('/conversations/pin-order', { conversation_ids: conversationIds })
    },

    sendTyping(conversationId: string, isTyping: boolean) {
      return client.post(`/conversations/${conversationId}/typing`, { is_typing: isTyping })
    },

    addParticipants(id: string, userIds: Array<number | string>) {
      return client.post<SingleResponse<ChatifyConversation>>(`/conversations/${id}/participants`, {
        user_ids: userIds,
      })
    },

    removeParticipant(conversationId: string, userId: number | string) {
      return client.delete(`/conversations/${conversationId}/participants/${userId}`)
    },

    leaveGroup(id: string) {
      return client.post(`/conversations/${id}/leave`)
    },

    getMembers(conversationId: string, params?: { page?: number; per_page?: number; search?: string }) {
      return client.get<PaginatedResponse<ChatifyParticipant>>(`/conversations/${conversationId}/participants`, {
        params,
      })
    },

    updateMemberRole(
      conversationId: string,
      userId: number | string,
      payload: {
        role: 'admin' | 'moderator' | 'member'
        permissions?: Partial<Record<GroupPermissionKey, boolean>> | null
      },
    ) {
      return client.patch<SingleResponse<ChatifyConversation>>(
        `/conversations/${conversationId}/participants/${userId}`,
        payload,
      )
    },

    transferOwnership(conversationId: string, userId: number | string) {
      return client.post<SingleResponse<ChatifyConversation>>(
        `/conversations/${conversationId}/transfer-ownership`,
        { user_id: userId },
      )
    },

    getMessages(conversationId: string, params?: { per_page?: number; after?: string }) {
      return client.get<PaginatedResponse<ChatifyMessage>>(
        `/conversations/${conversationId}/messages`,
        { params },
      )
    },

    sendMessage(
      conversationId: string,
      payload: { body?: string; attachment?: File; attachments?: File[]; reply_to_message_id?: string },
      config?: AxiosRequestConfig,
    ) {
      const hasFiles = Boolean(payload.attachment || payload.attachments?.length)

      if (!hasFiles) {
        return client.post<SingleResponse<ChatifyMessage>>(
          `/conversations/${conversationId}/messages`,
          {
            body: payload.body,
            reply_to_message_id: payload.reply_to_message_id,
          },
          config,
        )
      }

      const form = new FormData()
      if (payload.body) {
        form.append('body', payload.body)
      }
      if (payload.attachment) {
        form.append('attachment', payload.attachment)
      }
      if (payload.attachments?.length) {
        payload.attachments.forEach((file) => form.append('attachments[]', file))
      }
      if (payload.reply_to_message_id) {
        form.append('reply_to_message_id', payload.reply_to_message_id)
      }
      return client.post<SingleResponse<ChatifyMessage>>(
        `/conversations/${conversationId}/messages`,
        form,
        {
          headers: { 'Content-Type': 'multipart/form-data' },
          ...config,
        },
      )
    },

    updateMessage(id: string, body: string) {
      return client.patch<SingleResponse<ChatifyMessage>>(`/messages/${id}`, { body })
    },

    deleteMessage(id: string, scope: 'me' | 'all' = 'all') {
      return client.delete(`/messages/${id}`, { params: { scope } })
    },

    forwardMessage(conversationId: string, messageId: string) {
      return client.post<SingleResponse<ChatifyMessage>>(`/conversations/${conversationId}/forward`, {
        message_id: messageId,
      })
    },

    searchContacts(q: string, params?: { per_page?: number }) {
      return client.get<PaginatedResponse<ChatifyUser>>('/contacts/search', {
        params: { q, ...params },
      })
    },

    getProfile() {
      return client.get<SingleResponse<ChatifyUser>>('/profile')
    },

    updateProfile(payload: FormData | {
      name?: string
      username?: string | null
      email?: string
      current_password?: string
      reset_avatar?: boolean
    }, config?: AxiosRequestConfig) {
      if (payload instanceof FormData) {
        return client.post<SingleResponse<ChatifyUser>>('/profile', payload, {
          headers: { 'Content-Type': 'multipart/form-data' },
          ...config,
        })
      }
      return client.patch<SingleResponse<ChatifyUser>>('/profile', payload, config)
    },

    getSettings() {
      return client.get<SingleResponse<UserSettings>>('/settings')
    },

    updateSettings(payload: FormData, config?: AxiosRequestConfig) {
      return client.post<SingleResponse<UserSettings>>('/settings/avatar', payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
        ...config,
      })
    },

    patchSettings(payload: {
      dark_mode?: boolean
      active_status?: boolean
      reset_avatar?: boolean
      theme_preferences?: Record<string, unknown>
    }) {
      return client.patch<SingleResponse<UserSettings>>('/settings', payload)
    },

    uploadChatBackground(payload: FormData) {
      return client.post<SingleResponse<UserSettings>>('/settings/chat-background', payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    },

    getFavorites() {
      return client.get<PaginatedResponse<ChatifyUser>>('/favorites')
    },

    toggleFavorite(userId: number | string) {
      return client.post(`/favorites/${userId}`)
    },

    getBlockedUsers() {
      return client.get<PaginatedResponse<ChatifyUser>>('/blocks')
    },

    blockUser(userId: number | string) {
      return client.post(`/blocks/${userId}`)
    },

    unblockUser(userId: number | string) {
      return client.delete(`/blocks/${userId}`)
    },

    sendPresenceHeartbeat() {
      return client.post<{ data: { online: boolean } }>('/presence/heartbeat')
    },

    sendPresenceOffline() {
      return client.post<{ data: { online: boolean } }>('/presence/offline')
    },

    getAttachments(
      conversationId: string,
      params?: { type?: 'media' | 'docs' | 'links'; page?: number; per_page?: number },
    ) {
      return client.get<PaginatedResponse<SharedAttachment>>(`/conversations/${conversationId}/attachments`, {
        params,
      })
    },

    searchMessages(
      conversationId: string,
      q: string,
      params?: { page?: number; per_page?: number },
    ) {
      return client.get<PaginatedResponse<ChatifyMessage>>(
        `/conversations/${conversationId}/messages/search`,
        { params: { q, ...params } },
      )
    },

    fetchLinkPreview(url: string) {
      return client.get<SingleResponse<LinkPreview>>('/link-preview', { params: { url } })
    },

    // Borders & Admin
    getUserBorders() {
      return client.get<{
        data: {
          is_admin: boolean
          active_border_id: number | null
          available_borders: AvatarBorder[]
        }
      }>('/borders')
    },

    equipBorder(borderId: number | null) {
      return client.post<SingleResponse<ChatifyUser>>('/profile/border', { border_id: borderId })
    },

    getAdminBorders() {
      return client.get<{ data: AvatarBorder[] }>('/admin/borders')
    },

    uploadAdminBorder(formData: FormData) {
      return client.post<{ message: string; data: AvatarBorder }>('/admin/borders', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    },

    deleteAdminBorder(id: number) {
      return client.delete<{ message: string }>(`/admin/borders/${id}`)
    },

    giftBorder(payload: { border_id: number; user_query: string }) {
      return client.post<{ message: string; data: Record<string, unknown> }>('/admin/borders/gift', payload)
    },

    revokeBorderGift(payload: { border_id: number; user_id: number }) {
      return client.delete<{ message: string }>('/admin/borders/gift', { data: payload })
    },

    searchAdminUsers(q = '') {
      return client.get<{
        data: Array<{
          id: number
          name: string
          username: string | null
          email: string
          role: string
          borders_count: number
        }>
      }>('/admin/users', { params: { q } })
    },
  }
}

export type ChatifyApi = ReturnType<typeof createChatifyApi>
