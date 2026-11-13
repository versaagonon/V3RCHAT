import { getCurrentInstance, onBeforeUnmount, ref } from 'vue'

export const SIDEBAR_MIN_WIDTH = 280
export const SIDEBAR_DEFAULT_WIDTH = 380
export const SIDEBAR_MAX_WIDTH = 640
export const SIDEBAR_STORAGE_KEY = 'chatify_sidebar_width'

function loadSavedSidebarWidth(): number {
  if (typeof window === 'undefined') {
    return SIDEBAR_DEFAULT_WIDTH
  }
  try {
    const raw = localStorage.getItem(SIDEBAR_STORAGE_KEY)
    if (!raw) return SIDEBAR_DEFAULT_WIDTH
    const val = Number.parseInt(raw, 10)
    if (Number.isNaN(val)) return SIDEBAR_DEFAULT_WIDTH
    return Math.max(SIDEBAR_MIN_WIDTH, Math.min(SIDEBAR_MAX_WIDTH, val))
  } catch {
    return SIDEBAR_DEFAULT_WIDTH
  }
}

export function useSidebarResize() {
  const sidebarWidth = ref<number>(loadSavedSidebarWidth())
  const isResizing = ref(false)

  let startX = 0
  let startWidth = 0

  function onPointerMove(e: MouseEvent) {
    if (!isResizing.value) return
    const delta = e.clientX - startX
    const maxAllowed = Math.min(
      typeof window !== 'undefined' ? Math.max(SIDEBAR_MIN_WIDTH, window.innerWidth - 360) : SIDEBAR_MAX_WIDTH,
      SIDEBAR_MAX_WIDTH,
    )
    const newWidth = Math.max(SIDEBAR_MIN_WIDTH, Math.min(maxAllowed, startWidth + delta))
    sidebarWidth.value = Math.round(newWidth)
  }

  function stopResize() {
    if (!isResizing.value) return
    isResizing.value = false

    if (typeof document !== 'undefined') {
      document.body.classList.remove('chatify-resizing-active')
    }

    if (typeof window !== 'undefined') {
      window.removeEventListener('mousemove', onPointerMove)
      window.removeEventListener('mouseup', stopResize)
      try {
        localStorage.setItem(SIDEBAR_STORAGE_KEY, String(sidebarWidth.value))
      } catch {
        // ignore storage errors
      }
    }
  }

  function startResize(e: MouseEvent) {
    if (e.button !== 0) return
    isResizing.value = true
    startX = e.clientX
    startWidth = sidebarWidth.value

    if (typeof document !== 'undefined') {
      document.body.classList.add('chatify-resizing-active')
    }

    if (typeof window !== 'undefined') {
      window.addEventListener('mousemove', onPointerMove)
      window.addEventListener('mouseup', stopResize)
    }
  }

  function resetWidth() {
    sidebarWidth.value = SIDEBAR_DEFAULT_WIDTH
    if (typeof window !== 'undefined') {
      try {
        localStorage.setItem(SIDEBAR_STORAGE_KEY, String(SIDEBAR_DEFAULT_WIDTH))
      } catch {
        // ignore storage errors
      }
    }
  }

  if (getCurrentInstance()) {
    onBeforeUnmount(() => {
      stopResize()
    })
  }

  return {
    sidebarWidth,
    isResizing,
    startResize,
    resetWidth,
    minWidth: SIDEBAR_MIN_WIDTH,
    maxWidth: SIDEBAR_MAX_WIDTH,
    defaultWidth: SIDEBAR_DEFAULT_WIDTH,
  }
}
