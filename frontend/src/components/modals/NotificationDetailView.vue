<script setup lang="ts">
import { computed } from 'vue'
import type { ChatifyNotification } from '../../types'
import { useUiStore } from '../../stores/ui'
import { useConversationsStore } from '../../stores/conversations'
import { useConfirmStore } from '../../stores/confirm'
import UserAvatar from '../ui/UserAvatar.vue'
import LiquidGlassButton from '../ui/LiquidGlassButton.vue'

const props = defineProps<{
  notification: ChatifyNotification
}>()

const emit = defineEmits<{
  back: []
  confirm: [notification: ChatifyNotification]
  delete: [notification: ChatifyNotification]
}>()

const uiStore = useUiStore()
const conversationsStore = useConversationsStore()
const confirmStore = useConfirmStore()

const isRequest = computed(() => props.notification.type === 'request')
const isBorderGift = computed(() => Boolean(props.notification.data?.border_id))
const isGift = computed(() => Boolean(
  props.notification.data?.border_id
  || props.notification.data?.nameplate_id
  || props.notification.data?.profile_effect_id,
))

function formatDetailedTime(isoString: string): string {
  try {
    const d = new Date(isoString)
    return d.toLocaleString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return isoString
  }
}

function handleGoToProfile() {
  // Equip borders from the settings sidebar (Profile screen), not the settings modal.
  uiStore.closeModal()
  uiStore.requestSettingsScreen('profile')
  conversationsStore.setSidebarTab('settings')
}

async function handleDeleteClick() {
  const confirmed = await confirmStore.confirm({
    title: 'Delete notification?',
    message: 'This notification will be permanently removed. This action cannot be undone.',
    confirmLabel: 'Delete',
    variant: 'danger',
  })
  if (confirmed) {
    emit('delete', props.notification)
  }
}
</script>

