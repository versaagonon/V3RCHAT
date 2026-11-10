<script setup lang="ts">
import { computed, onMounted, onUnmounted, provide, watch } from 'vue'
import type Echo from 'laravel-echo'
import type {
  BootConfig,
  ChatifyConversation,
  ChatifyMessage,
  ConversationReadPayload,
  GroupMembershipRevokedPayload,
  GroupParticipantsChangedPayload,
  MessageDeletedPayload,
  UserPresenceChangedPayload,
  UserBlockChangedPayload,
  UserTypingPayload,
} from '../types'
import { bindEchoConnectionState, bindEchoDisconnect, bindEchoReconnect, subscribeToConversation, subscribeToUserInbox, useEcho } from '../composables/useEcho'
import { useConversationRouting } from '../composables/useConversationRouting'
import { createTypingSoundPlayer, useChatSounds } from '../composables/useChatSounds'
import { CONVERSATION_ROUTING_KEY } from '../constants/dom'
import { usePresence } from '../composables/usePresence'
import { useBreakpoints } from '../composables/useBreakpoints'
import { tryPlayIncomingMessageSound, resolveMessageSenderId } from '../utils/chatSounds'
import { useConfigStore } from '../stores/config'
import { useConnectionStore } from '../stores/connection'
import { useContactsStore } from '../stores/contacts'
import { useConversationsStore } from '../stores/conversations'
import { useMessagesStore } from '../stores/messages'
import { usePresenceStore } from '../stores/presence'
import { useTypingStore } from '../stores/typing'
import { useUiStore } from '../stores/ui'
import AuthPage from './auth/AuthPage.vue'
import LandingPage from './landing/LandingPage.vue'
import ChatifyApp from './layout/ChatifyApp.vue'
import ContactInfoModal from './modals/ContactInfoModal.vue'
import CreateGroupModal from './modals/CreateGroupModal.vue'
import ForwardMessageModal from './modals/ForwardMessageModal.vue'
import GroupInfoModal from './modals/GroupInfoModal.vue'
import NewChatModal from './modals/NewChatModal.vue'
import SettingsModal from './modals/SettingsModal.vue'
import ConfirmDialog from './ui/ConfirmDialog.vue'
import ToastHost from './ui/ToastHost.vue'

const props = defineProps<{
  config: BootConfig
}>()

const isLandingMode = computed(() => props.config.authMode === 'landing')
const isAuthMode = computed(() => !isLandingMode.value && (!props.config.user || Boolean(props.config.authMode)))
const isExternalView = computed(() => isLandingMode.value || isAuthMode.value)

const configStore = useConfigStore()
const connectionStore = useConnectionStore()
const conversationsStore = useConversationsStore()
const messagesStore = useMessagesStore()
const contactsStore = useContactsStore()
const presenceStore = usePresenceStore()
const typingStore = useTypingStore()
const uiStore = useUiStore()
const { isMobile } = useBreakpoints()

const { sendOffline: sendPresenceOffline } = usePresence()

const { play: playChatSound } = useChatSounds()
const playTypingSound = createTypingSoundPlayer(playChatSound)
const conversationRouting = useConversationRouting()

provide(CONVERSATION_ROUTING_KEY, conversationRouting)

const echo = useEcho(props.config)
let unsubscribeConversation: (() => void) | null = null
let unsubscribeInbox: (() => void) | null = null
let unsubscribeReconnect: (() => void) | null = null
let unsubscribeDisconnect: (() => void) | null = null
let unsubscribeConnectionState: (() => void) | null = null
let resyncTimer: number | null = null

connectionStore.setBroadcastEnabled(configStore.broadcastEnabled)
connectionStore.setBrowserOnline(typeof navigator !== 'undefined' ? navigator.onLine : true)

async function resyncAfterReconnect() {
  connectionStore.beginUpdating()

  try {
    await Promise.all([
      conversationsStore.fetchAll(),
      contactsStore.fetchFavorites(),
      contactsStore.fetchBlocked(),
    ])
    seedPresenceFromConversations()

    if (conversationsStore.activeId) {
      await messagesStore.fetchMessages(conversationsStore.activeId, true)
    }
  } finally {
    connectionStore.finishUpdating()
  }
}

function onBrowserOnline() {
  connectionStore.setBrowserOnline(true)
  void resyncAfterReconnect()
}

function onBrowserOffline() {
  connectionStore.setBrowserOnline(false)
}

function seedPresenceFromConversations() {
  if (!configStore.showOnlineStatus) {
    return
  }

  conversationsStore.items.forEach((conversation) => {
    const otherUser = conversation.relationships.other_user
    if (otherUser?.attributes.is_online !== undefined) {
      presenceStore.setOnline(otherUser.id, otherUser.attributes.is_online)
    }
  })
}

