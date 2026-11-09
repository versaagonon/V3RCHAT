<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useTyping } from '../../composables/useTyping'
import { useVoiceRecorder } from '../../composables/useVoiceRecorder'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import { useChatifyDirection } from '../../composables/useChatifyDirection'
import { stopAllVoicePlayback } from '../../utils/voicePlayback'
import { useConfigStore } from '../../stores/config'
import { useConfirmStore } from '../../stores/confirm'
import { useContactsStore } from '../../stores/contacts'
import { useConversationsStore } from '../../stores/conversations'
import { useMessagesStore } from '../../stores/messages'
import ComposerAttachMenu from './ComposerAttachMenu.vue'
import StickerPicker from './StickerPicker.vue'
import VoiceRecorderOverlay from './VoiceRecorderOverlay.vue'

const props = defineProps<{
  conversationId: string
}>()

const messagesStore = useMessagesStore()
const configStore = useConfigStore()
const conversationsStore = useConversationsStore()
const contactsStore = useContactsStore()
const confirmStore = useConfirmStore()
const { t } = useChatifyI18n()
const { dir } = useChatifyDirection()
const giphyEnabled = computed(() => configStore.giphyEnabled)
const { replyToMessage, editingMessage } = storeToRefs(messagesStore)

const body = ref('')
const composerDir = computed(() => (body.value.trim() ? 'auto' : dir.value))
const attachments = ref<File[]>([])
const previewUrls = ref<string[]>([])
const mediaInput = ref<HTMLInputElement | null>(null)
const documentInput = ref<HTMLInputElement | null>(null)
const textareaRef = ref<HTMLTextAreaElement | null>(null)
const attachMenuRef = ref<InstanceType<typeof ComposerAttachMenu> | null>(null)
const stickerPickerRef = ref<InstanceType<typeof StickerPicker> | null>(null)

const TEXTAREA_MAX_HEIGHT = 128
const isMultiline = ref(false)

const composerFilterId = `composer-glass-${Math.random().toString(36).slice(2, 9)}`
const supportsSvgFilter = ref(false)

const scaleR = -28
const scaleG = -20
const scaleB = -12

const composerMapDataUrl = computed(() => {
  const w = 320
  const h = 52
  const r = Math.min(w, h) * (0.07 * 0.5)
  const borderRadius = 26
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}" width="${w}" height="${h}">
    <defs>
      <linearGradient id="cmp-red" x1="100%" y1="0%" x2="0%" y2="0%">
        <stop offset="0%" stop-color="rgba(255,0,0,0)"/>
        <stop offset="14%" stop-color="rgba(255,0,0,1)"/>
        <stop offset="86%" stop-color="rgba(255,0,0,1)"/>
        <stop offset="100%" stop-color="rgba(255,0,0,0)"/>
      </linearGradient>
      <linearGradient id="cmp-blue" x1="0%" y1="0%" x2="0%" y2="100%">
        <stop offset="0%" stop-color="rgba(0,0,255,0)"/>
        <stop offset="14%" stop-color="rgba(0,0,255,1)"/>
        <stop offset="86%" stop-color="rgba(0,0,255,1)"/>
        <stop offset="100%" stop-color="rgba(0,0,255,0)"/>
      </linearGradient>
    </defs>
    <rect width="${w}" height="${h}" fill="rgb(128,128,128)"/>
    <rect width="${w}" height="${h}" rx="${borderRadius}" fill="url(#cmp-red)"/>
    <rect width="${w}" height="${h}" rx="${borderRadius}" fill="url(#cmp-blue)" style="mix-blend-mode:difference"/>
    <rect x="${r}" y="${r}" width="${Math.max(1, w - r * 2)}" height="${Math.max(1, h - r * 2)}" rx="${borderRadius}" fill="hsl(0 0% 50% / 0.93)" style="filter:blur(8px)"/>
  </svg>`
  return `data:image/svg+xml,${encodeURIComponent(svg)}`
})

onMounted(() => {
  if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    const testEl = document.createElement('div')
    testEl.style.backdropFilter = `url(#${composerFilterId})`
    supportsSvgFilter.value = testEl.style.backdropFilter !== ''
  }
})

