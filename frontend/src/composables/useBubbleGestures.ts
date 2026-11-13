import { onBeforeUnmount, ref } from 'vue'

/**
 * Long-press + horizontal swipe gestures for a chat bubble.
 *
 * Pointer Events are used (not touch-only) so the same code path works for mouse
 * users on desktop: hold with the left button behaves like a mobile long-press.
 *
 * - Long press  : hold past `holdDelay` without moving beyond `moveTolerance`
 *                 → fires onLongPress with the pointer position.
 * - Swipe       : horizontal travel past `swipeThreshold`
 *                 → fires onSwipe('left' | 'right') once and locks until release.
 *
 * A swipe cancels the long-press timer, and vice versa, so the two gestures never
 * fire together.
 */
export interface BubbleGestureOptions {
  /** ms to hold before a long press fires. */
  holdDelay?: number
  /** px of travel that still counts as "not moved". */
  moveTolerance?: number
  /** px of horizontal travel needed to trigger a swipe. */
  swipeThreshold?: number
  /** Hard cap on the visual drag offset in px. */
  maxDrag?: number
  /** Returns true when gestures should be ignored (e.g. selection active). */
  disabled?: () => boolean
  onLongPress: (point: { x: number; y: number }) => void
  onSwipe: (direction: 'left' | 'right') => void
}

export function useBubbleGestures(options: BubbleGestureOptions) {
  const holdDelay = options.holdDelay ?? 420
  const moveTolerance = options.moveTolerance ?? 10
  const swipeThreshold = options.swipeThreshold ?? 64
  const maxDrag = options.maxDrag ?? 96

  /** Set while a press is being tracked (used for the hold animation). */
  const isHolding = ref(false)
  /** True once the hold actually elapsed and the menu opened. */
  const holdTriggered = ref(false)
  /** Horizontal drag distance in px; sign follows the drag direction. */
  const dragX = ref(0)
  /** Direction of a locked swipe, so the bubble can show an arrow. */
  const swipeDirection = ref<'left' | 'right' | null>(null)

  let startX = 0
  let startY = 0
  let pointerId: number | null = null
  let holdTimer: ReturnType<typeof setTimeout> | null = null
  let moved = false
  let locked = false

  function clearHoldTimer() {
    if (holdTimer !== null) {
      clearTimeout(holdTimer)
      holdTimer = null
    }
  }

  function reset() {
    clearHoldTimer()
    isHolding.value = false
    holdTriggered.value = false
    dragX.value = 0
    swipeDirection.value = null
    pointerId = null
    moved = false
    locked = false
  }

  function onPointerDown(event: PointerEvent) {
    if (options.disabled?.()) return
    // Only the primary button drags; right-click still opens the native menu path.
    if (event.button !== 0 && event.pointerType === 'mouse') return
    if (pointerId !== null) return

    pointerId = event.pointerId
    startX = event.clientX
    startY = event.clientY
    moved = false
    locked = false
    dragX.value = 0
    swipeDirection.value = null
    holdTriggered.value = false
    isHolding.value = true

    clearHoldTimer()
    holdTimer = setTimeout(() => {
      holdTimer = null
      if (moved || locked) return
      holdTriggered.value = true
      isHolding.value = false
      options.onLongPress({ x: startX, y: startY })
    }, holdDelay)
  }

  function onPointerMove(event: PointerEvent) {
    if (pointerId === null || event.pointerId !== pointerId) return

    const dx = event.clientX - startX
    const dy = event.clientY - startY

    if (!moved && Math.hypot(dx, dy) > moveTolerance) {
      moved = true
      isHolding.value = false
      clearHoldTimer()
    }

    // Vertical intent belongs to the scroll container, not to us.
    if (Math.abs(dy) > Math.abs(dx)) return

    if (locked) {
      dragX.value = clamp(dx)
      return
    }

    if (Math.abs(dx) >= swipeThreshold) {
      locked = true
      swipeDirection.value = dx > 0 ? 'right' : 'left'
      options.onSwipe(swipeDirection.value)
    }

    dragX.value = clamp(dx)
  }

  function clamp(dx: number): number {
    const capped = Math.max(-maxDrag, Math.min(maxDrag, dx))
    return capped
  }

  function onPointerUp(event: PointerEvent) {
    if (pointerId === null || event.pointerId !== pointerId) return
    // Keep the arrow visible for a beat so the action reads as intentional,
    // then snap back.
    const wasLocked = locked
    const direction = swipeDirection.value
    reset()

    if (wasLocked) {
      swipeDirection.value = direction
      window.setTimeout(() => {
        swipeDirection.value = null
      }, 220)
    }
  }

  function onPointerCancel() {
    reset()
  }

  onBeforeUnmount(reset)

  return {
    isHolding,
    holdTriggered,
    dragX,
    swipeDirection,
    onPointerDown,
    onPointerMove,
    onPointerUp,
    onPointerCancel,
  }
}
