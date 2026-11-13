<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useBreakpoints } from '../../composables/useBreakpoints'
import { useConfigStore } from '../../stores/config'
import { useConnectionStore } from '../../stores/connection'
import { useConversationsStore } from '../../stores/conversations'
import { useNotificationsStore } from '../../stores/notifications'
import { useConfirmStore } from '../../stores/confirm'
import { useUiStore } from '../../stores/ui'
import { sidebarSubtitleLabel } from '../../utils/connectionLabel'
import ConversationList from './ConversationList.vue'
import ConversationSearch from './ConversationSearch.vue'
import FavoriteContactsList from './FavoriteContactsList.vue'
import SettingsSidebarView from './SettingsSidebarView.vue'
import NotificationsSidebarView from './NotificationsSidebarView.vue'
import UserAvatar from '../ui/UserAvatar.vue'
import UserNameplate from '../ui/UserNameplate.vue'
import LiquidGlassButton from '../ui/LiquidGlassButton.vue'
import PwaInstallBanner from '../ui/PwaInstallBanner.vue'

const { isMobile } = useBreakpoints()
const configStore = useConfigStore()
const connectionStore = useConnectionStore()
const uiStore = useUiStore()
const conversationsStore = useConversationsStore()
const notificationsStore = useNotificationsStore()
const confirmStore = useConfirmStore()
const { user, groupsEnabled } = storeToRefs(configStore)
const { sidebarTab } = storeToRefs(conversationsStore)
const { uiState } = storeToRefs(connectionStore)
const { notifications, totalBadgeCount } = storeToRefs(notificationsStore)

const sidebarSubtitle = computed(() => sidebarSubtitleLabel(uiState.value))

const navGlareX = ref('50%')
const navGlareY = ref('50%')
const navGlareOpacity = ref('0.65')

function onNavPointerMove(event: PointerEvent) {
  const target = event.currentTarget as HTMLElement | null
  if (!target) return
  const rect = target.getBoundingClientRect()
  const x = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100))
  const y = Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100))
  navGlareX.value = `${Math.round(x)}%`
  navGlareY.value = `${Math.round(y)}%`
  navGlareOpacity.value = '0.95'
}

function onNavPointerLeave() {
  navGlareX.value = '50%'
  navGlareY.value = '50%'
  navGlareOpacity.value = '0.65'
}

/* Notifications overflow (kebab) menu */
const notificationsMenuOpen = ref(false)
const notificationsMenuRef = ref<HTMLElement | null>(null)
const hasNotifications = computed(() => notifications.value.length > 0)
const hasUnread = computed(() => totalBadgeCount.value > 0)

function toggleNotificationsMenu() {
  notificationsMenuOpen.value = !notificationsMenuOpen.value
}

function closeNotificationsMenu() {
  notificationsMenuOpen.value = false
}

function onNotificationsMenuPointerDown(event: PointerEvent) {
  if (!notificationsMenuOpen.value) return
  const root = notificationsMenuRef.value
  if (root && !root.contains(event.target as Node)) {
    notificationsMenuOpen.value = false
  }
}

function onNotificationsMenuKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    notificationsMenuOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('pointerdown', onNotificationsMenuPointerDown)
  document.addEventListener('keydown', onNotificationsMenuKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onNotificationsMenuPointerDown)
  document.removeEventListener('keydown', onNotificationsMenuKeydown)
})

async function handleMarkAllRead() {
  closeNotificationsMenu()
  await notificationsStore.markAllRead()
}

async function handleDeleteAllNotifications() {
  closeNotificationsMenu()
  const confirmed = await confirmStore.confirm({
    title: 'Delete all notifications?',
    message: 'Every notification will be permanently removed. This action cannot be undone.',
    confirmLabel: 'Delete all',
    variant: 'danger',
  })
  if (!confirmed) return
  await notificationsStore.removeAllNotifications()
}
</script>