const composerGlareX = ref('35%')
const composerGlareY = ref('20%')
const composerGlareOpacity = ref('0.65')

function onComposerPointerMove(event: PointerEvent) {
  const target = event.currentTarget as HTMLElement | null
  if (!target) return
  const rect = target.getBoundingClientRect()
  const x = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100))
  const y = Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100))
  composerGlareX.value = `${Math.round(x)}%`
  composerGlareY.value = `${Math.round(y)}%`
  composerGlareOpacity.value = '0.85'
}

function onComposerPointerLeave() {
  composerGlareX.value = '35%'
  composerGlareY.value = '20%'
  composerGlareOpacity.value = '0.65'
}

const { notifyTyping, stopTyping } = useTyping(() => props.conversationId)
const {
  isRecording,
  durationMs,
  waveform,
  startRecording,
  stopRecording,
  cancelRecording,
} = useVoiceRecorder()

const VIDEO_EXTENSIONS = new Set([
  'mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v', 'ogv', '3gp', 'mpeg', 'mpg',
])

const accept = [
  ...(configStore.attachments?.allowedImages.map((ext) => `.${ext}`) ?? []),
  ...(configStore.attachments?.allowedFiles.map((ext) => `.${ext}`) ?? []),
].join(',')

const mediaAccept = [
  ...(configStore.attachments?.allowedImages ?? []),
  ...(configStore.attachments?.allowedFiles ?? []).filter((ext) => VIDEO_EXTENSIONS.has(ext)),
]
  .map((ext) => `.${ext}`)
  .join(',')

const modeLabel = computed(() => {
  if (editingMessage.value) {
    return t('ui.thread.composer.editing_message')
  }
  if (replyToMessage.value) {
    const preview = replyToMessage.value.attributes.body?.slice(0, 80) ?? t('ui.thread.composer.reply_attachment')
    return t('ui.thread.composer.replying_to', { preview })
  }
  return ''
})

const hasContent = computed(() => Boolean(body.value.trim()) || attachments.value.length > 0)
const showSendButton = computed(() => hasContent.value || Boolean(editingMessage.value))

const otherUser = computed(() => conversationsStore.activeConversation?.relationships.other_user ?? null)

const blockedByMe = computed(() => {
  const conversation = conversationsStore.activeConversation

  return Boolean(
    conversation?.attributes.conversation_type === 'direct'
    && otherUser.value
    && contactsStore.isBlockedByMe(otherUser.value.id),
  )
})

const isMessagingBlockedDirect = computed(() => {
  const conversation = conversationsStore.activeConversation

  return Boolean(
    conversation?.attributes.conversation_type === 'direct'
    && otherUser.value
    && contactsStore.isMessagingBlocked(otherUser.value.id),
  )
})

async function unblockContact() {
  if (!otherUser.value) {
    return
  }

  const confirmed = await confirmStore.confirm({
    title: t('ui.confirm.unblock.title', { name: otherUser.value.attributes.name }),
    message: t('ui.confirm.unblock.message'),
    confirmLabel: t('ui.confirm.unblock.confirm'),
  })

  if (confirmed) {
    await contactsStore.unblockUser(otherUser.value.id)
  }
}
const imagePreviews = computed(() =>
  attachments.value.flatMap((file, attachmentIndex) => {
    if (!file.type.startsWith('image/')) {
      return []
    }

    const previewIndex = attachments.value
      .slice(0, attachmentIndex)
      .filter((item) => item.type.startsWith('image/')).length

    return [{
      file,
      url: previewUrls.value[previewIndex],
      attachmentIndex,
    }]
  }).filter((item) => item.url),
)

const nonImageAttachments = computed(() =>
  attachments.value.filter((file) => !file.type.startsWith('image/')),
)

const hasComposerExtras = computed(
  () => Boolean(modeLabel.value) || imagePreviews.value.length > 0 || nonImageAttachments.value.length > 0,
)

watch(editingMessage, async (message) => {
  if (message) {
    body.value = message.attributes.body ?? ''
    clearAttachments()
    await nextTick()
    resizeTextarea()
  }
})

