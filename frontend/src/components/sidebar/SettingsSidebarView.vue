<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import SettingsRootView from '../settings/SettingsRootView.vue'
import SettingsThemesView from '../settings/SettingsThemesView.vue'
import SettingsFontView from '../settings/SettingsFontView.vue'
import SettingsWallpaperView from '../settings/SettingsWallpaperView.vue'
import SettingsBlockedContactsView from '../settings/SettingsBlockedContactsView.vue'
import { clonePreferences } from '../../composables/useBootConfig'
import type { ChatifyThemePreferences } from '../../themes/types'
import { useConfigStore } from '../../stores/config'
import { useSettingsStore } from '../../stores/settings'
import { useConversationsStore } from '../../stores/conversations'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

type SettingsScreen = 'root' | 'themes' | 'font' | 'wallpaper' | 'blocked'

const configStore = useConfigStore()
const settingsStore = useSettingsStore()
const conversationsStore = useConversationsStore()
const { t } = useChatifyI18n()
const { saving } = storeToRefs(settingsStore)
const { isDirty } = storeToRefs(configStore)

const screen = ref<SettingsScreen>('root')
const screenStack = ref<SettingsScreen[]>(['root'])
const direction = ref<'forward' | 'back'>('forward')
const draftSnapshots = ref<ChatifyThemePreferences[]>([])

const titles = computed<Record<SettingsScreen, string>>(() => ({
  root: t('ui.settings.title'),
  themes: t('ui.settings.themes'),
  font: t('ui.settings.layout_and_fonts_title', 'Layout & Fonts'),
  wallpaper: t('ui.settings.wallpaper'),
  blocked: t('ui.settings.blocked_contacts'),
}))

const screensWithFooter = new Set<SettingsScreen>(['themes', 'font', 'wallpaper'])

onMounted(() => {
  configStore.beginDraftSession()
  screen.value = 'root'
  screenStack.value = ['root']
  draftSnapshots.value = []
})

function navigate(next: SettingsScreen) {
  draftSnapshots.value.push(clonePreferences(configStore.draftPreferences))
  direction.value = 'forward'
  screenStack.value.push(next)
  screen.value = next
}

function goBack() {
  if (screenStack.value.length <= 1) {
    conversationsStore.setSidebarTab('chats')
    return
  }

  const snapshot = draftSnapshots.value.pop()
  if (snapshot) {
    configStore.restoreDraft(snapshot)
  }

  direction.value = 'back'
  screenStack.value.pop()
  screen.value = screenStack.value[screenStack.value.length - 1] ?? 'root'
}

function cancelChanges() {
  configStore.resetDraft()
  if (screen.value === 'root') {
    conversationsStore.setSidebarTab('chats')
    return
  }

  draftSnapshots.value = []
  screen.value = 'root'
  screenStack.value = ['root']
}

async function saveChanges() {
  const ok = await settingsStore.saveAll()
  if (ok) {
    draftSnapshots.value = []
    if (screen.value !== 'root') {
      screen.value = 'root'
      screenStack.value = ['root']
    }
  }
}
</script>

<template>
  <div class="chatify-sidebar-settings chatify:flex chatify:h-full chatify:min-h-0 chatify:w-full chatify:flex-col chatify:overflow-hidden">
    <!-- Sub-screen header if navigated deeper into settings -->
    <div
      v-if="screen !== 'root'"
      class="chatify-sidebar-divide chatify:flex chatify:shrink-0 chatify:items-center chatify:gap-2 chatify:border-b chatify:bg-chatify-sidebar chatify:px-3 chatify:py-2.5"
    >
      <button
        type="button"
        class="chatify:flex chatify:h-8 chatify:w-8 chatify:items-center chatify:justify-center chatify:rounded-full chatify:text-chatify-text chatify:transition hover:chatify:bg-white/10"
        :aria-label="t('ui.common.back')"
        @click="goBack"
      >
        <svg class="chatify:h-5 chatify:w-5 chatify:shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      <h3 class="chatify:min-w-0 chatify:flex-1 chatify:truncate chatify:text-sm chatify:font-semibold chatify:text-chatify-text">
        {{ titles[screen] }}
      </h3>
    </div>

    <!-- Scrollable content area following sidebar theme -->
    <div class="chatify:min-h-0 chatify:flex-1 chatify:overflow-y-auto chatify:p-3.5">
      <Transition :name="direction === 'forward' ? 'chatify-slide-left' : 'chatify-slide-right'" mode="out-in">
        <div :key="screen" class="chatify:min-h-0 chatify:min-w-0 chatify:w-full">
          <SettingsRootView v-if="screen === 'root'" @navigate="navigate" />
          <SettingsThemesView v-else-if="screen === 'themes'" />
          <SettingsFontView v-else-if="screen === 'font'" />
          <SettingsWallpaperView v-else-if="screen === 'wallpaper'" />
          <SettingsBlockedContactsView v-else-if="screen === 'blocked'" />
        </div>
      </Transition>
    </div>

    <!-- Footer actions for sub-screens with dirty draft settings -->
    <div
      v-if="screensWithFooter.has(screen)"
      class="chatify-sidebar-divide chatify:flex chatify:shrink-0 chatify:items-center chatify:justify-end chatify:gap-2 chatify:border-t chatify:bg-chatify-sidebar chatify:px-4 chatify:py-2.5"
    >
      <button
        type="button"
        class="chatify:rounded-lg chatify:px-3.5 chatify:py-1.5 chatify:text-xs chatify:text-chatify-muted chatify:transition hover:chatify:bg-white/5"
        :disabled="saving"
        @click="cancelChanges"
      >
        {{ $t('ui.common.cancel') }}
      </button>
      <button
        type="button"
        class="chatify:rounded-lg chatify:bg-chatify-primary chatify:px-4 chatify:py-1.5 chatify:text-xs chatify:font-semibold chatify:text-white chatify:shadow chatify:transition hover:chatify:opacity-95 disabled:chatify:opacity-50"
        :disabled="saving || !isDirty"
        @click="saveChanges"
      >
        {{ saving ? $t('ui.common.saving') : $t('ui.common.save') }}
      </button>
    </div>
  </div>
</template>

