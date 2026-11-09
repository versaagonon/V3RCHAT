<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'

const props = withDefaults(
  defineProps<{
    ariaLabel?: string
    title?: string
    size?: string
    scale?: number
  }>(),
  {
    ariaLabel: '',
    title: '',
    size: '2.5rem',
    scale: -35,
  },
)

const emit = defineEmits<{
  (e: 'click', event: MouseEvent): void
}>()

const filterId = `liquid-glass-${Math.random().toString(36).slice(2, 9)}`
const btnRef = ref<HTMLButtonElement | null>(null)
const glareX = ref('30%')
const glareY = ref('20%')
const glareOpacity = ref('0.75')
const supportsSvgFilter = ref(false)

const scaleR = computed(() => props.scale - 8)
const scaleG = computed(() => props.scale)
const scaleB = computed(() => props.scale + 8)

const mapDataUrl = computed(() => {
  const w = 40
  const h = 40
  const r = Math.min(w, h) * (0.07 * 0.5)
  const borderRadius = 20
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}" width="${w}" height="${h}">
    <defs>
      <linearGradient id="red" x1="100%" y1="0%" x2="0%" y2="0%">
        <stop offset="0%" stop-color="rgba(255,0,0,0)"/>
        <stop offset="20%" stop-color="rgba(255,0,0,1)"/>
        <stop offset="80%" stop-color="rgba(255,0,0,1)"/>
        <stop offset="100%" stop-color="rgba(255,0,0,0)"/>
      </linearGradient>
      <linearGradient id="blue" x1="0%" y1="0%" x2="0%" y2="100%">
        <stop offset="0%" stop-color="rgba(0,0,255,0)"/>
        <stop offset="20%" stop-color="rgba(0,0,255,1)"/>
        <stop offset="80%" stop-color="rgba(0,0,255,1)"/>
        <stop offset="100%" stop-color="rgba(0,0,255,0)"/>
      </linearGradient>
    </defs>
    <rect width="${w}" height="${h}" fill="rgb(128,128,128)"/>
    <rect width="${w}" height="${h}" rx="${borderRadius}" fill="url(#red)"/>
    <rect width="${w}" height="${h}" rx="${borderRadius}" fill="url(#blue)" style="mix-blend-mode:difference"/>
    <rect x="${r}" y="${r}" width="${Math.max(1, w - r * 2)}" height="${Math.max(1, h - r * 2)}" rx="${borderRadius}" fill="hsl(0 0% 50% / 0.93)" style="filter:blur(11px)"/>
  </svg>`
  return `data:image/svg+xml,${encodeURIComponent(svg)}`
})

onMounted(() => {
  if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    const testEl = document.createElement('div')
    testEl.style.backdropFilter = `url(#${filterId})`
    supportsSvgFilter.value = testEl.style.backdropFilter !== ''
  }
})

function onPointerMove(event: PointerEvent) {
  if (!btnRef.value) return
  const rect = btnRef.value.getBoundingClientRect()
  const x = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100))
  const y = Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100))
  glareX.value = `${Math.round(x)}%`
  glareY.value = `${Math.round(y)}%`
  glareOpacity.value = '1'
}

function onPointerLeave() {
  glareX.value = '30%'
  glareY.value = '20%'
  glareOpacity.value = '0.75'
}
</script>

<template>
  <div class="liquid-glass-wrapper">
    <!-- SVG Chromatic Aberration & Refraction Filter -->
    <svg
      class="liquid-glass-svg-def"
      xmlns="http://www.w3.org/2000/svg"
      aria-hidden="true"
    >
      <defs>
        <filter
          :id="filterId"
          color-interpolation-filters="sRGB"
          x="-50%"
          y="-50%"
          width="200%"
          height="200%"
        >
          <feImage
            :href="mapDataUrl"
            x="0"
            y="0"
            width="100%"
            height="100%"
            preserveAspectRatio="none"
            result="map"
          />
          <feDisplacementMap
            in="SourceGraphic"
            in2="map"
            :scale="scaleR"
            xChannelSelector="R"
            yChannelSelector="G"
            result="dispRed"
          />
          <feColorMatrix
            in="dispRed"
            type="matrix"
            values="1 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 1 0"
            result="red"
          />
          <feDisplacementMap
            in="SourceGraphic"
            in2="map"
            :scale="scaleG"
            xChannelSelector="R"
            yChannelSelector="G"
            result="dispGreen"
          />
          <feColorMatrix
            in="dispGreen"
            type="matrix"
            values="0 0 0 0 0  0 1 0 0 0  0 0 0 0 0  0 0 0 1 0"
            result="green"
          />
          <feDisplacementMap
            in="SourceGraphic"
            in2="map"
            :scale="scaleB"
            xChannelSelector="R"
            yChannelSelector="G"
            result="dispBlue"
          />
          <feColorMatrix
            in="dispBlue"
            type="matrix"
            values="0 0 0 0 0  0 0 0 0 0  0 0 1 0 0  0 0 0 1 0"
            result="blue"
          />
          <feBlend in="red" in2="green" mode="screen" result="rg" />
          <feBlend in="rg" in2="blue" mode="screen" result="output" />
          <feGaussianBlur in="output" stdDeviation="0.6" />
        </filter>
      </defs>
    </svg>

    <button
      ref="btnRef"
      type="button"
      class="liquid-glass-btn"
      :class="{ 'liquid-glass-svg-mode': supportsSvgFilter }"
      :style="{
        width: size,
        height: size,
        '--filter-id': `url(#${filterId})`,
        '--glare-x': glareX,
        '--glare-y': glareY,
        '--glare-opacity': glareOpacity,
      }"
      :aria-label="ariaLabel"
      :title="title || ariaLabel"
      @click="emit('click', $event)"
      @pointermove="onPointerMove"
      @pointerleave="onPointerLeave"
    >
      <!-- Specular interactive reflection glare -->
      <span class="liquid-glass-glare" aria-hidden="true" />
      <!-- Rim highlight -->
      <span class="liquid-glass-rim" aria-hidden="true" />
      <!-- Button icon / content -->
      <span class="liquid-glass-content">
        <slot />
      </span>
    </button>
  </div>
