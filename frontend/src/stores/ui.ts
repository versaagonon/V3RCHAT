import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { ModalName } from '../types'

export const useUiStore = defineStore('ui', () => {
  const activeModal = ref<ModalName>(null)
  const modalContext = ref<Record<string, unknown>>({})
  const showThreadOnMobile = ref(false)
  const mobileDrawerOpen = ref(false)
  const desktopSidebarCollapsed = ref(false)
  const composerDraft = ref('')
  const messageSearchOpen = ref(false)
  // Screen the settings sidebar should open (e.g. 'profile' from a gift notification).
  // Consumed once by SettingsSidebarView so normal navigation still starts at root.
  const requestedSettingsScreen = ref<string | null>(null)

  function requestSettingsScreen(screen: string | null) {
    requestedSettingsScreen.value = screen
  }

  function consumeRequestedSettingsScreen(): string | null {
    const screen = requestedSettingsScreen.value
    requestedSettingsScreen.value = null
    return screen
  }

  function openModal(name: Exclude<ModalName, null>, context: Record<string, unknown> = {}) {
    activeModal.value = name
    modalContext.value = context
  }

  function closeModal() {
    activeModal.value = null
    modalContext.value = {}
  }

  function openMessageSearch() {
    messageSearchOpen.value = true
  }

  function closeMessageSearch() {
    messageSearchOpen.value = false
  }

  function showThread() {
    showThreadOnMobile.value = true
    mobileDrawerOpen.value = false
  }

  function hideThread() {
    showThreadOnMobile.value = false
    mobileDrawerOpen.value = false
  }

  function openMobileDrawer() {
    mobileDrawerOpen.value = true
  }

  function closeMobileDrawer() {
    mobileDrawerOpen.value = false
  }

  function toggleMobileDrawer(force?: boolean) {
    mobileDrawerOpen.value = typeof force === 'boolean' ? force : !mobileDrawerOpen.value
  }

  function toggleDesktopSidebar(force?: boolean) {
    desktopSidebarCollapsed.value = typeof force === 'boolean' ? force : !desktopSidebarCollapsed.value
  }

  function setComposerDraft(value: string) {
    composerDraft.value = value
  }

  return {
    activeModal,
    modalContext,
    showThreadOnMobile,
    mobileDrawerOpen,
    desktopSidebarCollapsed,
    composerDraft,
    messageSearchOpen,
    requestedSettingsScreen,
    requestSettingsScreen,
    consumeRequestedSettingsScreen,
    openModal,
    closeModal,
    openMessageSearch,
    closeMessageSearch,
    showThread,
    hideThread,
    openMobileDrawer,
    closeMobileDrawer,
    toggleMobileDrawer,
    toggleDesktopSidebar,
    setComposerDraft,
  }
})
