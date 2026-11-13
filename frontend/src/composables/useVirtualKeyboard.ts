import { onMounted, onUnmounted, ref } from 'vue'

export function useVirtualKeyboard() {
  const keyboardHeight = ref(0)
  const isKeyboardOpen = ref(false)
  const viewportHeight = ref(typeof window !== 'undefined' ? window.innerHeight : 0)

  function update() {
    if (typeof window === 'undefined') return

    if (window.visualViewport) {
      const vh = window.visualViewport.height
      const ih = window.innerHeight
      const diff = Math.max(0, ih - vh)

      // Virtual keyboards typically take > 150px
      if (diff > 140) {
        keyboardHeight.value = diff
        isKeyboardOpen.value = true
      } else {
        keyboardHeight.value = 0
        isKeyboardOpen.value = false
      }

      viewportHeight.value = vh
      document.documentElement.style.setProperty('--app-viewport-height', `${vh}px`)
      document.documentElement.style.setProperty('--app-keyboard-height', `${keyboardHeight.value}px`)
    } else {
      viewportHeight.value = window.innerHeight
      document.documentElement.style.setProperty('--app-viewport-height', `${window.innerHeight}px`)
      document.documentElement.style.setProperty('--app-keyboard-height', '0px')
    }
  }

  function onFocusIn(e: FocusEvent) {
    const target = e.target as HTMLElement | null
    if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA')) {
      // Small timeout to allow iOS visualViewport to stabilize
      setTimeout(update, 250)
    }
  }

  function onFocusOut() {
    setTimeout(update, 200)
  }

  onMounted(() => {
    update()
    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', update)
      window.visualViewport.addEventListener('scroll', update)
    }
    window.addEventListener('resize', update)
    window.addEventListener('focusin', onFocusIn)
    window.addEventListener('focusout', onFocusOut)
  })

  onUnmounted(() => {
    if (window.visualViewport) {
      window.visualViewport.removeEventListener('resize', update)
      window.visualViewport.removeEventListener('scroll', update)
    }
    window.removeEventListener('resize', update)
    window.removeEventListener('focusin', onFocusIn)
    window.removeEventListener('focusout', onFocusOut)
  })

  return {
    keyboardHeight,
    isKeyboardOpen,
    viewportHeight,
  }
}

