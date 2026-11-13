import { onMounted, onUnmounted, ref, computed } from 'vue'

const MOBILE_BREAKPOINT = 768
const TABLET_BREAKPOINT = 1024
const ULTRAWIDE_BREAKPOINT = 1920

export function useBreakpoints() {
  const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024)
  const windowHeight = ref(typeof window !== 'undefined' ? window.innerHeight : 768)

  const isMobile = computed(() => windowWidth.value < MOBILE_BREAKPOINT)
  const isTablet = computed(() => windowWidth.value >= MOBILE_BREAKPOINT && windowWidth.value < TABLET_BREAKPOINT)
  const isDesktop = computed(() => windowWidth.value >= TABLET_BREAKPOINT)
  const isUltrawide = computed(() => windowWidth.value >= ULTRAWIDE_BREAKPOINT)

  function update() {
    if (typeof window === 'undefined') return
    windowWidth.value = window.innerWidth
    windowHeight.value = window.innerHeight
  }

  onMounted(() => {
    update()
    window.addEventListener('resize', update, { passive: true })
    window.addEventListener('orientationchange', update, { passive: true })
  })

  onUnmounted(() => {
    if (typeof window === 'undefined') return
    window.removeEventListener('resize', update)
    window.removeEventListener('orientationchange', update)
  })

  return {
    windowWidth,
    windowHeight,
    isMobile,
    isTablet,
    isDesktop,
    isUltrawide,
  }
}