function revokePreviews() {
  previewUrls.value.forEach((url) => URL.revokeObjectURL(url))
  previewUrls.value = []
}

function clearAttachments() {
  revokePreviews()
  attachments.value = []
  if (mediaInput.value) {
    mediaInput.value.value = ''
  }
  if (documentInput.value) {
    documentInput.value.value = ''
  }
}

function resetTextareaHeight() {
  const el = textareaRef.value
  if (!el) {
    isMultiline.value = false
    return
  }

  el.style.height = '2.25rem'
  el.style.overflowY = 'hidden'
  isMultiline.value = false
}

function resizeTextarea() {
  const el = textareaRef.value
  if (!el) {
    return
  }

  el.style.height = 'auto'
  const nextHeight = Math.min(el.scrollHeight, TEXTAREA_MAX_HEIGHT)
  el.style.height = `${Math.max(nextHeight, 36)}px`
  el.style.overflowY = el.scrollHeight > TEXTAREA_MAX_HEIGHT ? 'auto' : 'hidden'
  isMultiline.value = nextHeight > 44
}

function cancelMode() {
  messagesStore.setReplyTo(null)
  messagesStore.setEditingMessage(null)
  body.value = ''
  clearAttachments()
  resetTextareaHeight()
}

function buildReplyPreview() {
  const reply = replyToMessage.value
  if (!reply) {
    return null
  }

  const isOwnReplyTarget =
    configStore.user &&
    String(reply.relationships.sender.data.id) === String(configStore.user.id)

  return {
    id: reply.id,
    body: reply.attributes.body,
    sender_name: isOwnReplyTarget ? t('ui.user.you') : t('ui.actions.message.reply'),
  }
}

function splitAttachments(files: File[]) {
  const imageFiles = files.filter((file) => file.type.startsWith('image/'))
  const otherFiles = files.filter((file) => !file.type.startsWith('image/'))
  return { imageFiles, otherFiles }
}

async function dispatchSend(options?: {
  text?: string
  files?: File[]
}) {
  const text = (options?.text ?? body.value).trim()
  const files = options?.files ?? attachments.value

  if (!text && files.length === 0) {
    return
  }

  const editing = editingMessage.value
  const replyId = replyToMessage.value?.id
  const replyPreview = buildReplyPreview()
  const { imageFiles, otherFiles } = splitAttachments(files)

  body.value = ''
  clearAttachments()
  resetTextareaHeight()
  stopTyping()

  if (editing) {
    messagesStore.setEditingMessage(null)
    try {
      await messagesStore.updateMessage(editing, text)
    } catch {
      messagesStore.setEditingMessage(editing)
      body.value = text
      await nextTick()
      resizeTextarea()
    }
    return
  }

  messagesStore.setReplyTo(null)

  messagesStore.queueOutboundMessage({
    conversationId: props.conversationId,
    body: text,
    attachment: otherFiles[0],
    attachments: imageFiles.length > 0 ? imageFiles : undefined,
    reply_to_message_id: replyId,
    reply_to: replyPreview,
  })
}

async function send() {
  await dispatchSend()
}

function addFiles(files: File[]) {
  if (files.length === 0) {
    return
  }

  revokePreviews()
  attachments.value = files
  previewUrls.value = files
    .filter((file) => file.type.startsWith('image/'))
    .map((file) => URL.createObjectURL(file))
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    void send()
    return
  }

  if (event.key === 'Enter' && event.shiftKey) {
    requestAnimationFrame(resizeTextarea)
  }
}

function onInput() {
  notifyTyping()
  resizeTextarea()
}

function onMediaChange(event: Event) {
  const target = event.target as HTMLInputElement
  addFiles(Array.from(target.files ?? []))
}

function onDocumentChange(event: Event) {
  const target = event.target as HTMLInputElement
  addFiles(Array.from(target.files ?? []))
}

