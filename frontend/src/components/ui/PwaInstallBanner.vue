<script setup lang="ts">
import { ref, computed } from 'vue'
import appLogo from '../../assets/images/logo.png'
import { usePwa } from '../../composables/usePwa'
import { useUiStore } from '../../stores/ui'
import LiquidGlassButton from './LiquidGlassButton.vue'

const { isStandalone, canInstall, isIos, installApp } = usePwa()
const uiStore = useUiStore()
const dismissed = ref(false)

const logoSrc = ref<string>(appLogo)

function onLogoError() {
  if (logoSrc.value === appLogo) {
    logoSrc.value = '/icons/icon-96x96.png'
  } else if (logoSrc.value === '/icons/icon-96x96.png') {
    logoSrc.value = '/vendor/chatify/logo.png'
  }
}

const showBanner = computed(() => {
  if (dismissed.value || isStandalone.value) return false
  return canInstall.value || isIos.value
})

async function onInstallClick() {
  if (isIos.value) {
    uiStore.openModal('pwaInstall')
  } else {
    const success = await installApp()
    if (!success) {
      uiStore.openModal('pwaInstall')
    }
  }
}

function dismiss() {
  dismissed.value = true
}
</script>

<template>
  <Transition name="chatify-install-dock">
    <div
      v-if="showBanner"
      class="chatify-pwa-dock-banner chatify-install-glass chatify:relative chatify:z-20 chatify:flex chatify:items-center chatify:justify-between chatify:gap-2.5 chatify:px-3.5 chatify:py-2"
    >
      <!-- Logo + Text block -->
      <div class="chatify:flex chatify:min-w-0 chatify:flex-1 chatify:items-center chatify:gap-2.5">
        <img
          :src="logoSrc"
          alt="V3RCHAT Logo"
          class="chatify:h-8 chatify:w-8 chatify:shrink-0 chatify:rounded-lg chatify:border chatify:border-white/10 chatify:object-cover chatify:shadow-sm"
          @error="onLogoError"
        />
        <div class="chatify:min-w-0 chatify:flex-1">
          <p class="chatify:truncate chatify:text-xs chatify:font-semibold chatify:text-white">
            Install V3RCHAT
          </p>
          <p class="chatify:truncate chatify:text-[11px] chatify:text-zinc-400">
            Fast, fullscreen & distraction-free
          </p>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="chatify:flex chatify:shrink-0 chatify:items-center chatify:gap-1.5">
        <LiquidGlassButton
          :aria-label="'Install'"
          :title="'Install'"
          size="1.75rem"
          width="4.25rem"
          @click="onInstallClick"
        >
          <span class="chatify:text-[11.5px] chatify:font-medium chatify:leading-none">Install</span>
        </LiquidGlassButton>
        <button
          type="button"
          class="chatify:flex chatify:h-6 chatify:w-6 chatify:items-center chatify:justify-center chatify:rounded-full chatify:text-zinc-400 chatify:transition hover:chatify:bg-white/10 hover:chatify:text-white"
          aria-label="Dismiss banner"
          title="Dismiss"
          @click="dismiss"
        >
          <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.chatify-install-dock-enter-active,
.chatify-install-dock-leave-active {
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.chatify-install-dock-enter-from,
.chatify-install-dock-leave-to {
  opacity: 0;
  transform: translateY(6px);
}
</style>
