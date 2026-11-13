<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import SettingsGroup from './SettingsGroup.vue'
import SettingsRow from './SettingsRow.vue'
import SettingsProfileHeader from './SettingsProfileHeader.vue'
import { getThemeById } from '../../themes/presets'
import { getFontById } from '../../themes/fonts'
import { getPatternById } from '../../themes/patterns'
import { useConfigStore } from '../../stores/config'
import { useContactsStore } from '../../stores/contacts'
import { useSettingsStore } from '../../stores/settings'
import { useConfirmStore } from '../../stores/confirm'
import { useUiStore } from '../../stores/ui'
import { usePwa } from '../../composables/usePwa'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

defineEmits<{
  navigate: [screen: 'themes' | 'font' | 'wallpaper' | 'blocked' | 'profile' | 'admin']
}>()

const configStore = useConfigStore()
const contactsStore = useContactsStore()
const settingsStore = useSettingsStore()
const confirmStore = useConfirmStore()
const uiStore = useUiStore()
const { isStandalone, canInstall, isIos } = usePwa()
const { t } = useChatifyI18n()
const { user, preferences, themesEnabled, fontsEnabled, wallpaperEnabled, showOnlineStatus } = storeToRefs(configStore)
const { blockedUsers } = storeToRefs(contactsStore)
const { loggingOut } = storeToRefs(settingsStore)

const isAdmin = computed(() => (user.value?.attributes.role ?? 'node') === 'admin')

const profileSubtitle = computed(() => {
  if (user.value?.attributes.username) {
    return `@${user.value.attributes.username}`
  }
  return user.value?.attributes.name ?? ''
})

const themeLabel = computed(() => getThemeById(preferences.value.themeId).name)
const layoutFontsLabel = computed(() => {
  const font = getFontById(preferences.value.fontFamily).label
  const scale = preferences.value.uiScale ?? 100
  return `${font} · ${scale}%`
})

const wallpaperLabel = computed(() => {
  const wallpaper = preferences.value.wallpaper
  if (wallpaper.kind === 'none') {
    return t('ui.settings.none')
  }

  if (wallpaper.kind === 'image') {
    return t('ui.settings.custom_photo')
  }

  return getPatternById(wallpaper.patternId)?.name ?? t('ui.settings.pattern')
})

const blockedContactsLabel = computed(() => {
  const count = blockedUsers.value.length
  if (count === 0) {
    return t('ui.settings.none')
  }

  return count === 1 ? t('ui.settings.contact_count') : t('ui.settings.contacts_count', { n: count })
})

async function toggleOnlineStatus() {
  await configStore.setShowOnlineStatus(!showOnlineStatus.value)
}

async function handleLogout() {
  if (loggingOut.value) {
    return
  }

  const confirmed = await confirmStore.confirm({
    title: t('ui.confirm.logout.title', 'Log Out'),
    message: t('ui.confirm.logout.message', 'Are you sure you want to end your session and log out?'),
    confirmLabel: t('ui.confirm.logout.confirm', 'Log Out'),
    variant: 'danger',
  })

  if (confirmed) {
    await settingsStore.logout()
  }
}
</script>

<template>
  <div>
    <SettingsProfileHeader @open="uiStore.openModal('profileInfo')" />

    <SettingsGroup>
      <SettingsRow
        :label="t('ui.settings.profile', 'Profile')"
        :value="profileSubtitle"
        chevron
        @click="$emit('navigate', 'profile')"
      >
        <template #icon>
          <svg
            class="chatify:h-4 chatify:w-4 chatify:shrink-0 chatify:text-chatify-primary"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
            />
          </svg>
        </template>
      </SettingsRow>

      <SettingsRow
        v-if="isAdmin"
        label="Admin Panel"
        value="Gift & Manage Borders"
        chevron
        @click="$emit('navigate', 'admin')"
      >
        <template #icon>
          <svg
            class="chatify:h-4 chatify:w-4 chatify:shrink-0 chatify:text-chatify-primary"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
            />
          </svg>
        </template>
      </SettingsRow>
      <SettingsRow
        :label="t('ui.settings.show_online_status')"
        :value="showOnlineStatus ? t('ui.settings.on') : t('ui.settings.off')"
        toggle
        :toggle-on="showOnlineStatus"
        @toggle="toggleOnlineStatus"
      >
        <template #icon>
          <svg
            class="chatify:h-4 chatify:w-4 chatify:shrink-0 chatify:text-chatify-primary"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.6 5.6a9 9 0 000 12.8m12.8 0a9 9 0 000-12.8" />
          </svg>
        </template>
      </SettingsRow>
      <SettingsRow
        v-if="themesEnabled"
        :label="t('ui.settings.themes')"
        :value="themeLabel"
        chevron
        @click="$emit('navigate', 'themes')"
      >
        <template #icon>
          <svg
            class="chatify:h-4 chatify:w-4 chatify:shrink-0 chatify:text-chatify-primary"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"
            />
          </svg>
        </template>
      </SettingsRow>
      <SettingsRow
        v-if="fontsEnabled"
        :label="t('ui.settings.layout_and_fonts', 'Layout & Fonts')"
        :value="layoutFontsLabel"
        chevron
        @click="$emit('navigate', 'font')"
      >
        <template #icon>
          <svg
            class="chatify:h-4 chatify:w-4 chatify:shrink-0 chatify:text-chatify-primary"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7V5h16v2M12 5v14M8.5 19h7" />
          </svg>
        </template>
      </SettingsRow>
      <SettingsRow
        v-if="wallpaperEnabled"
        :label="t('ui.settings.wallpaper')"
        :value="wallpaperLabel"
        chevron
        @click="$emit('navigate', 'wallpaper')"
      >
        <template #icon>
          <svg
            class="chatify:h-4 chatify:w-4 chatify:shrink-0 chatify:text-chatify-primary"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
        </template>
      </SettingsRow>
      <SettingsRow
        :label="t('ui.settings.blocked_contacts')"
        :value="blockedContactsLabel"
        chevron
        @click="$emit('navigate', 'blocked')"
      >
        <template #icon>
          <svg
            class="chatify:h-4 chatify:w-4 chatify:shrink-0 chatify:text-chatify-primary"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.6 5.6l12.8 12.8" />
          </svg>
        </template>
      </SettingsRow>
      <SettingsRow
        label="Install Application"
        :value="isStandalone ? 'Installed (PWA)' : (canInstall || isIos ? 'Install PWA' : 'Available')"
        chevron
        @click="uiStore.openModal('pwaInstall')"
      >
        <template #icon>
          <svg class="chatify:h-4 chatify:w-4 chatify:shrink-0 chatify:text-chatify-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
        </template>
      </SettingsRow>
    </SettingsGroup>

    <SettingsGroup class="chatify:mt-3">
      <SettingsRow
        :label="loggingOut ? t('ui.settings.logging_out', 'Logging out...') : t('ui.settings.logout', 'Log Out')"
        danger
        :disabled="loggingOut"
        @click="handleLogout"
      >
        <template #icon>
          <svg
            class="chatify:h-4 chatify:w-4 chatify:shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
            />
          </svg>
        </template>
      </SettingsRow>
    </SettingsGroup>
  </div>
</template>