function removeAttachment(index: number) {
  const file = attachments.value[index]
  if (!file) {
    return
  }

  if (file.type.startsWith('image/')) {
    const previewIndex = attachments.value
      .slice(0, index)
      .filter((item) => item.type.startsWith('image/')).length
    if (previewUrls.value[previewIndex]) {
      URL.revokeObjectURL(previewUrls.value[previewIndex])
      previewUrls.value = previewUrls.value.filter((_, i) => i !== previewIndex)
    }
  }

  attachments.value = attachments.value.filter((_, i) => i !== index)
}

function openMediaPicker() {
  mediaInput.value?.click()
}

function openDocumentPicker() {
  documentInput.value?.click()
}

async function onStickerSelect(file: File) {
  await dispatchSend({ text: '', files: [file] })
}

function closePopups() {
  attachMenuRef.value?.close()
  stickerPickerRef.value?.close()
}

function onAttachMenuOpen() {
  stickerPickerRef.value?.close()
}

function onStickerPickerOpen() {
  attachMenuRef.value?.close()
}

function beginVoiceRecording() {
  if (showSendButton.value || isRecording.value) {
    return
  }

  closePopups()
  stopAllVoicePlayback()
  void startRecording()
}

async function sendVoiceRecording() {
  const file = await stopRecording()
  if (file) {
    await dispatchSend({ text: '', files: [file] })
  }
}

function discardVoiceRecording() {
  cancelRecording()
}

onBeforeUnmount(revokePreviews)

defineExpose({
  addFiles,
  closePopups,
})
</script>

