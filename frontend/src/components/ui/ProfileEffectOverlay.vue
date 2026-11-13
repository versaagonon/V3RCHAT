<script setup lang="ts">
/**
 * ProfileEffectOverlay — paired 450x880 APNG animation rendered on top of the
 * contact-info and profile-info modals.
 *
 * pair-1 is the entrance: it plays exactly once (its APNG may declare an
 * infinite loop, so playback is bounded by the duration parsed from its fcTL
 * chunks instead of relying on the file's own num_plays). When that timer
 * fires, the layer swaps to pair-2.
 *
 * pair-2 no longer loops back-to-back. It plays one full cycle, freezes on its
 * last frame for PAIR2_IDLE_MS, then replays from frame 0. Each replay uses a
 * freshly minted object URL so the browser restarts the APNG instead of
 * resuming the already-running one. Unmounting (modal close) tears everything
 * down so the next open starts from pair-1 again.
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import type { ProfileEffect } from '../../types'
import { fetchProfileEffectBlob } from '../../utils/profileEffectBlobs'

/** How long pair-2 rests on its final frame between plays. */
const PAIR2_IDLE_MS = 3000

const props = defineProps<{
  effect: ProfileEffect | null
}>()

const phase = ref<'pair1' | 'pair2' | 'idle'>('pair1')
const pair1Src = ref<string | null>(null)
const pair2Src = ref<string | null>(null)
/** Still frame shown while pair-2 rests between plays. */
const pair2StillSrc = ref<string | null>(null)
const pair2Img = ref<HTMLImageElement | null>(null)

let swapTimer: ReturnType<typeof setTimeout> | null = null
let pair2Timer: ReturnType<typeof setTimeout> | null = null
let idleTimer: ReturnType<typeof setTimeout> | null = null
let loadToken = 0
let pair1ObjectUrl: string | null = null
let pair2ObjectUrl: string | null = null

const isVisible = computed(() => Boolean(props.effect && pair1Src.value))

function clearAllTimers() {
  if (swapTimer !== null) {
    clearTimeout(swapTimer)
    swapTimer = null
  }
  if (pair2Timer !== null) {
    clearTimeout(pair2Timer)
    pair2Timer = null
  }
  if (idleTimer !== null) {
    clearTimeout(idleTimer)
    idleTimer = null
  }
}

function revokeObjectUrl(url: string | null) {
  if (url !== null && url.startsWith('blob:')) {
    URL.revokeObjectURL(url)
  }
}

function resetPlayback() {
  clearAllTimers()
  revokeObjectUrl(pair1ObjectUrl)
  revokeObjectUrl(pair2ObjectUrl)
  pair1ObjectUrl = null
  pair2ObjectUrl = null
  phase.value = 'pair1'
  pair1Src.value = null
  pair2Src.value = null
  pair2StillSrc.value = null
}

async function preparePair1() {
  const effect = props.effect
  const token = ++loadToken

  resetPlayback()

  if (!effect) return

  // Warm the pair-2 Blob while the entrance plays, so each replay after the
  // idle gap can start instantly without touching the network.
  if (effect.pair2_url) {
    void fetchProfileEffectBlob(effect.pair2_url).catch(() => {})
  }

  try {
    const blob = await fetchProfileEffectBlob(effect.pair1_url)
    if (token !== loadToken || !props.effect) return

    pair1ObjectUrl = URL.createObjectURL(blob)
    pair1Src.value = pair1ObjectUrl
  } catch {
    if (token !== loadToken || !props.effect) return
    // Fallback to the direct URL; playback may join the shared loop phase but
    // the effect still renders.
    pair1Src.value = effect.pair1_url
  }
}

/**
 * Start the pair-1 → pair-2 countdown from the image load event, which now
 * coincides with the first frame of the freshly minted object URL. Swapping
 * just under the full duration hides the loop boundary instead of flashing the
 * first frame of a second pair-1 cycle.
 */
