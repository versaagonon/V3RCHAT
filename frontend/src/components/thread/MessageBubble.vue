<script setup lang="ts">
import { computed, ref } from 'vue'
import type { ChatifyMessage } from '../../types'
import { formatMessageTime } from '../../utils/format'
import { isPendingMessageId } from '../../utils/outboundMessage'
import { copyPlainText } from '../../utils/copyText'
import { bubbleTextClass } from '../../themes/utils'
import { useImageLightbox } from '../../composables/useImageLightbox'
import { useConfigStore } from '../../stores/config'
import { useToastStore } from '../../stores/toast'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import { useBreakpoints } from '../../composables/useBreakpoints'
import { useBubbleGestures } from '../../composables/useBubbleGestures'
import MessageAlbumBubble from './MessageAlbumBubble.vue'
import MessageActionsMenu from './MessageActionsMenu.vue'
import MessageBody from './MessageBody.vue'
import MessageDeliveryStatus from './MessageDeliveryStatus.vue'
import MessageDocCard from './MessageDocCard.vue'
import VoiceMessageBubble from './VoiceMessageBubble.vue'

const props = defineProps<{
  message: ChatifyMessage
  isOwn: boolean
  isGroup?: boolean
  isSavedConversation?: boolean
  senderName?: string
  showSenderName?: boolean
  clusterSpacing?: 'tight' | 'normal'
}>()

const emit = defineEmits<{
  edit: []
  removeForMe: []
  removeForAll: []
  reply: []
  forward: []
  resend: []
  cancel: []
  jumpTo: [messageId: string]
}>()

const { show } = useImageLightbox()
const toastStore = useToastStore()
const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { isMobile } = useBreakpoints()
const actionsMenuRef = ref<InstanceType<typeof MessageActionsMenu> | null>(null)

const isAdmin = computed(() => (configStore.user?.attributes.role ?? 'node') === 'admin')

const localStatus = computed(() => props.message.attributes.local_status ?? null)
const isPending = computed(() => isPendingMessageId(props.message.id))
const showActions = computed(() => !isPending.value && localStatus.value !== 'sending')
const hasCopyableText = computed(() => Boolean(props.message.attributes.body?.trim()))
const spacingClass = computed(() => {
  // Uniform gap for every bubble. The old 'tight' (mt-0.5) vs 'normal' (mt-2) split
  // alternated with cluster index and the 5-minute gap rule, so consecutive bubbles
  // sat at visibly different offsets — some pushed out, some pulled in. The prop is
  // kept so callers do not change, but both values now resolve to the same gap.
  void props.clusterSpacing
  return 'chatify:mt-1'
})

const imageLoaded = ref(false)

const uploadProgress = computed(() => {
  const value = props.message.attributes.upload_progress
  return typeof value === 'number' ? value : null
})

const isUploading = computed(
  () =>
    isPending.value &&
    uploadProgress.value !== null &&
    uploadProgress.value < 100,
)

function attachments() {
  if (props.message.attributes.attachments?.length) {
    return props.message.attributes.attachments
  }
  return props.message.attributes.attachment ? [props.message.attributes.attachment] : []
}

function isImageAlbum() {
  const items = attachments()
  return items.length > 1 && items.every((item) => item.type === 'image')
}

function isAudioAttachment() {
  return props.message.attributes.attachment?.type === 'audio'
}

function isVideoAttachment() {
  return props.message.attributes.attachment?.type === 'video'
}

function openSingleImage() {
  const attachment = props.message.attributes.attachment
  if (attachment?.type === 'image') {
    show([attachment], 0)
  }
}

function onContextMenu(event: MouseEvent) {
  event.preventDefault()

  if (!showActions.value) {
    return
  }

  actionsMenuRef.value?.openAt(event.clientX, event.clientY)
}

/**
 * Swipe-to-reply.
 *
 * Swipe RIGHT on a bubble → reply to that bubble (works on others' messages, the
 * common case). Swipe LEFT on your OWN bubble → reply to it. Swiping left on
 * someone else's bubble, or right on your own, does nothing: the gesture is only
 * armed for the direction that maps to "reply to this message" for that side, so a
 * stray drag never fires an action the user did not mean.
 */
const canSwipeReply = computed(() => {
  if (!showActions.value) return false
  return true
})

const {
  isHolding,
  dragX,
  swipeDirection,
  onPointerDown,
  onPointerMove,
  onPointerUp,
  onPointerCancel,
} = useBubbleGestures({
  disabled: () => !showActions.value,
  onLongPress: ({ x, y }) => {
    actionsMenuRef.value?.openAt(x, y)
  },
  onSwipe: (direction) => {
    // Own bubbles reply on a left drag, others' on a right drag.
    const wanted = props.isOwn ? 'left' : 'right'
    if (direction !== wanted) return
    emit('reply')
  },
})