<template>
  <div class="chatify-sidebar-shell chatify:relative chatify:flex chatify:h-full chatify:min-h-0 chatify:flex-col">
    <!-- Header: Notifications View -->
    <header
      v-if="sidebarTab === 'notifications'"
      class="chatify-sidebar-divide chatify:flex chatify:shrink-0 chatify:items-center chatify:justify-between chatify:border-b chatify:bg-chatify-sidebar chatify:px-3.5 chatify:py-3"
      style="padding-top: max(0.75rem, env(safe-area-inset-top, 0.75rem));"
    >
      <div class="chatify:flex chatify:items-center chatify:gap-2.5">
        <LiquidGlassButton
          aria-label="Back to conversations"
          title="Back"
          size="2.35rem"
          @click="conversationsStore.setSidebarTab('chats')"
        >
          <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </LiquidGlassButton>
        <div class="chatify:flex chatify:items-center chatify:gap-2">
          <h2 class="chatify:text-base chatify:font-bold chatify:text-chatify-text">Notifications</h2>  
        </div>
      </div>
      <div ref="notificationsMenuRef" class="chatify:relative chatify:z-40 chatify:shrink-0">
        <LiquidGlassButton
          aria-label="Notification options"
          title="Notification options"
          size="2.35rem"
          @click="toggleNotificationsMenu"
        >
          <svg class="chatify:h-5 chatify:w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="5" r="1.7" />
            <circle cx="12" cy="12" r="1.7" />
            <circle cx="12" cy="19" r="1.7" />
          </svg>
        </LiquidGlassButton>

        <Transition name="chatify-notification-menu-pop">
          <div
            v-if="notificationsMenuOpen"
            class="chatify-notification-menu"
            role="menu"
            aria-label="Notification options"
          >
            <button
              type="button"
              role="menuitem"
              class="chatify-notification-menu-item"
              :disabled="!hasUnread"
              @click="handleMarkAllRead"
            >
              <svg class="chatify:h-4 chatify:w-4 chatify:shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span>Mark all read</span>
            </button>

            <button
              type="button"
              role="menuitem"
              class="chatify-notification-menu-item is-danger"
              :disabled="!hasNotifications"
              @click="handleDeleteAllNotifications"
            >
              <svg class="chatify:h-4 chatify:w-4 chatify:shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              <span>Delete all notifications</span>
            </button>
          </div>
        </Transition>
      </div>
    </header>

    <header
      v-else-if="sidebarTab === 'settings'"
      class="chatify-sidebar-divide chatify:flex chatify:shrink-0 chatify:items-center chatify:justify-between chatify:border-b chatify:bg-chatify-sidebar chatify:px-4 chatify:py-3.5"
      style="padding-top: max(0.75rem, env(safe-area-inset-top, 0.75rem));"
    >
      <div class="chatify:flex chatify:items-center chatify:gap-2">
        <h2 class="chatify:text-base chatify:font-bold chatify:text-chatify-text">{{ $t('ui.settings.title') }}</h2>
      </div>
      <button
        type="button"
        class="chatify:flex chatify:h-8 chatify:w-8 chatify:items-center chatify:justify-center chatify:rounded-full chatify:text-chatify-muted chatify:transition hover:chatify:bg-white/10 hover:chatify:text-chatify-text"
        :aria-label="$t('ui.common.close')"
        @click="conversationsStore.setSidebarTab('chats')"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </header>

    <header
      v-else
      class="chatify-sidebar-divide chatify:relative chatify:flex chatify:shrink-0 chatify:items-center chatify:overflow-hidden chatify:border-b chatify:bg-chatify-sidebar chatify:px-3.5 chatify:py-3"
      style="padding-top: max(0.75rem, env(safe-area-inset-top, 0.75rem));"
    >
      <!-- Equipped nameplate as the header backdrop. Without one the header keeps
           its plain theme surface, so the layout is unchanged for users who never
           equipped a plate. -->
      <UserNameplate
        :nameplate="user?.attributes.nameplate ?? null"
        mode="fill"
        fit="cover"
      />

      <div class="chatify:relative chatify:z-10 chatify:flex chatify:w-full chatify:min-w-0 chatify:items-center chatify:gap-3">
        <button
          v-if="user"
          type="button"
          class="chatify:flex chatify:min-w-0 chatify:flex-1 chatify:items-center chatify:gap-3 chatify:rounded-xl chatify:px-1 chatify:py-0.5 chatify:text-start chatify:transition hover:chatify:bg-white/[0.06] active:chatify:scale-[0.99]"
          :aria-label="$t('ui.settings.profile', 'Profile')"
          :title="$t('ui.settings.profile', 'Profile')"
          @click="uiStore.openModal('profileInfo')"
        >
          <UserAvatar size="sm" :alt="user.attributes.name" />
          <div class="chatify:min-w-0 chatify:flex-1">
            <p class="chatify:truncate chatify:text-sm chatify:font-semibold">{{ user.attributes.name }}</p>
            <p
              class="chatify:truncate chatify:text-xs chatify:text-chatify-muted"
              :class="{ 'chatify:animate-pulse': uiState !== 'online' }"
            >
              {{ sidebarSubtitle }}
            </p>
          </div>
        </button>

        <!-- Action buttons group: Notification Bell, New Chat, Create Group, Desktop Collapse -->
        <div class="chatify:flex chatify:shrink-0 chatify:items-center chatify:gap-1.5">
          <!-- Notifications Bell Button Container with Outer Floating Badge -->
          <div class="chatify:group chatify:relative chatify:inline-flex chatify:shrink-0 chatify:items-center chatify:justify-center">
            <LiquidGlassButton
              :aria-label="$t('ui.sidebar.notifications', 'Notifications')"
              :title="$t('ui.sidebar.notifications', 'Notifications')"
              size="2.35rem"
              @click="conversationsStore.setSidebarTab('notifications')"
            >
              <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
            </LiquidGlassButton>

            <!-- Compact Deep-Red Notification Counter Badge that follows the glass button on hover -->
            <span
              v-if="totalBadgeCount > 0"
              class="chatify:pointer-events-none chatify:absolute chatify:top-0.5 chatify:right-0.5 chatify:z-30 chatify:flex chatify:h-3.5 chatify:min-w-[0.875rem] chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-red-700 chatify:px-1 chatify:text-[8px] chatify:font-bold chatify:leading-none chatify:text-white chatify:shadow-sm chatify:ring-1.5 chatify:ring-[#09090b] chatify:transition-transform chatify:duration-200 chatify:ease-out group-hover:chatify:-translate-y-[1.5px] group-hover:chatify:scale-[1.07] group-active:chatify:translate-y-0.5 group-active:chatify:scale-[0.94]"
            >
              {{ totalBadgeCount > 99 ? '99+' : totalBadgeCount }}
            </span>
          </div>

          <LiquidGlassButton
            :aria-label="$t('ui.sidebar.new_chat')"
            :title="$t('ui.sidebar.new_chat')"
            size="2.35rem"
            @click="uiStore.openModal('newChat')"
          >
            <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </LiquidGlassButton>
          <LiquidGlassButton
            v-if="groupsEnabled"
            :aria-label="$t('ui.sidebar.create_group')"
            :title="$t('ui.sidebar.create_group')"
            size="2.35rem"
            @click="uiStore.openModal('createGroup')"
          >
            <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </LiquidGlassButton>
        </div>
      </div>
    </header>

    <div class="chatify:flex chatify:min-h-0 chatify:flex-1 chatify:flex-col chatify:overflow-hidden">
      <template v-if="sidebarTab === 'chats'">
        <ConversationSearch />
        <ConversationList />
      </template>

      <FavoriteContactsList v-else-if="sidebarTab === 'favorites'" />

      <SettingsSidebarView v-else-if="sidebarTab === 'settings'" />

      <NotificationsSidebarView v-else-if="sidebarTab === 'notifications'" @back="conversationsStore.setSidebarTab('chats')" />
    </div>

    <!--
      Floating bottom area.
      The install banner and navigation dock are absolutely positioned over the
      conversation list so rows scroll behind the glass instead of ending on a
      separate opaque band.
    -->
    <div class="chatify-sidebar-bottom-area">
      <!-- PWA Install Banner floating directly above the navigation bar -->
      <PwaInstallBanner />

      <div class="chatify-sidebar-bottom-dock" style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom, 0.5rem));">
        <nav
          class="chatify-bottom-bar chatify-sidebar-bottom-nav chatify-sidebar-floating-nav liquid-glass-floating-nav"
          :style="{
            '--glare-x': navGlareX,
            '--glare-y': navGlareY,
            '--glare-opacity': navGlareOpacity,
          }"
          :aria-label="$t('ui.sidebar.navigation')"
          @pointermove="onNavPointerMove"
          @pointerleave="onNavPointerLeave"
        >
          <span class="liquid-glass-refraction-fringe" aria-hidden="true" />
          <span class="liquid-glass-specular" aria-hidden="true" />
          <span class="liquid-glass-nav-glare" aria-hidden="true" />
          <span class="liquid-glass-nav-rim" aria-hidden="true" />

          <!-- Single Sliding Jelly Indicator Pill (3-tab responsive slider) -->
          <span
            class="liquid-glass-sliding-pill"
            :class="{
              'pos-chats': sidebarTab === 'chats',
              'pos-favorites': sidebarTab === 'favorites',
              'pos-settings': sidebarTab === 'settings',
              'chatify:opacity-0': sidebarTab === 'notifications',
            }"
            aria-hidden="true"
          >
            <span class="sliding-pill-glow" />
            <span class="sliding-pill-rim" />
          </span>

          <!-- Tab 1: Chats -->
          <button
            type="button"
            class="chatify-sidebar-nav-item liquid-glass-nav-tab"
            :class="{ 'chatify-sidebar-nav-item-active': sidebarTab === 'chats' }"
            :aria-label="$t('ui.sidebar.chats')"
            :title="$t('ui.sidebar.chats')"
            @click="conversationsStore.setSidebarTab('chats')"
          >
            <svg class="chatify:h-5.5 chatify:w-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </button>

          <!-- Tab 2: Favorites -->
          <button
            type="button"
            class="chatify-sidebar-nav-item liquid-glass-nav-tab"
            :class="{ 'chatify-sidebar-nav-item-active': sidebarTab === 'favorites' }"
            :aria-label="$t('ui.sidebar.favorites')"
            :title="$t('ui.sidebar.favorites')"
            @click="conversationsStore.setSidebarTab('favorites')"
          >
            <svg class="chatify:h-5.5 chatify:w-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>
          </button>

          <!-- Tab 3: Settings (after Favorites) -->
          <button
            type="button"
            class="chatify-sidebar-nav-item liquid-glass-nav-tab"
            :class="{ 'chatify-sidebar-nav-item-active': sidebarTab === 'settings' }"
            :aria-label="$t('ui.sidebar.settings')"
            :title="$t('ui.sidebar.settings')"
            @click="conversationsStore.setSidebarTab('settings')"
          >
            <svg class="chatify:h-5.5 chatify:w-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>
