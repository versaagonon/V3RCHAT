import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'
import { preferencesForServer } from '../composables/useBootConfig'
import { chatifyT } from '../i18n/nonComponent'
import { extractErrorMessage } from '../utils/errors'
import { useConfigStore } from './config'
import { useToastStore } from './toast'

export const useSettingsStore = defineStore('settings', () => {
  const saving = ref(false)
  const loggingOut = ref(false)

  const configStore = useConfigStore()
  const toastStore = useToastStore()

  async function saveAll() {
    if (!configStore.api) {
      return false
    }

    saving.value = true

    try {
      if (configStore.pendingWallpaperFile) {
        const form = new FormData()
        form.append('background', configStore.pendingWallpaperFile)
        const { data } = await configStore.api.uploadChatBackground(form)
        const url = data.data.attributes.chat_background_url
        if (url) {
          configStore.draftPreferences.wallpaper.imageUrl = `${url}${url.includes('?') ? '&' : '?'}t=${Date.now()}`
          configStore.draftPreferences.wallpaper.kind = 'image'
        }
      }

      await configStore.api.patchSettings({
        theme_preferences: preferencesForServer(configStore.draftPreferences),
      })

      configStore.commitSaved(configStore.draftPreferences)
      return true
    } catch (err) {
      toastStore.show({
        message: extractErrorMessage(err, chatifyT('ui.errors.failed_save_settings')),
        icon: 'error',
        placement: 'bottom',
      })
      return false
    } finally {
      saving.value = false
    }
  }

  async function logout() {
    if (loggingOut.value) {
      return
    }

    loggingOut.value = true

    try {
      const csrfToken = configStore.boot?.csrfToken
        || (typeof document !== 'undefined' ? (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content : '')
        || ''

      const logoutUrl = configStore.boot?.logoutUrl || '/logout'

      await axios.post(logoutUrl, {}, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
        },
      })
    } catch {
      // In case session expired or network error, proceed to redirect
    } finally {
      if (typeof window !== 'undefined') {
        const isStandalone =
          (typeof window.matchMedia === 'function' ? window.matchMedia('(display-mode: standalone)').matches : false) ||
          (window.navigator as unknown as { standalone?: boolean })?.standalone === true ||
          (typeof document !== 'undefined' && Boolean(document.referrer?.includes('android-app://'))) ||
          Boolean(window.location?.search && new URLSearchParams(window.location.search).get('pwa') === '1')

        // Purge runtime caches & storage on logout
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
          navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_DATA' })
        }
        try {
          sessionStorage.clear()
        } catch {
          // ignore
        }

        // PWA navigation restriction: inside PWA, unauthenticated users must land on /login
        window.location.href = isStandalone ? (configStore.boot?.loginUrl ?? '/login?pwa=1') : '/'
      }
    }
  }

  return {
    saving,
    loggingOut,
    saveAll,
    logout,
  }
})