const bubbleTransform = computed(() => {
  if (dragX.value === 0) return undefined
  // Damped and capped in the composable; translate only, never resize.
  return `translateX(${dragX.value}px)`
})

async function onCopyText() {
  const body = props.message.attributes.body
  if (!body?.trim()) {
    return
  }

  const copied = await copyPlainText(body)
  toastStore.show({
    message: copied ? t('ui.toast.copied') : t('ui.toast.copy_failed'),
    icon: copied ? 'success' : 'error',
  })
}
</script>

<template>
  <div
    class="chatify:flex chatify:w-full chatify:flex-col chatify:gap-1"
    :class="[
      spacingClass,
      isOwn ? 'chatify:items-end' : 'chatify:items-start',
    ]"
  >
    <div
      class="chatify:group chatify-message-row chatify:flex chatify:min-w-0 chatify:items-end chatify:gap-1"
      :class="[
        isOwn ? 'chatify-message-row-outgoing' : '',
        isHolding ? 'chatify-message-holding' : '',
      ]"
      @contextmenu="onContextMenu"
      @pointerdown="onPointerDown"
      @pointermove="onPointerMove"
      @pointerup="onPointerUp"
      @pointercancel="onPointerCancel"
    >
      <!-- Swipe-to-reply affordance: arrow grows in as the bubble is dragged -->
      <span
        v-if="swipeDirection"
        class="chatify-bubble-swipe-hint"
        :class="isOwn ? 'chatify-bubble-swipe-hint-own' : 'chatify-bubble-swipe-hint-other'"
        aria-hidden="true"
      >
        <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a4 4 0 010 8h-3M3 10l4-4M3 10l4 4" />
        </svg>
      </span>

      <MessageActionsMenu
        v-if="isOwn && showActions"
        ref="actionsMenuRef"
        :is-own="isOwn"
        :is-admin="isAdmin"
        :has-copyable-text="hasCopyableText"
        :is-saved-conversation="isSavedConversation"
        :hide-trigger="isMobile"
        @edit="emit('edit')"
        @remove-for-me="emit('removeForMe')"
        @remove-for-all="emit('removeForAll')"
        @reply="emit('reply')"
        @forward="emit('forward')"
        @copy="onCopyText"
      />

      <div
        class="chatify-message-bubble chatify:min-w-0 chatify:max-w-full chatify:overflow-hidden chatify:px-3.5 chatify:py-1.5 chatify:shadow-sm"
        :class="[
          isOwn ? `chatify-message-bubble-out chatify:bg-chatify-bubble-out ${bubbleTextClass(true)}` : `chatify-message-bubble-in chatify:bg-chatify-bubble-in ${bubbleTextClass(false)}`,
          localStatus === 'sending' ? 'chatify-message-pending' : '',
          localStatus === 'failed' ? 'chatify-message-failed' : '',
        ]"
        :style="{ transform: bubbleTransform }"
      >
        <p
          v-if="showSenderName && senderName"
          class="chatify:mb-1 chatify:text-xs chatify:font-semibold chatify:text-chatify-primary-dark"
        >
          {{ senderName }}
        </p>

        <div
          v-if="message.attributes.forwarded_from"
          class="chatify:mb-1 chatify:border-s-2 chatify:border-chatify-primary chatify:ps-2 chatify:text-xs chatify:italic chatify:opacity-80"
        >
          <p>{{ $t('ui.thread.bubble.forwarded') }}</p>
        </div>

        <button
          v-if="message.attributes.reply_to"
          type="button"
          class="chatify:mb-1 chatify:block chatify:w-full chatify:cursor-pointer chatify:rounded chatify:border-s-2 chatify:border-chatify-primary chatify:bg-black/5 chatify:px-2 chatify:py-0.5 chatify:text-start chatify:text-xs chatify:opacity-80 chatify:transition chatify:hover:opacity-100"
          @click="emit('jumpTo', message.attributes.reply_to.id)"
        >
          <span class="chatify:block chatify:font-semibold">{{ message.attributes.reply_to.sender_name }}</span>
          <span class="chatify:block chatify:truncate">{{ message.attributes.reply_to.body || $t('ui.thread.bubble.attachment') }}</span>
        </button>

        <div v-if="isImageAlbum()" class="chatify:relative">
          <MessageAlbumBubble :attachments="attachments()" />
          <div
            v-if="isUploading"
            class="chatify-attachment-progress-overlay chatify:rounded-xl"
          >
            <div class="chatify-attachment-progress-ring">{{ uploadProgress }}%</div>
            <button
              type="button"
              class="chatify-attachment-progress-cancel"
              :aria-label="$t('ui.thread.bubble.cancel_upload')"
              @click="emit('cancel')"
            >
              ✕
            </button>
          </div>
        </div>

        <template v-else>
          <VoiceMessageBubble
            v-if="isAudioAttachment() && message.attributes.attachment"
            :attachment="message.attributes.attachment"
            :uploading="isUploading"
            :progress="uploadProgress"
            @cancel="emit('cancel')"
          />

          <div
            v-else-if="isVideoAttachment() && message.attributes.attachment"
            class="chatify:relative chatify:mb-1 chatify:block chatify:max-w-full chatify:overflow-hidden chatify:rounded-xl"
          >
            <video
              :src="message.attributes.attachment.url"
              controls
              preload="metadata"
              class="chatify:max-h-40 chatify:max-w-[14rem] chatify:rounded-xl"
            />
            <div
              v-if="isUploading"
              class="chatify-attachment-progress-overlay chatify:rounded-xl"
            >
              <div class="chatify-attachment-progress-ring">{{ uploadProgress }}%</div>
              <button
                type="button"
                class="chatify-attachment-progress-cancel"
                :aria-label="$t('ui.thread.bubble.cancel_upload')"
                @click="emit('cancel')"
              >
                ✕
              </button>
            </div>
          </div>

          <div
            v-else-if="message.attributes.attachment?.type === 'image'"
            class="chatify:relative chatify:mb-1 chatify:block chatify:max-w-full chatify:overflow-hidden chatify:rounded-xl"
          >
            <div
              v-if="!imageLoaded"
              class="chatify-attachment-skeleton chatify:h-32 chatify:w-40"
            />
            <button
              type="button"
              class="chatify:block chatify:overflow-hidden chatify:rounded-xl"
              :class="imageLoaded ? '' : 'chatify:hidden'"
              @click="openSingleImage"
            >
              <img
                :src="message.attributes.attachment.url"
                :alt="message.attributes.attachment.original_name ?? t('ui.thread.bubble.attachment')"
                class="chatify:max-h-32 chatify:max-w-[10.5rem] chatify:object-cover"
                @load="imageLoaded = true"
                @error="imageLoaded = true"
              />
            </button>
            <div
              v-if="isUploading"
              class="chatify-attachment-progress-overlay chatify:rounded-xl"
            >
              <div class="chatify-attachment-progress-ring">{{ uploadProgress }}%</div>
              <button
                type="button"
                class="chatify-attachment-progress-cancel"
                :aria-label="$t('ui.thread.bubble.cancel_upload')"
                @click="emit('cancel')"
              >
                ✕
              </button>
            </div>
          </div>

          <MessageDocCard
            v-else-if="message.attributes.attachment"
            :attachment="message.attributes.attachment"
            :uploading="isUploading"
            :progress="uploadProgress"
            @cancel="emit('cancel')"
          />
        </template>

        <MessageBody
          v-if="message.attributes.body"
          :body="message.attributes.body"
        ><template #meta><span class="chatify-message-meta chatify-message-meta-inline"><span v-if="message.attributes.edited_at" class="chatify:text-[10px] chatify:italic">{{ $t('ui.thread.bubble.edited') }}</span><span class="chatify:text-[10px]">{{ formatMessageTime(message.attributes.created_at) }}</span><MessageDeliveryStatus
                v-if="isOwn"
                :status="localStatus"
                :read="message.attributes.read"
                :is-own="isOwn"
              /></span></template></MessageBody>

        <div
          v-else
          class="chatify-message-meta chatify-message-meta-standalone chatify:mt-0.5 chatify:flex chatify:items-center chatify:justify-end chatify:gap-1"
        >
          <span v-if="message.attributes.edited_at" class="chatify:text-[10px] chatify:italic">{{ $t('ui.thread.bubble.edited') }}</span>
          <span class="chatify:text-[10px]">
            {{ formatMessageTime(message.attributes.created_at) }}
          </span>
          <MessageDeliveryStatus
            v-if="isOwn"
            :status="localStatus"
            :read="message.attributes.read"
            :is-own="isOwn"
          />
        </div>
      </div>

      <MessageActionsMenu
        v-if="!isOwn && showActions"
        ref="actionsMenuRef"
        :is-own="isOwn"
        :is-admin="isAdmin"
        :has-copyable-text="hasCopyableText"
        :is-saved-conversation="isSavedConversation"
        :hide-trigger="isMobile"
        @edit="emit('edit')"
        @remove-for-me="emit('removeForMe')"
        @remove-for-all="emit('removeForAll')"
        @reply="emit('reply')"
        @forward="emit('forward')"
        @copy="onCopyText"
      />
    </div>

    <div
      v-if="isOwn && localStatus === 'failed'"
      class="chatify-message-row chatify-message-row-outgoing chatify:flex chatify:gap-2 chatify:px-1"
    >
      <button
        type="button"
        class="chatify:text-xs chatify:font-medium chatify:text-chatify-primary"
        @click="emit('resend')"
      >
        {{ $t('ui.thread.bubble.resend') }}
      </button>
      <button
        type="button"
        class="chatify:text-xs chatify:font-medium chatify:text-chatify-muted"
        @click="emit('cancel')"
      >
        {{ $t('ui.thread.bubble.cancel') }}
      </button>
    </div>
  </div>
</template>