</template>

<style scoped>
.liquid-glass-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.liquid-glass-svg-def {
  position: absolute;
  width: 0;
  height: 0;
  pointer-events: none;
  opacity: 0;
  z-index: -1;
}

.liquid-glass-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.32);
  background: color-mix(in srgb, var(--chatify-color-chatify-primary, #ffffff) 18%, transparent);
  backdrop-filter: blur(14px) saturate(1.4);
  -webkit-backdrop-filter: blur(14px) saturate(1.4);
  color: var(--chatify-color-chatify-text, #ffffff);
  cursor: pointer;
  padding: 0;
  box-shadow:
    0 0 2px 1px rgba(255, 255, 255, 0.45) inset,
    0 0 10px 3px rgba(255, 255, 255, 0.16) inset,
    0 4px 16px rgba(0, 0, 0, 0.32),
    0 8px 24px rgba(0, 0, 0, 0.28),
    0 2px 6px rgba(255, 255, 255, 0.15) inset;
  transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.22s ease, border-color 0.22s ease, background 0.22s ease;
  user-select: none;
  -webkit-tap-highlight-color: transparent;
}

.liquid-glass-btn.liquid-glass-svg-mode {
  backdrop-filter: var(--filter-id) blur(1px) saturate(1.3);
  -webkit-backdrop-filter: var(--filter-id) blur(1px) saturate(1.3);
}

.liquid-glass-btn:hover {
  transform: translateY(-1.5px) scale(1.07);
  border-color: rgba(255, 255, 255, 0.65);
  background: color-mix(in srgb, var(--chatify-color-chatify-primary, #ffffff) 30%, transparent);
  box-shadow:
    0 0 3px 1.5px rgba(255, 255, 255, 0.7) inset,
    0 0 14px 5px rgba(255, 255, 255, 0.28) inset,
    0 6px 22px rgba(0, 0, 0, 0.45),
    0 12px 32px rgba(0, 0, 0, 0.35),
    0 2px 8px rgba(255, 255, 255, 0.3) inset;
}

.liquid-glass-btn:active {
  transform: translateY(0.5px) scale(0.94);
  box-shadow:
    0 0 2px 1px rgba(255, 255, 255, 0.35) inset,
    0 0 6px 2px rgba(255, 255, 255, 0.12) inset,
    0 2px 8px rgba(0, 0, 0, 0.28);
}

.liquid-glass-glare {
  position: absolute;
  inset: -1px;
  border-radius: inherit;
  background: radial-gradient(
    circle at var(--glare-x, 30%) var(--glare-y, 20%),
    rgba(255, 255, 255, 0.6) 0%,
    rgba(255, 255, 255, 0.12) 35%,
    transparent 70%
  );
  pointer-events: none;
  opacity: var(--glare-opacity, 0.75);
  transition: opacity 0.25s ease;
}

.liquid-glass-rim {
  position: absolute;
  inset: 0;
  border-radius: inherit;
  pointer-events: none;
  box-shadow:
    inset 0 1.5px 1.5px 0 rgba(255, 255, 255, 0.75),
    inset 0 -1.5px 1.5px 0 rgba(0, 0, 0, 0.45);
}

.liquid-glass-content {
  position: relative;
  z-index: 2;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  pointer-events: none;
  color: var(--chatify-color-chatify-text, #ffffff);
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.4));
  transition: transform 0.2s ease;
}

.liquid-glass-btn:hover .liquid-glass-content {
  transform: scale(1.04);
}
</style>
