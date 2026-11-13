import { ref, computed } from 'vue'

export interface BeforeInstallPromptEvent extends Event {
  readonly platforms: string[]
  readonly userChoice: Promise<{
    outcome: 'accepted' | 'dismissed'
    platform: string
  }>
  prompt(): Promise<void>
}

const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null)
const isInstalled = ref(false)
const isSwRegistered = ref(false)
const hasUpdate = ref(false)
let swRegistration: ServiceWorkerRegistration | null = null

function checkStandalone(): boolean {
  if (typeof window === 'undefined') return false
  try {
    const isMatchMediaStandalone = typeof window.matchMedia === 'function' ? window.matchMedia('(display-mode: standalone)').matches : false
    const isIosStandalone = Boolean((window.navigator as unknown as { standalone?: boolean })?.standalone)
    const isAndroidReferrer = Boolean(typeof document !== 'undefined' && document.referrer?.includes('android-app://'))
    const isQueryPwa = Boolean(window.location?.search && new URLSearchParams(window.location.search).get('pwa') === '1')
    return isMatchMediaStandalone || isIosStandalone || isAndroidReferrer || isQueryPwa
  } catch {
    return false
  }
}

export function usePwa() {
  const isStandalone = ref(checkStandalone())

  const isIos = computed(() => {
    if (typeof navigator === 'undefined') return false
    return /iphone|ipad|ipod/i.test(navigator.userAgent) && !isStandalone.value
  })

  const isAndroid = computed(() => {
    if (typeof navigator === 'undefined') return false
    return /android/i.test(navigator.userAgent)
  })

  const canInstall = computed(() => {
    return !isStandalone.value && !isInstalled.value && (Boolean(deferredPrompt.value) || isIos.value)
  })

  async function installApp(): Promise<boolean> {
    if (!deferredPrompt.value) {
      return false
    }

    try {
      await deferredPrompt.value.prompt()
      const { outcome } = await deferredPrompt.value.userChoice
      if (outcome === 'accepted') {
        isInstalled.value = true
        deferredPrompt.value = null
        return true
      }
      return false
    } catch (err) {
      console.warn('[V3RCHAT PWA] Install prompt error:', err)
      return false
    }
  }

  function applyUpdate() {
    if (swRegistration?.waiting) {
      swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' })
    }
  }

  function clearPwaData() {
    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
      navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_DATA' })
    }
  }

  return {
    isStandalone,
    isInstalled,
    isIos,
    isAndroid,
    canInstall,
    hasUpdate,
    installApp,
    applyUpdate,
    clearPwaData,
  }
}

export function initPwaLifecycle() {
  if (typeof window === 'undefined') return

  // 1. Detect standalone mode changes
  const mql = window.matchMedia('(display-mode: standalone)')
  mql.addEventListener('change', (e) => {
    if (e.matches) {
      isInstalled.value = true
    }
  })

  if (checkStandalone()) {
    isInstalled.value = true
  }

  // 2. Capture Chrome/Android beforeinstallprompt
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    deferredPrompt.value = e as BeforeInstallPromptEvent
  })

  window.addEventListener('appinstalled', () => {
    isInstalled.value = true
    deferredPrompt.value = null
    console.log('[V3RCHAT PWA] Successfully installed to Home Screen / System.')
  })

  // 3. Register Service Worker in production (and allow dev if supported)
  if ('serviceWorker' in navigator) {
    window.addEventListener('DOMContentLoaded', async () => {
      try {
        const reg = await navigator.serviceWorker.register('/sw.js', {
          scope: '/',
        })
        swRegistration = reg
        isSwRegistered.value = true

        reg.addEventListener('updatefound', () => {
          const newWorker = reg.installing
          if (!newWorker) return
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              hasUpdate.value = true
              console.log('[V3RCHAT PWA] New update available.')
            }
          })
        })

        let refreshing = false
        navigator.serviceWorker.addEventListener('controllerchange', () => {
          if (!refreshing) {
            refreshing = true
            window.location.reload()
          }
        })
      } catch (err) {
        console.warn('[V3RCHAT PWA] Service Worker registration failed:', err)
      }
    })
  }
}

