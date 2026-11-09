import { playChatSound, unlockChatAudio } from '../composables/useChatSounds'
import type { ChatifyMessage } from '../types'

const recentIncomingSoundMessageIds = new Set<string>()
const MAX_RECENT_INCOMING_SOUND_IDS = 200

export function resolveMessageSenderId(message: ChatifyMessage | null | undefined): string | null {
  const senderId = message?.relationships?.sender?.data?.id
  return senderId == null ? null : String(senderId)
}

export function shouldPlayIncomingSound(options: {
  conversationId: string
  senderId: number | string
  currentUserId: number | string
  activeConversationId: string | null
  isSenderBlocked: boolean
}): boolean {
  if (String(options.senderId) === String(options.currentUserId)) {
    return false
  }

  if (options.isSenderBlocked) {
    return false
  }

  if (
    options.activeConversationId === options.conversationId &&
    typeof document !== 'undefined' &&
    document.visibilityState === 'visible'
  ) {
    return false
  }

  return true
}

export function tryPlayIncomingMessageSound(options: {
  messageId: string
  conversationId: string
  senderId: number | string
  currentUserId: number | string
  activeConversationId: string | null
  isSenderBlocked: boolean
}): void {
  if (!shouldPlayIncomingSound(options)) {
    return
  }

  if (recentIncomingSoundMessageIds.has(options.messageId)) {
    return
  }

  recentIncomingSoundMessageIds.add(options.messageId)

  if (recentIncomingSoundMessageIds.size > MAX_RECENT_INCOMING_SOUND_IDS) {
    const [oldest] = recentIncomingSoundMessageIds
    if (oldest) {
      recentIncomingSoundMessageIds.delete(oldest)
    }
  }

  unlockChatAudio()
  playChatSound('incomingMessage')

  if (typeof window !== 'undefined' && 'Notification' in window) {
    if (Notification.permission === 'granted') {
      try {
        new Notification('V3RCHAT', {
          body: 'Pesan baru diterima',
          icon: '/vendor/chatify/avatar.png',
        })
      } catch {
        // Ignore notification errors
      }
    } else if (Notification.permission !== 'denied') {
      void Notification.requestPermission()
    }
  }
}

export function resetIncomingSoundDedupeForTests() {
  recentIncomingSoundMessageIds.clear()
}