function normalizeInboxPayload(payload: unknown): ChatifyConversation | null {
  if (!payload || typeof payload !== 'object') {
    return null
  }

  const record = payload as Record<string, unknown>

  if (record.type === 'conversation' && record.id != null) {
    return payload as ChatifyConversation
  }

  if (record.data && typeof record.data === 'object') {
    return normalizeInboxPayload(record.data)
  }

  return null
}

function scheduleResyncAfterReconnect() {
  if (resyncTimer !== null) {
    window.clearTimeout(resyncTimer)
  }

  resyncTimer = window.setTimeout(() => {
    resyncTimer = null
    void resyncAfterReconnect()
  }, 400)
}

function playIncomingSoundForMessage(message: ChatifyMessage) {
  if (!props.config.user || message.attributes.kind === 'system') {
    return
  }

  const senderId = resolveMessageSenderId(message)
  if (!senderId) {
    return
  }

  tryPlayIncomingMessageSound({
    messageId: message.id,
    conversationId: message.attributes.conversation_id,
    senderId,
    currentUserId: props.config.user.id,
    activeConversationId: conversationsStore.activeId,
    isSenderBlocked: contactsStore.isMessagingBlocked(senderId),
  })
}

function playIncomingSoundForInbox(conversation: ChatifyConversation) {
  if (!props.config.user) {
    return
  }

  const lastMessage = conversation.relationships.last_message
  if (!lastMessage || lastMessage.attributes.kind === 'system') {
    return
  }

  const senderId = resolveMessageSenderId(lastMessage)
  if (!senderId) {
    return
  }

  tryPlayIncomingMessageSound({
    messageId: lastMessage.id,
    conversationId: conversation.id,
    senderId,
    currentUserId: props.config.user.id,
    activeConversationId: conversationsStore.activeId,
    isSenderBlocked: contactsStore.isMessagingBlocked(senderId),
  })
}

function handleInboxUpdated(payload: unknown) {
  if (!props.config.user) {
    return
  }

  const conversation = normalizeInboxPayload(payload)
  if (!conversation) {
    return
  }

  conversationsStore.handleInboxUpdate(conversation, props.config.user.id)

  const isThreadVisible = !isMobile.value || uiStore.showThreadOnMobile
  const isCurrentActive = String(conversationsStore.activeId) === String(conversation.id)

  const lastMessage = conversation.relationships.last_message
  if (
    lastMessage
    && lastMessage.attributes.kind !== 'system'
    && isCurrentActive
    && isThreadVisible
  ) {
    messagesStore.handleMessageSent(lastMessage)
    void conversationsStore.markRead(conversation.id)
  }

  playIncomingSoundForInbox(conversation)
}

function setupInboxSubscription() {
  if (!props.config.user) {
    return
  }

  const currentUser = props.config.user
  unsubscribeInbox?.()
  unsubscribeInbox = subscribeToUserInbox(
    echo as Echo<'pusher'> | null,
    currentUser.id,
    {
      onInboxUpdated: handleInboxUpdated,
      onGroupMembershipRevoked: (payload) => {
        const data = payload as GroupMembershipRevokedPayload
        if (String(data.user_id) !== String(currentUser.id)) {
          return
        }

        if (conversationsStore.activeId === data.conversation_id) {
          messagesStore.clearConversation(data.conversation_id)
          conversationsStore.clearActive()
        }

        conversationsStore.handleMembershipRevoked(data.conversation_id)
        uiStore.closeModal()
      },
      onUserPresenceChanged: (payload) => {
        const data = payload as UserPresenceChangedPayload
        if (!configStore.showOnlineStatus) {
          return
        }

        presenceStore.setOnline(data.user_id, data.is_online)
      },
      onUserBlockChanged: (payload) => {
        contactsStore.handleUserBlockChanged(payload as UserBlockChangedPayload, currentUser.id)
      },
    },
  )
}