<template>
  <div class="chatify-composer-floating">
    <div
      v-if="isMessagingBlockedDirect"
      class="chatify-composer-blocked-banner"
    >
      <p v-if="blockedByMe">
        {{ $t('ui.thread.composer.blocked_by_me') }}
      </p>
      <p v-else>
        {{ $t('ui.thread.composer.blocked_by_them') }}
      </p>
      <button
        v-if="blockedByMe"
        type="button"
        class="chatify-composer-blocked-action"
        @click="unblockContact"
      >
        {{ $t('ui.thread.composer.unblock') }}
      </button>
    </div>

    <div v-else-if="hasComposerExtras && !isRecording" class="chatify-composer-floating-extras">
      <div
        v-if="modeLabel"
        class="chatify-composer-mode-banner"
      >
        <span class="chatify:truncate">{{ modeLabel }}</span>
        <button type="button" class="chatify-composer-mode-close" @click="cancelMode">
          ✕
        </button>
      </div>

      <div v-if="imagePreviews.length > 0" class="chatify-composer-preview-row">
        <div
          v-for="preview in imagePreviews"
          :key="preview.url"
          class="chatify-composer-preview-thumb"
        >
          <img :src="preview.url" alt="" />
          <button
            type="button"
            class="chatify-composer-preview-remove"
            @click="removeAttachment(preview.attachmentIndex)"
          >
            ✕
          </button>
        </div>
      </div>

      <div v-if="nonImageAttachments.length > 0" class="chatify-composer-file-row">
        <div
          v-for="file in nonImageAttachments"
          :key="`${file.name}-${file.size}`"
          class="chatify-composer-file-chip"
        >
          <span class="chatify:truncate">{{ file.name }}</span>
        </div>
      </div>
    </div>

    <!-- SVG Chromatic Aberration & Refraction Filter for Composer -->
    <svg
      class="liquid-glass-svg-def"
      xmlns="http://www.w3.org/2000/svg"
      aria-hidden="true"
    >
      <defs>
        <filter
          :id="composerFilterId"
          color-interpolation-filters="sRGB"
          x="-20%"
          y="-20%"
          width="140%"
          height="140%"
        >
          <feImage
            :href="composerMapDataUrl"
            x="0"
            y="0"
            width="100%"
            height="100%"
            preserveAspectRatio="none"
            result="map"
          />
          <feDisplacementMap
            in="SourceGraphic"
            in2="map"
            :scale="scaleR"
            xChannelSelector="R"
            yChannelSelector="G"
            result="dispRed"
          />
          <feDisplacementMap
            in="SourceGraphic"
            in2="map"
            :scale="scaleG"
            xChannelSelector="R"
            yChannelSelector="G"
            result="dispGreen"
          />
          <feDisplacementMap
            in="SourceGraphic"
            in2="map"
            :scale="scaleB"
            xChannelSelector="R"
            yChannelSelector="G"
            result="dispBlue"
          />
          <feColorMatrix
            in="dispRed"
            type="matrix"
            values="1 0 0 0 0
                    0 0 0 0 0
                    0 0 0 0 0
                    0 0 0 1 0"
            result="redChannel"
          />
          <feColorMatrix
            in="dispGreen"
            type="matrix"
            values="0 0 0 0 0
                    0 1 0 0 0
                    0 0 0 0 0
                    0 0 0 1 0"
            result="greenChannel"
          />
          <feColorMatrix
            in="dispBlue"
            type="matrix"
            values="0 0 0 0 0
                    0 0 0 0 0
                    0 0 1 0 0
                    0 0 0 1 0"
            result="blueChannel"
          />
          <feBlend in="redChannel" in2="greenChannel" mode="screen" result="rg" />
          <feBlend in="rg" in2="blueChannel" mode="screen" />
        </filter>
      </defs>
    </svg>

    <VoiceRecorderOverlay
      v-if="isRecording"
      class="chatify-composer-floating-pill"
      :duration-ms="durationMs"
      :waveform="waveform"
      @cancel="discardVoiceRecording"
      @send="sendVoiceRecording"
    />

    <div
      v-else-if="!isMessagingBlockedDirect"
      class="chatify-composer-floating-pill liquid-glass-composer"
      :class="[
        isMultiline ? 'chatify-composer-floating-pill-expanded' : '',
        supportsSvgFilter ? 'liquid-glass-svg-mode' : '',
      ]"
      :style="{
        '--glare-x': composerGlareX,
        '--glare-y': composerGlareY,
        '--glare-opacity': composerGlareOpacity,
        '--filter-id': `url(#${composerFilterId})`,
      }"
      @pointermove="onComposerPointerMove"
      @pointerleave="onComposerPointerLeave"
    >
      <span class="liquid-glass-refraction-fringe" aria-hidden="true" />
      <span class="liquid-glass-specular" aria-hidden="true" />
      <span class="liquid-glass-glare" aria-hidden="true" />
      <span class="liquid-glass-glare-opposite" aria-hidden="true" />
      <span class="liquid-glass-rim" aria-hidden="true" />
      <input
        ref="mediaInput"
        type="file"
        class="chatify:hidden"
        multiple
        :accept="mediaAccept"
        @change="onMediaChange"
      />
      <input
        ref="documentInput"
        type="file"
        class="chatify:hidden"
        :accept="accept"
        @change="onDocumentChange"
      />

      <ComposerAttachMenu
        ref="attachMenuRef"
        @pick-media="openMediaPicker"
        @pick-document="openDocumentPicker"
        @open="onAttachMenuOpen"
      />

      <StickerPicker
        v-if="giphyEnabled"
        ref="stickerPickerRef"
        @select="onStickerSelect"
        @open="onStickerPickerOpen"
      />

      <textarea
        ref="textareaRef"
        v-model="body"
        rows="1"
        :dir="composerDir"
        :placeholder="$t('ui.thread.composer.type_message')"
        class="chatify-composer-input chatify-composer-floating-input chatify:text-start"
        @keydown="onKeydown"
        @input="onInput"
        @focus="closePopups"
      />

      <button
        v-if="showSendButton"
        type="button"
        class="chatify-composer-send-btn"
        :disabled="!hasContent && !editingMessage"
        :aria-label="$t('ui.thread.composer.send')"
        @click="send"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
        </svg>
      </button>

      <button
        v-else
        type="button"
        class="chatify-composer-mic-btn"
        :aria-label="$t('ui.thread.composer.record_voice')"
        @click="beginVoiceRecording"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 14a3 3 0 003-3V5a3 3 0 10-6 0v6a3 3 0 003 3zm5-3a5 5 0 01-10 0H5a7 7 0 0014 0h-2zm-5 7a7 7 0 007-7h-2a5 5 0 01-10 0H5a7 7 0 007 7z" />
        </svg>
      </button>
    </div>
  </div>
</template>