<template>
  <div class="chatify:flex chatify:flex-col chatify:gap-2.5 chatify:overflow-y-auto chatify:pt-1 chatify:ps-1.5 chatify:pr-1 chatify:scrollbar-thin">
    <!-- Top Action Bar with Back Button -->
    <div class="chatify:flex chatify:items-center chatify:justify-between chatify:border-b chatify:border-white/10 chatify:pb-2">
      <LiquidGlassButton
        class="chatify:group"
        :aria-label="'Back'"
        size="2.1rem"
        width="auto"
        @click="emit('back')"
      >
        <span class="chatify:flex chatify:items-center chatify:gap-2 chatify:px-3 chatify:text-xs chatify:font-medium chatify:leading-none">
          <svg class="chatify:h-4 chatify:w-4 chatify:transition-transform group-hover:chatify:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          <span>Back</span>
        </span>
      </LiquidGlassButton>

      <span class="chatify:text-[11px] chatify:text-chatify-muted">
        {{ formatDetailedTime(notification.created_at) }}
      </span>
    </div>

    <!-- Notification Header Info -->
    <div class="chatify:flex chatify:flex-col chatify:gap-1.5">
      <!-- Author profile for information notifications -->
      <div
        v-if="!isRequest && notification.author"
        class="chatify:flex chatify:items-center chatify:gap-2.5"
      >
        <UserAvatar
          size="sm"
          :src="notification.author.avatar"
          :border-url="notification.author.avatar_border?.image_url ?? null"
          :alt="notification.author.name"
        />
        <div class="chatify:flex chatify:min-w-0 chatify:flex-1 chatify:flex-col chatify:gap-1">
          <h4 class="chatify:truncate chatify:text-base chatify:font-bold chatify:leading-tight chatify:text-white">
            {{ notification.author.name }}
          </h4>
          <span
            class="chatify:inline-flex chatify:w-fit chatify:items-center chatify:rounded-md chatify:px-1.5 chatify:py-0.5 chatify:text-[9px] chatify:font-bold chatify:uppercase chatify:tracking-wider"
            :class="isGift ? 'chatify:bg-amber-500/15 chatify:text-amber-400' : 'chatify:bg-zinc-800 chatify:text-zinc-300'"
          >
            {{ isGift ? 'Exclusive Gift' : 'Announcement' }}
          </span>
        </div>
      </div>

      <!-- Category badge fallback (requests, or info without author) -->
      <div v-else class="chatify:flex chatify:items-center chatify:gap-2">
        <span
          v-if="isRequest"
          class="chatify:inline-flex chatify:items-center chatify:gap-1 chatify:rounded-md chatify:bg-blue-500/15 chatify:px-2 chatify:py-0.5 chatify:text-[10px] chatify:font-bold chatify:uppercase chatify:tracking-wider chatify:text-blue-400"
        >
          <svg class="chatify:h-3 chatify:w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
          </svg>
          Chat Request
        </span>
        <span
          v-else-if="isBorderGift"
          class="chatify:inline-flex chatify:items-center chatify:gap-1 chatify:rounded-md chatify:bg-amber-500/15 chatify:px-2 chatify:py-0.5 chatify:text-[10px] chatify:font-bold chatify:uppercase chatify:tracking-wider chatify:text-amber-400"
        >
          <svg class="chatify:h-3 chatify:w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V4a2 2 0 10-4 0v4H3v4h18V8h-5V4a2 2 0 10-4 0v4zm0 13H4a1 1 0 01-1-1v-8h9v9zm0 0h8a1 1 0 001-1v-8h-9v9z" />
          </svg>
          Exclusive Gift
        </span>
        <span
          v-else
          class="chatify:inline-flex chatify:items-center chatify:gap-1 chatify:rounded-md chatify:bg-zinc-800 chatify:px-2 chatify:py-0.5 chatify:text-[10px] chatify:font-bold chatify:uppercase chatify:tracking-wider chatify:text-zinc-300"
        >
          <svg class="chatify:h-3 chatify:w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
          </svg>
          Announcement
        </span>
      </div>

      <!-- Title: Uncut, wrapping cleanly with break-words -->
      <h3 class="chatify:text-base chatify:font-bold chatify:leading-snug chatify:text-white chatify:break-words chatify:break-all">
        {{ notification.title || (isRequest ? 'Direct Chat Request' : 'Notification') }}
      </h3>
    </div>

    <!-- SENDER PROFILE (FOR CHAT REQUESTS) -->
    <div
      v-if="isRequest"
      class="chatify:flex chatify:items-center chatify:gap-3.5 chatify:rounded-2xl chatify:border chatify:border-blue-500/20 chatify:bg-blue-500/[0.06] chatify:p-3.5"
    >
      <UserAvatar
        size="sm"
        :src="notification.data?.sender_avatar"
        :border-url="notification.data?.sender_border?.image_url"
        :alt="notification.data?.sender_name || 'Sender'"
      />
      <div class="chatify:min-w-0 chatify:flex-1">
        <h4 class="chatify:truncate chatify:text-sm chatify:font-bold chatify:text-white">
          {{ notification.data?.sender_name || 'Unknown User' }}
        </h4>
        <p v-if="notification.data?.sender_username" class="chatify:truncate chatify:text-xs chatify:text-blue-300/80">
          @{{ notification.data.sender_username }}
        </p>
        <p class="chatify:mt-0.5 chatify:text-[11px] chatify:text-chatify-muted">
          Wants to start a conversation with you
        </p>
      </div>
    </div>

    <!-- BODY CONTENT / DESCRIPTION -->
    <p class="chatify:text-xs chatify:leading-relaxed chatify:text-zinc-200 chatify:whitespace-pre-wrap chatify:break-words chatify:break-all">
      {{ notification.body }}
    </p>

    <!-- BORDER GIFT GRAPHIC SHOWCASE -->
    <div
      v-if="isBorderGift"
      class="chatify:flex chatify:flex-col chatify:gap-2"
    >
      <div class="chatify:flex chatify:items-center chatify:gap-4">
        <UserAvatar size="md" :border-url="notification.image_url" />
        <div class="chatify:min-w-0 chatify:flex-1">
          <span class="chatify:text-[10px] chatify:font-bold chatify:uppercase chatify:tracking-wider chatify:text-amber-400">
            Equip Animated Border
          </span>
          <h4 class="chatify:truncate chatify:text-sm chatify:font-bold chatify:text-white">
            {{ notification.data?.border_name || 'Animated Border' }}
          </h4>
          <p class="chatify:text-[11px] chatify:text-chatify-muted">
            This animated frame has been added to your profile inventory.
          </p>
        </div>
      </div>

      <button
        type="button"
        class="chatify:mt-1 chatify:inline-flex chatify:w-full chatify:items-center chatify:justify-center chatify:gap-2 chatify:rounded-xl chatify:bg-gradient-to-r chatify:from-amber-600 chatify:to-yellow-600 chatify:py-2.5 chatify:px-4 chatify:text-xs chatify:font-bold chatify:text-white chatify:shadow chatify:transition hover:chatify:brightness-110"
        @click="handleGoToProfile"
      >
        <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        <span>Go to Profile to Equip</span>
      </button>
    </div>

    <!-- GENERAL ATTACHED IMAGE — no frame or backdrop; portrait and landscape
         images size themselves and stay centered. -->
    <img
      v-else-if="notification.image_url"
      :src="notification.image_url"
      alt="Attachment"
      class="chatify:block chatify:self-start chatify:max-h-80 chatify:w-auto chatify:max-w-full chatify:rounded-xl chatify:object-contain"
    />

    <!-- ACTION BUTTONS -->
    <!-- Request Accept / Decline Actions -->
    <div
      v-if="isRequest && notification.status === 'pending'"
      class="chatify:flex chatify:items-center chatify:gap-2.5 chatify:pt-1"
    >
      <button
        type="button"
        class="chatify:inline-flex chatify:flex-1 chatify:items-center chatify:justify-center chatify:gap-2 chatify:rounded-xl chatify:bg-emerald-600 chatify:py-2.5 chatify:px-4 chatify:text-xs chatify:font-bold chatify:text-white chatify:shadow chatify:transition hover:chatify:bg-emerald-500"
        @click="emit('confirm', notification)"
      >
        <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>Confirm Request</span>
      </button>

      <button
        type="button"
        class="chatify:inline-flex chatify:flex-1 chatify:items-center chatify:justify-center chatify:gap-2 chatify:rounded-xl chatify:border chatify:border-rose-500/30 chatify:bg-rose-500/10 chatify:py-2.5 chatify:px-4 chatify:text-xs chatify:font-semibold chatify:text-rose-300 chatify:transition hover:chatify:bg-rose-500/20"
        @click="emit('delete', notification)"
      >
        <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        <span>Decline</span>
      </button>
    </div>

    <!-- Standard Dismiss / Delete: 80% glass pill + 20% round glass icon button -->
    <div
      v-else
      class="chatify:grid chatify:w-full chatify:grid-cols-[4fr_1fr] chatify:items-center chatify:gap-2.5 chatify:pt-1"
    >
      <LiquidGlassButton
        class="chatify:w-full"
        width="100%"
        size="2.5rem"
        @click="emit('back')"
      >
        <span class="chatify:text-xs chatify:font-semibold chatify:leading-none">Close</span>
      </LiquidGlassButton>

      <div class="chatify:flex chatify:justify-center">
        <LiquidGlassButton
          aria-label="Delete notification"
          size="2.5rem"
          @click="handleDeleteClick"
        >
          <svg class="chatify:h-4 chatify:w-4 chatify:text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </LiquidGlassButton>
      </div>
    </div>
  </div>
</template>
