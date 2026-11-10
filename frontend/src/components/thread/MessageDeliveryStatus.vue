<script setup lang="ts">
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import MessageStatusTicks from './MessageStatusTicks.vue'

defineProps<{
  status?: 'sending' | 'failed' | null
  read: boolean
  isOwn: boolean
}>()

const { t } = useChatifyI18n()
</script>

<template>
  <span
    v-if="status === 'sending'"
    class="chatify-message-status-sending chatify:inline-flex chatify:items-center chatify:opacity-65"
    :title="t('ui.thread.delivery.sending')"
    :aria-label="t('ui.thread.delivery.sending_aria')"
  >
    <svg viewBox="0 0 16 11" class="chatify:h-3 chatify:w-4 chatify:text-chatify-muted" fill="none">
      <path
        d="M11.071 0.75L4.682 7.133 1.928 4.384 0.5 5.812l4.182 4.182 7.182-7.182L11.071 0.75Z"
        fill="currentColor"
      />
    </svg>
  </span>
  <MessageStatusTicks v-else-if="isOwn && status !== 'failed'" :read="read" />
</template>
