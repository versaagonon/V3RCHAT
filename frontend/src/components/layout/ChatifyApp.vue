<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useBreakpoints } from '../../composables/useBreakpoints'
import { useSidebarResize } from '../../composables/useSidebarResize'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import Sidebar from '../sidebar/Sidebar.vue'
import ThreadPanel from '../thread/ThreadPanel.vue'

const { isMobile, isTablet, isDesktop } = useBreakpoints()
const { sidebarWidth, isResizing, startResize, resetWidth } = useSidebarResize()
const uiStore = useUiStore()
const conversationsStore = useConversationsStore()
const { showThreadOnMobile, mobileDrawerOpen } = storeToRefs(uiStore)

// Prevent background scroll when mobile drawer is open
watch(mobileDrawerOpen, (isOpen) => {
  if (typeof document !== 'undefined') {
    if (isOpen) {
      document.body.style.overflow = 'hidden'
      document.body.style.touchAction = 'none'
    } else {
      document.body.style.overflow = ''
      document.body.style.touchAction = ''
    }
  }
})

// Auto-close mobile drawer when selecting a conversation on mobile
watch(
  () => conversationsStore.activeId,
  (id) => {
    if (isMobile.value && id) {
      uiStore.closeMobileDrawer()
      uiStore.showThread()
    }
  },
)

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    if (mobileDrawerOpen.value) {
      uiStore.closeMobileDrawer()
    }
  }
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('keydown', handleKeydown)
  }
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('keydown', handleKeydown)
    document.body.style.overflow = ''
    document.body.style.touchAction = ''
  }
})
</script>

<template>
  <div class="chatify:relative chatify:flex chatify:h-full chatify:w-full chatify:overflow-hidden chatify:bg-chatify-sidebar">
    <!-- Desktop / Tablet Main Sidebar -->
    <aside
      v-if="!isMobile"
      class="chatify-sidebar-container chatify:relative chatify:flex chatify:h-full chatify:shrink-0 chatify:flex-col chatify:border-e chatify:bg-chatify-sidebar chatify-sidebar-divide"
      :style="{ width: isTablet ? '320px' : `${sidebarWidth}px` }"
    >
      <Sidebar />

      <!-- Desktop Resizer Handle (Active on desktop only) -->
      <div
        v-if="!isTablet"
        class="chatify-sidebar-resizer"
        :class="{ 'is-resizing': isResizing }"
        role="separator"
        aria-orientation="vertical"
        aria-label="Resize sidebar"
        title="Drag to resize, double-click to reset"
        @mousedown="startResize"
        @dblclick="resetWidth"
      >
        <div class="chatify-sidebar-resizer-line" />
        <div class="chatify-sidebar-resizer-grip" aria-hidden="true">
          <span />
          <span />
          <span />
        </div>
      </div>
    </aside>

    <!-- Mobile Full-Screen Sidebar (When browsing inbox without active thread) -->
    <aside
      v-else-if="!showThreadOnMobile"
      class="chatify-sidebar-container chatify:relative chatify:flex chatify:h-full chatify:w-full chatify:flex-col chatify:bg-chatify-sidebar"
    >
      <Sidebar />
    </aside>

    <!-- Main Conversation Thread Panel -->
    <main
      dir="ltr"
      class="chatify-thread-panel chatify:relative chatify:flex chatify:min-w-0 chatify:flex-1 chatify:flex-col chatify:bg-chatify-panel"
      :class="[
        isMobile ? 'chatify:w-full' : '',
        isMobile && !showThreadOnMobile ? 'chatify:hidden' : 'chatify:flex',
      ]"
    >
      <ThreadPanel />
    </main>

    <!-- Mobile Slide-Over Navigation Drawer (When inside conversation on mobile) -->
    <Teleport to="body">
      <div v-if="isMobile && showThreadOnMobile">
        <!-- Backdrop Overlay -->
        <Transition name="chatify-drawer-backdrop">
          <div
            v-if="mobileDrawerOpen"
            class="chatify-drawer-backdrop chatify:fixed chatify:inset-0 chatify:z-50 chatify:bg-black/75 chatify:backdrop-blur-sm chatify:transition-opacity"
            aria-hidden="true"
            @click="uiStore.closeMobileDrawer()"
          />
        </Transition>

        <!-- Slide-over Drawer Content -->
        <Transition name="chatify-drawer-slide">
          <div
            v-if="mobileDrawerOpen"
            class="chatify-drawer-panel chatify:fixed chatify:inset-y-0 chatify:start-0 chatify:z-50 chatify:flex chatify:w-[86vw] chatify:max-w-[360px] chatify:flex-col chatify:bg-chatify-sidebar chatify:shadow-2xl chatify:border-e chatify:border-white/10 chatify:overflow-hidden"
            role="dialog"
            aria-modal="true"
            aria-label="Navigation drawer"
          >
            <!-- Drawer Header Bar with Close -->
            <div class="chatify:flex chatify:items-center chatify:justify-between chatify:border-b chatify:border-white/10 chatify:bg-black/40 chatify:px-4 chatify:py-2.5">
              <div class="chatify:flex chatify:items-center chatify:gap-2">
                <img src="/icons/icon-48x48.png" alt="Logo" class="chatify:h-5 chatify:w-5 chatify:rounded-md" />
                <span class="chatify:text-xs chatify:font-bold chatify:tracking-wider chatify:text-white uppercase">V3RCHAT</span>
              </div>
              <button
                type="button"
                class="chatify:flex chatify:h-7 chatify:w-7 chatify:items-center chatify:justify-center chatify:rounded-full chatify:text-zinc-400 chatify:transition hover:chatify:bg-white/10 hover:chatify:text-white"
                aria-label="Close drawer"
                @click="uiStore.closeMobileDrawer()"
              >
                <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Drawer Body -->
            <div class="chatify:min-h-0 chatify:flex-1 chatify:overflow-y-auto">
              <Sidebar />
            </div>
          </div>
        </Transition>
      </div>
    </Teleport>
  </div>
</template>
