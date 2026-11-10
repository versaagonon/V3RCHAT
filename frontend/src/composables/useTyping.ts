import { onBeforeUnmount, ref } from 'vue'
import { useConfigStore } from '../stores/config'
import { useContactsStore } from '../stores/contacts'
import { useConversationsStore } from '../stores/conversations'

const TYPING_STOP_MS = 1800

export function useTyping(conversationId: () => string | null) {
  const configStore = useConfigStore()
  const contactsStore = useContactsStore()
  const conversationsStore = useConversationsStore()
  const stopTimer = ref<number | null>(null)
  const isTyping = ref(false)

  function clearTimer() {
    if (stopTimer.value !== null) {
      window.clearTimeout(stopTimer.value)
      stopTimer.value = null
    }
  }

  function canSendTyping(): boolean {
    const id = conversationId()
    if (!id) {
      return false
    }

    const conversation = conversationsStore.activeConversation
    if (!conversation || conversation.id !== id) {
      return true
    }

    if (conversation.attributes.conversation_type === 'direct') {
      const otherUser = conversation.relationships.other_user
      if (otherUser && contactsStore.isMessagingBlocked(otherUser.id)) {
        return false
      }
    }

    return true
  }

  async function sendTypingState(next: boolean) {
    const id = conversationId()
    if (!id || !configStore.api || !canSendTyping()) {
      return
    }

    try {
      await configStore.api.sendTyping(id, next)
    } catch {
    }
  }

  let lastTypingSentAt = 0

  function notifyTyping() {
    if (!canSendTyping()) {
      return
    }

    const now = Date.now()
    if (!isTyping.value || now - lastTypingSentAt > 3000) {
      isTyping.value = true
      lastTypingSentAt = now
      void sendTypingState(true)
    }

    clearTimer()
    stopTimer.value = window.setTimeout(() => {
      isTyping.value = false
      void sendTypingState(false)
    }, TYPING_STOP_MS)
  }

  function stopTyping(options?: { silent?: boolean }) {
    clearTimer()
    if (isTyping.value) {
      isTyping.value = false
      if (!options?.silent) {
        void sendTypingState(false)
      }
    }
  }

  onBeforeUnmount(stopTyping)

  return {
    notifyTyping,
    stopTyping,
  }
}