function schedulePair2() {
  if (phase.value !== 'pair1') return

  if (swapTimer !== null) clearTimeout(swapTimer)
  const duration = Number(props.effect?.pair1_duration_ms) || 5000

  swapTimer = setTimeout(() => {
    swapTimer = null
    void startPair2Cycle()
  }, Math.max(300, duration - 40))
}

/**
 * Play one pair-2 cycle. A new object URL is minted every time so the APNG
 * always starts from its first frame, even though the Blob itself is cached.
 */
async function startPair2Cycle() {
  const effect = props.effect
  if (!effect) return

  const token = loadToken
  revokeObjectUrl(pair2ObjectUrl)
  pair2ObjectUrl = null

  let src = effect.pair2_url
  try {
    const blob = await fetchProfileEffectBlob(effect.pair2_url)
    if (token !== loadToken || !props.effect) return

    pair2ObjectUrl = URL.createObjectURL(blob)
    src = pair2ObjectUrl
  } catch {
    if (token !== loadToken || !props.effect) return
    // Keep the direct URL fallback; it may not restart cleanly, but it renders.
  }

  pair2StillSrc.value = null
  pair2Src.value = src
  phase.value = 'pair2'
}

/** Bound one pair-2 cycle, then rest on its final frame. */
function schedulePair2Idle() {
  if (phase.value !== 'pair2') return

  if (pair2Timer !== null) clearTimeout(pair2Timer)
  const duration = Number(props.effect?.pair2_duration_ms) || 5000

  pair2Timer = setTimeout(() => {
    pair2Timer = null
    enterPair2Idle()
  }, Math.max(300, duration - 40))
}

/**
 * Freeze the currently painted frame into a still image, then wait
 * PAIR2_IDLE_MS before the next playback. The still keeps the effect visible
 * during the gap instead of making it blink out. If the snapshot is
 * unavailable (canvas blocked), the overlay simply stays empty for the gap.
 */
function enterPair2Idle() {
  if (phase.value !== 'pair2') return

  pair2StillSrc.value = captureCurrentFrame()
  phase.value = 'idle'

  revokeObjectUrl(pair2ObjectUrl)
  pair2ObjectUrl = null
  pair2Src.value = null

  idleTimer = setTimeout(() => {
    idleTimer = null
    void startPair2Cycle()
  }, PAIR2_IDLE_MS)
}

function captureCurrentFrame(): string | null {
  const img = pair2Img.value
  if (!img || !img.naturalWidth || !img.naturalHeight) return null

  try {
    const canvas = document.createElement('canvas')
    canvas.width = img.naturalWidth
    canvas.height = img.naturalHeight

    const context = canvas.getContext('2d')
    if (!context) return null

    context.drawImage(img, 0, 0)
    return canvas.toDataURL('image/png')
  } catch {
    return null
  }
}

watch(
  () => props.effect?.id,
  () => {
    void preparePair1()
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  loadToken++
  resetPlayback()
})
</script>

<template>
  <div
    v-if="isVisible"
    class="chatify-profile-effect"
    aria-hidden="true"
    data-chatify-profile-effect
  >
    <img
      v-if="phase === 'pair1'"
      :key="`${effect?.id}-pair-1-${pair1Src}`"
      :src="pair1Src ?? undefined"
      alt=""
      class="chatify-profile-effect-img"
      draggable="false"
      @load="schedulePair2"
      @error="schedulePair2"
    />
    <img
      v-else-if="phase === 'pair2' && pair2Src"
      ref="pair2Img"
      :key="`${effect?.id}-pair-2-${pair2Src}`"
      :src="pair2Src"
      alt=""
      class="chatify-profile-effect-img"
      draggable="false"
      @load="schedulePair2Idle"
      @error="schedulePair2Idle"
    />
    <img
      v-else-if="pair2StillSrc"
      :key="`${effect?.id}-pair-2-still-${pair2StillSrc}`"
      :src="pair2StillSrc"
      alt=""
      class="chatify-profile-effect-img"
      draggable="false"
    />
  </div>
</template>
