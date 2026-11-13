<script setup lang="ts">
import { computed, ref } from 'vue'
import appLogo from '../../assets/images/logo.png'
import { useUiStore } from '../../stores/ui'
import { usePwa } from '../../composables/usePwa'
import BaseModal from './BaseModal.vue'

const uiStore = useUiStore()
const { isStandalone, isIos, isAndroid, canInstall, installApp } = usePwa()
const modalLogoSrc = ref<string>(appLogo)

function onLogoError() {
  if (modalLogoSrc.value === appLogo) {
    modalLogoSrc.value = '/icons/icon-192x192.png'
  } else if (modalLogoSrc.value === '/icons/icon-192x192.png') {
    modalLogoSrc.value = '/vendor/chatify/logo.png'
  }
}

const isOpen = computed(() => uiStore.activeModal === 'pwaInstall')

function close() {
  uiStore.closeModal()
}

async function handleInstall() {
  const installed = await installApp()
  if (installed) {
    close()
  }
}
</script>

<template>
  <BaseModal
    :open="isOpen"
    title="Install V3RCHAT Application"
    size="sm"
    @close="close"
  >
    <div class="chatify:flex chatify:flex-col chatify:items-center chatify:text-center chatify:p-2">
      <!-- App Icon with glow -->
      <div class="chatify:relative chatify:mb-4 chatify:flex chatify:h-20 chatify:w-20 chatify:items-center chatify:justify-center chatify:rounded-2xl chatify:bg-black/60 chatify:border chatify:border-white/15 chatify:shadow-2xl">
        <img
          :src="modalLogoSrc"
          alt="V3RCHAT Logo"
          class="chatify:h-14 chatify:w-14 chatify:object-contain chatify:drop-shadow-[0_0_12px_rgba(255,255,255,0.25)]"
          @error="onLogoError"
        />
        <span class="chatify:absolute chatify:-bottom-1 chatify:-right-1 chatify:flex chatify:h-5 chatify:items-center chatify:rounded-full chatify:bg-chatify-primary-dark chatify:px-1.5 chatify:text-[9px] chatify:font-bold chatify:text-white chatify:shadow-md">
          PWA
        </span>
      </div>

      <h3 class="chatify:text-lg chatify:font-bold chatify:text-chatify-text">
        V3RCHAT for Mobile & Desktop
      </h3>
      <p class="chatify:mt-1 chatify:text-xs chatify:text-chatify-muted chatify:leading-relaxed chatify:max-w-xs">
        Experience seamless fullscreen communication, safe area notch integration, and zero address bar clutter.
      </p>

      <!-- Already Installed Badge -->
      <div
        v-if="isStandalone"
        class="chatify:mt-4 chatify:inline-flex chatify:items-center chatify:gap-2 chatify:rounded-full chatify:bg-emerald-500/15 chatify:border chatify:border-emerald-500/30 chatify:px-3 chatify:py-1 chatify:text-xs chatify:font-semibold chatify:text-emerald-400"
      >
        <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
        Installed in Standalone Mode
      </div>

      <!-- iOS Instructions -->
      <div
        v-else-if="isIos"
        class="chatify:mt-4 chatify:w-full chatify:rounded-xl chatify:bg-white/5 chatify:border chatify:border-white/10 chatify:p-3.5 chatify:text-start"
      >
        <p class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text chatify:mb-2 chatify:flex chatify:items-center chatify:gap-1.5">
          <svg class="chatify:h-4 chatify:w-4 chatify:text-chatify-primary" fill="currentColor" viewBox="0 0 24 24">
            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.62-.75 1.04-1.8 0.92-2.85-.9.04-2 .6-2.64 1.35-.56.65-.96 1.7-0.83 2.73.99.08 2.03-.54 2.55-1.23z"/>
          </svg>
          Add to iPhone Home Screen
        </p>
        <ol class="chatify:space-y-2 chatify:text-xs chatify:text-chatify-muted chatify:ps-4 chatify:list-decimal">
          <li>
            Tap the <span class="chatify:font-semibold chatify:text-chatify-text">Share</span> button
            <span class="chatify:inline-block chatify:rounded chatify:bg-white/10 chatify:px-1.5 chatify:py-0.5 chatify:text-[11px] chatify:align-baseline">
              <svg class="chatify:inline chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
              </svg>
            </span> in Safari's bottom toolbar.
          </li>
          <li>
            Scroll down and select <span class="chatify:font-semibold chatify:text-chatify-text">Add to Home Screen</span> (+).
          </li>
          <li>
            Tap <span class="chatify:font-semibold chatify:text-chatify-text">Add</span> in the top right corner.
          </li>
        </ol>
      </div>

      <!-- Android / Desktop Install Prompt -->
      <div v-else-if="canInstall" class="chatify:mt-4 chatify:w-full">
        <button
          type="button"
          class="chatify:flex chatify:w-full chatify:items-center chatify:justify-center chatify:gap-2 chatify:rounded-xl chatify:bg-chatify-primary-dark chatify:py-2.5 chatify:text-sm chatify:font-semibold chatify:text-white chatify:shadow-lg chatify:transition hover:chatify:opacity-90 active:chatify:scale-[0.98]"
          @click="handleInstall"
        >
          <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Install Application
        </button>
      </div>

      <!-- Generic Desktop Browser Info -->
      <div
        v-else
        class="chatify:mt-4 chatify:w-full chatify:rounded-xl chatify:bg-white/5 chatify:border chatify:border-white/10 chatify:p-3 chatify:text-xs chatify:text-chatify-muted"
      >
        To install on desktop, click the install icon located on the right side of your browser's address bar.
      </div>
    </div>
  </BaseModal>
</template>

