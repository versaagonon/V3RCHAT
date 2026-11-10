<script setup lang="ts">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useConfigStore } from '../../stores/config'
import { useConnectionStore } from '../../stores/connection'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import { sidebarSubtitleLabel } from '../../utils/connectionLabel'
import ConversationList from './ConversationList.vue'
import ConversationSearch from './ConversationSearch.vue'
import FavoriteContactsList from './FavoriteContactsList.vue'
import SettingsSidebarView from './SettingsSidebarView.vue'
import UserAvatar from '../ui/UserAvatar.vue'
import LiquidGlassButton from '../ui/LiquidGlassButton.vue'

const configStore = useConfigStore()
const connectionStore = useConnectionStore()
const uiStore = useUiStore()
const conversationsStore = useConversationsStore()
const { user, groupsEnabled } = storeToRefs(configStore)
const { sidebarTab } = storeToRefs(conversationsStore)
const { uiState } = storeToRefs(connectionStore)

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
</script>

<template>
  <div class="chatify:flex chatify:h-full chatify:min-h-0 chatify:flex-col">
    <header v-if="sidebarTab === 'settings'" class="chatify-sidebar-divide chatify:flex chatify:shrink-0 chatify:items-center chatify:justify-between chatify:border-b chatify:bg-chatify-sidebar chatify:px-4 chatify:py-3.5">
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

    <header v-else class="chatify-sidebar-divide chatify:flex chatify:shrink-0 chatify:items-center chatify:gap-3 chatify:border-b chatify:bg-chatify-sidebar chatify:px-4 chatify:py-3">
      <UserAvatar v-if="user" size="sm" :alt="user.attributes.name" />
      <div class="chatify:min-w-0 chatify:flex-1">
        <p class="chatify:truncate chatify:text-sm chatify:font-semibold">{{ user?.attributes.name }}</p>
        <p
          class="chatify:text-xs chatify:text-chatify-muted"
          :class="{ 'chatify:animate-pulse': uiState !== 'online' }"
        >
          {{ sidebarSubtitle }}
        </p>
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
    </header>

    <div class="chatify:flex chatify:min-h-0 chatify:flex-1 chatify:flex-col chatify:overflow-hidden">
      <template v-if="sidebarTab === 'chats'">
        <ConversationSearch />
        <ConversationList />
      </template>

      <FavoriteContactsList v-else-if="sidebarTab === 'favorites'" />

      <SettingsSidebarView v-else-if="sidebarTab === 'settings'" />
    </div>

    <div class="chatify-sidebar-bottom-dock">
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
</template>
