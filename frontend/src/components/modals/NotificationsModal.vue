<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useNotificationsStore } from '../../stores/notifications'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import type { ChatifyNotification } from '../../types'
import BaseModal from './BaseModal.vue'
import UserAvatar from '../ui/UserAvatar.vue'
import NotificationDetailView from './NotificationDetailView.vue'

const uiStore = useUiStore()
const conversationsStore = useConversationsStore()
const notificationsStore = useNotificationsStore()
const { t } = useChatifyI18n()
const { activeModal } = storeToRefs(uiStore)
const { notifications, loading, unreadInfoCount, pendingRequestsCount } = storeToRefs(notificationsStore)

const open = computed(() => activeModal.value === 'notifications')
const activeTab = ref<'all' | 'information' | 'request'>('all')
const selectedNotification = ref<ChatifyNotification | null>(null)

// Glare coordinates for Switcher Dock (matching AuthPage)
const switcherGlareX = ref('50%')
const switcherGlareY = ref('50%')
const switcherGlareOpacity = ref('0.65')

function onSwitcherPointerMove(event: PointerEvent) {
  const target = event.currentTarget as HTMLElement | null
  if (!target) return
  const rect = target.getBoundingClientRect()
  const x = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100))
  const y = Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100))
  switcherGlareX.value = `${Math.round(x)}%`
  switcherGlareY.value = `${Math.round(y)}%`
  switcherGlareOpacity.value = '0.95'
}

function onSwitcherPointerLeave() {
  switcherGlareX.value = '50%'
  switcherGlareY.value = '50%'
  switcherGlareOpacity.value = '0.65'
}

watch(open, (isOpen) => {
  if (isOpen) {
    notificationsStore.fetchNotifications()
  } else {
    selectedNotification.value = null
  }
})

watch(activeTab, () => {
  selectedNotification.value = null
})

onMounted(() => {
  notificationsStore.fetchNotifications()
})

const filteredNotifications = computed<ChatifyNotification[]>(() => {
  if (activeTab.value === 'information') {
    return notifications.value.filter((n) => n.type === 'information')
  }
  if (activeTab.value === 'request') {
    return notifications.value.filter((n) => n.type === 'request')
  }
  return notifications.value
})

const allCount = computed(() => notifications.value.length)
const infoCount = computed(() => notifications.value.filter((n) => n.type === 'information').length)
const requestCount = computed(() => notifications.value.filter((n) => n.type === 'request').length)

function formatTime(isoString: string): string {
  try {
    const d = new Date(isoString)
    const now = new Date()
    const diffMs = now.getTime() - d.getTime()
    const diffMins = Math.floor(diffMs / (1000 * 60))
    const diffHours = Math.floor(diffMins / 60)
    const diffDays = Math.floor(diffHours / 24)

    if (diffMins < 1) return 'Just now'
    if (diffMins < 60) return `${diffMins}m ago`
    if (diffHours < 24) return `${diffHours}h ago`
    if (diffDays < 7) return `${diffDays}d ago`
    return d.toLocaleDateString()
  } catch {
    return ''
  }
}

async function selectNotification(item: ChatifyNotification) {
  if (item.type === 'request') {
    const convId = item.data?.conversation_id
    if (convId) {
      uiStore.closeModal()
      conversationsStore.setSidebarTab('chats')
      await conversationsStore.select(convId)
      uiStore.showThread()
      return
    }
  }
  selectedNotification.value = item
  void handleMarkRead(item)
}

async function handleConfirm(notification: ChatifyNotification) {
  if (selectedNotification.value?.id === notification.id) {
    selectedNotification.value = null
  }
  await notificationsStore.confirmRequest(notification.id)
}

async function handleDelete(notification: ChatifyNotification) {
  if (selectedNotification.value?.id === notification.id) {
    selectedNotification.value = null
  }
  const convId = notification.data?.conversation_id
  if (convId) {
    conversationsStore.removeConversation(String(convId))
    if (String(conversationsStore.activeId) === String(convId)) {
      conversationsStore.clearActive()
      uiStore.hideThread()
    }
  }
  if (notification.type === 'request') {
    await notificationsStore.deleteRequest(notification.id)
  } else {
    await notificationsStore.removeNotification(notification.id)
  }
  await conversationsStore.fetchAll()
}