function bindEcho(conversationId: string | null) {
  unsubscribeConversation?.()
  unsubscribeConversation = null

  if (!conversationId || !props.config.user) {
    return
  }

  const currentUserId = String(props.config.user.id)

  unsubscribeConversation = subscribeToConversation(echo as Echo<'pusher'> | null, conversationId, {
    onMessageSent: (payload) => {
      const message = payload as ChatifyMessage
      messagesStore.handleMessageSent(message)
      playIncomingSoundForMessage(message)
    },
    onMessageUpdated: (payload) => messagesStore.handleMessageUpdated(payload as ChatifyMessage),
    onMessageDeleted: (payload) => messagesStore.handleMessageDeleted(payload as MessageDeletedPayload),
    onConversationRead: (payload) => {
      const data = payload as ConversationReadPayload
      if (String(data.user_id) !== currentUserId) {
        messagesStore.byConversation[data.conversation_id]?.items.forEach((message) => {
          if (String(message.relationships.sender.data.id) === currentUserId) {
            message.attributes.read = true
          }
        })
      }
    },
    onGroupParticipantsChanged: (payload) => {
      const data = payload as GroupParticipantsChangedPayload
      conversationsStore.updateParticipants(data.conversation_id, {
        participant_count: data.participant_count,
        participants: data.participants,
        participants_preview: data.participants_preview,
      })
    },
    onUserTyping: (payload) => {
      const data = payload as UserTypingPayload
      if (contactsStore.isMessagingBlocked(data.user_id)) {
        return
      }

      typingStore.setTyping(data.conversation_id, data.user_id, data.is_typing)

      if (
        data.is_typing
        && conversationsStore.activeId === data.conversation_id
        && String(data.user_id) !== currentUserId
      ) {
        playTypingSound()
      }
    },
  })
}

function onVisibilityChange() {
  if (document.visibilityState === 'visible') {
    scheduleResyncAfterReconnect()
  }
}

onMounted(async () => {
  if (isExternalView.value) {
    return
  }

  setupInboxSubscription()

  unsubscribeReconnect = bindEchoReconnect(echo as Echo<'pusher'> | null, () => {
    setupInboxSubscription()
    bindEcho(conversationsStore.activeId)
    void resyncAfterReconnect()
  })

  unsubscribeDisconnect = bindEchoDisconnect(echo as Echo<'pusher'> | null, () => {
    void sendPresenceOffline(true)
  })

  unsubscribeConnectionState = bindEchoConnectionState(echo as Echo<'pusher'> | null, (state) => {
    connectionStore.setPusherState(state.current)
  })

  window.addEventListener('online', onBrowserOnline)
  window.addEventListener('offline', onBrowserOffline)
  document.addEventListener('visibilitychange', onVisibilityChange)

  await Promise.all([
    conversationsStore.fetchAll(),
    contactsStore.fetchFavorites(),
    contactsStore.fetchBlocked(),
  ])

  seedPresenceFromConversations()

  if (props.config.conversationId) {
    await conversationsStore.select(props.config.conversationId)
    await messagesStore.fetchMessages(props.config.conversationId, true)
    bindEcho(props.config.conversationId)
  }
})

onUnmounted(() => {
  if (isExternalView.value) {
    return
  }

  if (resyncTimer !== null) {
    window.clearTimeout(resyncTimer)
    resyncTimer = null
  }

  unsubscribeConversation?.()
  unsubscribeInbox?.()
  unsubscribeReconnect?.()
  unsubscribeDisconnect?.()
  unsubscribeConnectionState?.()
  window.removeEventListener('online', onBrowserOnline)
  window.removeEventListener('offline', onBrowserOffline)
  document.removeEventListener('visibilitychange', onVisibilityChange)
})

watch(
  () => conversationsStore.activeId,
  async (id, previousId) => {
    if (isExternalView.value) {
      return
    }

    if (previousId) {
      typingStore.clearConversation(previousId)
    }

    bindEcho(id)

    if (id) {
      await messagesStore.fetchMessages(id, true)
      await conversationsStore.markRead(id)
    }
  },
)
</script>

<template>
  <LandingPage
    v-if="isLandingMode"
    :user="props.config.user"
    :login-url="props.config.loginUrl ?? '/login'"
    :register-url="props.config.registerUrl ?? '/register'"
    :dashboard-url="props.config.webBase ?? '/v3rchat'"
    :app-name="props.config.appName ?? 'V3RCHAT'"
  />
  <AuthPage
    v-else-if="isAuthMode"
    :initial-mode="props.config.authMode === 'register' ? 'register' : 'login'"
    :csrf-token="props.config.csrfToken"
    :login-url="props.config.loginUrl ?? '/login'"
    :register-url="props.config.registerUrl ?? '/register'"
    :dashboard-url="props.config.webBase ?? '/v3rchat'"
    :app-name="props.config.appName ?? 'V3RCHAT'"
  />
  <template v-else>
    <ChatifyApp />
    <ContactInfoModal />
    <GroupInfoModal />
    <CreateGroupModal />
    <NewChatModal />
    <ForwardMessageModal />
    <SettingsModal />
    <ConfirmDialog />
    <ToastHost />
  </template>
</template>