async function handleMarkRead(notification: ChatifyNotification) {
  if (notification.status === 'unread') {
    await notificationsStore.markRead(notification.id)
  }
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="selectedNotification ? 'Notification Details' : 'Notifications'"
    @close="uiStore.closeModal()"
  >
    <!-- DETAIL SUB-PAGE VIEW -->
    <NotificationDetailView
      v-if="selectedNotification"
      :notification="selectedNotification"
      @back="selectedNotification = null"
      @confirm="handleConfirm"
      @delete="handleDelete"
    />

    <!-- LIST AND FILTER TABS VIEW -->
    <div
      v-else
      class="chatify:flex chatify:max-h-[75vh] chatify:flex-col chatify:gap-3 chatify:overflow-hidden chatify:pt-1"
    >
      <!-- Filter Tabs Bar (Floating Liquid Glass Switcher matching AuthPage) -->
      <div class="chatify:flex chatify:flex-col chatify:gap-2 chatify:border-b chatify:border-white/10 chatify:pb-3">
        <div
          class="v3rchat-notifications-switcher liquid-glass-floating-nav"
          :style="{
            '--glare-x': switcherGlareX,
            '--glare-y': switcherGlareY,
            '--glare-opacity': switcherGlareOpacity,
          }"
          role="tablist"
          aria-label="Notification filter"
          @pointermove="onSwitcherPointerMove"
          @pointerleave="onSwitcherPointerLeave"
        >
          <span class="liquid-glass-refraction-fringe" aria-hidden="true" />
          <span class="liquid-glass-specular" aria-hidden="true" />
          <span class="liquid-glass-nav-glare" aria-hidden="true" />
          <span class="liquid-glass-nav-rim" aria-hidden="true" />

          <!-- Single Sliding Jelly Indicator Pill (Spring Physics) -->
          <span
            class="liquid-glass-sliding-pill"
            :class="{
              'pos-all': activeTab === 'all',
              'pos-info': activeTab === 'information',
              'pos-request': activeTab === 'request',
            }"
            aria-hidden="true"
          >
            <span class="sliding-pill-glow" />
            <span class="sliding-pill-rim" />
          </span>

          <!-- Tab: All -->
          <button
            type="button"
            class="v3rchat-switcher-btn liquid-glass-nav-tab"
            :class="{ 'v3rchat-switcher-active': activeTab === 'all' }"
            role="tab"
            :aria-selected="activeTab === 'all'"
            @click="activeTab = 'all'"
          >
            <svg class="chatify:h-3.5 chatify:w-3.5 chatify:shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <span>All</span>
            <span class="notification-tab-badge">
              {{ allCount }}
            </span>
          </button>

          <!-- Tab: Information -->
          <button
            type="button"
            class="v3rchat-switcher-btn liquid-glass-nav-tab"
            :class="{ 'v3rchat-switcher-active': activeTab === 'information' }"
            role="tab"
            :aria-selected="activeTab === 'information'"
            @click="activeTab = 'information'"
          >
            <svg class="chatify:h-3.5 chatify:w-3.5 chatify:shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Information</span>
            <span
              v-if="unreadInfoCount > 0"
              class="notification-tab-badge is-highlight"
            >
              {{ unreadInfoCount }}
            </span>
            <span v-else class="notification-tab-badge">
              {{ infoCount }}
            </span>
          </button>

          <!-- Tab: Request -->
          <button
            type="button"
            class="v3rchat-switcher-btn liquid-glass-nav-tab"
            :class="{ 'v3rchat-switcher-active': activeTab === 'request' }"
            role="tab"
            :aria-selected="activeTab === 'request'"
            @click="activeTab = 'request'"
          >
            <svg class="chatify:h-3.5 chatify:w-3.5 chatify:shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            <span>Request</span>
            <span
              v-if="pendingRequestsCount > 0"
              class="notification-tab-badge is-alert"
            >
              {{ pendingRequestsCount }}
            </span>
            <span v-else class="notification-tab-badge">
              {{ requestCount }}
            </span>
          </button>
        </div>

        <!-- Mark all as read button -->
        <div v-if="unreadInfoCount > 0" class="chatify:flex chatify:justify-end">
          <button
            type="button"
            class="chatify:inline-flex chatify:items-center chatify:gap-1 chatify:rounded-lg chatify:border chatify:border-white/10 chatify:bg-white/[0.04] chatify:px-2.5 chatify:py-1 chatify:text-[11px] chatify:font-medium chatify:text-chatify-muted chatify:transition hover:chatify:bg-white/[0.08] hover:chatify:text-white"
            @click="notificationsStore.markAllRead()"
          >
            <svg class="chatify:h-3 chatify:w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>Mark all read</span>
          </button>
        </div>
      </div>

      <!-- Notifications List Container -->
      <div class="chatify:min-h-0 chatify:flex-1 chatify:overflow-y-auto chatify:space-y-2.5 chatify:pr-1 chatify:scrollbar-thin">
        <!-- Loading State -->
        <div v-if="loading && notifications.length === 0" class="chatify:flex chatify:items-center chatify:justify-center chatify:py-12 chatify:text-chatify-muted">
          <svg class="chatify:h-6 chatify:w-6 chatify:animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="chatify:opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="chatify:opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
          </svg>
        </div>

        <!-- Empty State -->
        <div
          v-else-if="filteredNotifications.length === 0"
          class="chatify:flex chatify:flex-col chatify:items-center chatify:justify-center chatify:py-12 chatify:text-center"
        >
          <div class="chatify:flex chatify:h-12 chatify:w-12 chatify:items-center chatify:justify-center chatify:rounded-full chatify:border chatify:border-white/10 chatify:bg-white/[0.03] chatify:text-zinc-500">
            <svg class="chatify:h-6 chatify:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </div>
          <h4 class="chatify:mt-3 chatify:text-xs chatify:font-semibold chatify:text-zinc-300">No notifications found</h4>
          <p class="chatify:mt-1 chatify:text-[11px] chatify:text-chatify-muted">You're all caught up on this section.</p>
        </div>

        <!-- Notification Items -->
        <div
          v-for="item in filteredNotifications"
          :key="item.id"
          class="chatify:group chatify:relative chatify:flex chatify:w-full chatify:min-w-0 chatify:cursor-pointer chatify:flex-col chatify:gap-2.5 chatify:rounded-2xl chatify:border chatify:p-3.5 chatify:transition"
          :class="[
            item.type === 'request'
              ? 'chatify:border-blue-500/20 chatify:bg-blue-500/[0.04] hover:chatify:border-blue-500/40 hover:chatify:bg-blue-500/[0.08]'
              : item.status === 'unread'
                ? 'chatify:border-amber-500/25 chatify:bg-amber-500/[0.04] hover:chatify:border-amber-500/45 hover:chatify:bg-amber-500/[0.08]'
                : 'chatify:border-white/10 chatify:bg-white/[0.02] hover:chatify:border-white/20 hover:chatify:bg-white/[0.05]',
          ]"
          @click="selectNotification(item)"
        >
          <!-- 1. REQUEST CARD (Styled like a clean conversation list item) -->
          <div v-if="item.type === 'request'" class="chatify:flex chatify:w-full chatify:min-w-0 chatify:items-center chatify:justify-between chatify:gap-3">
            <div class="chatify:flex chatify:min-w-0 chatify:flex-1 chatify:items-center chatify:gap-3">
              <!-- Sender Avatar with Animated Border -->
              <UserAvatar
                size="sm"
                :src="item.data?.sender_avatar"
                :border-url="item.data?.sender_border?.image_url"
                :alt="item.data?.sender_name || 'Sender'"
              />
              <div class="chatify:min-w-0 chatify:flex-1">
                <div class="chatify:flex chatify:items-center chatify:gap-1.5">
                  <span class="chatify:truncate chatify:text-xs chatify:font-bold chatify:text-white">
                    {{ item.data?.sender_name || 'Unknown User' }}
                  </span>
                  <span v-if="item.data?.sender_username" class="chatify:truncate chatify:text-[10px] chatify:text-chatify-muted">
                    @{{ item.data.sender_username }}
                  </span>
                </div>
                <p class="chatify:truncate chatify:text-[11px] chatify:font-medium chatify:text-blue-400">
                  Incoming Chat Request
                </p>
              </div>
            </div>

            <div class="chatify:flex chatify:shrink-0 chatify:items-center chatify:gap-1.5">
              <span class="chatify:text-[10px] chatify:text-chatify-muted">{{ formatTime(item.created_at) }}</span>
              <svg class="chatify:h-3.5 chatify:w-3.5 chatify:text-zinc-500 group-hover:chatify:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </div>

          <!-- 2. INFORMATION CARD -->
          <div v-else class="chatify:w-full chatify:min-w-0 chatify:space-y-2">
            <div class="chatify:flex chatify:items-start chatify:justify-between chatify:gap-2">
              <div class="chatify:flex chatify:min-w-0 chatify:flex-1 chatify:items-center chatify:gap-2">
                <div class="chatify:flex chatify:h-6 chatify:w-6 chatify:shrink-0 chatify:items-center chatify:justify-center chatify:rounded-lg chatify:bg-amber-500/20 chatify:text-amber-400">
                  <svg v-if="item.data?.border_id" class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V4a2 2 0 10-4 0v4H3v4h18V8h-5V4a2 2 0 10-4 0v4zm0 13H4a1 1 0 01-1-1v-8h9v9zm0 0h8a1 1 0 001-1v-8h-9v9z" />
                  </svg>
                  <svg v-else class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                  </svg>
                </div>
                <!-- Title: Truncated safely on list card -->
                <h5 class="chatify:min-w-0 chatify:flex-1 chatify:truncate chatify:text-xs chatify:font-bold chatify:text-white">
                  {{ item.title }}
                </h5>
                <span v-if="item.status === 'unread'" class="chatify:h-1.5 chatify:w-1.5 chatify:shrink-0 chatify:rounded-full chatify:bg-amber-400" />
              </div>

              <div class="chatify:flex chatify:shrink-0 chatify:items-center chatify:gap-2">
                <span class="chatify:text-[10px] chatify:text-chatify-muted">{{ formatTime(item.created_at) }}</span>
                <button
                  type="button"
                  class="chatify:text-chatify-muted hover:chatify:text-red-400"
                  title="Dismiss notification"
                  @click.stop="handleDelete(item)"
                >
                  <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Description: Truncated cleanly to max 2 lines with break-words & break-all -->
            <p class="chatify:line-clamp-2 chatify:break-words chatify:break-all chatify:text-xs chatify:leading-relaxed chatify:text-zinc-300">
              {{ item.body }}
            </p>

            <!-- Media / Graphic Banner Preview -->
            <div v-if="item.image_url" class="chatify:mt-1.5 chatify:overflow-hidden chatify:rounded-xl chatify:border chatify:border-white/10 chatify:bg-black/30">
              <!-- Border Gift Graphic Showcase -->
              <div v-if="item.data?.border_id" class="chatify:flex chatify:items-center chatify:gap-3 chatify:p-2.5">
                <div class="chatify:relative chatify:h-12 chatify:w-12 chatify:shrink-0">
                  <div class="chatify:h-full chatify:w-full chatify:rounded-full chatify:bg-zinc-800" />
                  <img
                    :src="item.image_url"
                    alt=""
                    class="chatify-avatar-border chatify:pointer-events-none chatify:absolute chatify:inset-[-10%] chatify:h-[120%] chatify:w-[120%] chatify:max-w-none chatify:object-contain chatify:z-10"
                  />
                </div>
                <div class="chatify:min-w-0 chatify:flex-1">
                  <span class="chatify:text-[9px] chatify:font-semibold chatify:uppercase chatify:text-amber-400">Exclusive Border Gift</span>
                  <p class="chatify:truncate chatify:text-xs chatify:font-bold chatify:text-white">{{ item.data.border_name }}</p>
                  <p class="chatify:truncate chatify:text-[10px] chatify:text-chatify-muted">Equip inside Profile settings.</p>
                </div>
              </div>

              <!-- General Announcement Image -->
              <img
                v-else
                :src="item.image_url"
                alt="Announcement attachment"
                class="chatify:max-h-40 chatify:w-full chatify:object-cover"
              />
            </div>

            <!-- Clickable 'View details' indicator at bottom of card -->
            <div class="chatify:flex chatify:items-center chatify:justify-end chatify:pt-1">
              <span class="chatify:inline-flex chatify:items-center chatify:gap-1 chatify:text-[11px] chatify:font-medium chatify:text-blue-400 group-hover:chatify:text-blue-300">
                <span>View details</span>
                <svg class="chatify:h-3 chatify:w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </BaseModal>
</template>
