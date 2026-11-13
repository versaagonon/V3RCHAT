<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import {
  type HsvColor,
  hexToHsv,
  hsvToHex,
  hsvToRgb,
  rgbToHex,
} from '../../utils/colorPicker'

const props = defineProps<{
  modelValue: string
}>()

const emit = defineEmits<{
  'update:modelValue': [color: string]
  'change': [color: string]
  'close': []
}>()

const hsv = ref<HsvColor>(hexToHsv(props.modelValue || '#3B82F6'))
const hexInput = ref(props.modelValue ? props.modelValue.toUpperCase() : '#3B82F6')
const mode = ref<'hex' | 'rgb'>('hex')

const satAreaRef = ref<HTMLElement | null>(null)
const hueBarRef = ref<HTMLElement | null>(null)

let isDraggingSat = false
let isDraggingHue = false

const isEyeDropperSupported = typeof window !== 'undefined' && 'EyeDropper' in window

// Update local HSV when external prop changes
watch(
  () => props.modelValue,
  (val) => {
    if (!val) return
    const currentHex = hsvToHex(hsv.value.h, hsv.value.s, hsv.value.v)
    if (val.toLowerCase() !== currentHex.toLowerCase()) {
      hsv.value = hexToHsv(val)
      hexInput.value = val.toUpperCase()
    }
  },
)

const currentHex = computed(() => hsvToHex(hsv.value.h, hsv.value.s, hsv.value.v))
const currentRgb = computed(() => hsvToRgb(hsv.value.h, hsv.value.s, hsv.value.v))

function emitColor() {
  const hex = currentHex.value
  hexInput.value = hex
  emit('update:modelValue', hex)
  emit('change', hex)
}

// 1. Saturation & Brightness Handling
function updateSatValFromEvent(e: MouseEvent | TouchEvent) {
  if (!satAreaRef.value) return
  const rect = satAreaRef.value.getBoundingClientRect()
  const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX
  const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY

  const x = Math.max(0, Math.min(rect.width, clientX - rect.left))
  const y = Math.max(0, Math.min(rect.height, clientY - rect.top))

  const s = Math.round((x / rect.width) * 100)
  const v = Math.round((1 - y / rect.height) * 100)

  hsv.value.s = s
  hsv.value.v = v
  emitColor()
}

function onSatMouseDown(e: MouseEvent) {
  if (e.button !== 0) return
  isDraggingSat = true
  updateSatValFromEvent(e)
  window.addEventListener('mousemove', onSatMouseMove)
  window.addEventListener('mouseup', onSatMouseUp)
}

function onSatMouseMove(e: MouseEvent) {
  if (!isDraggingSat) return
  updateSatValFromEvent(e)
}

function onSatMouseUp() {
  isDraggingSat = false
  window.removeEventListener('mousemove', onSatMouseMove)
  window.removeEventListener('mouseup', onSatMouseUp)
}

// 2. Hue Slider Handling
function updateHueFromEvent(e: MouseEvent | TouchEvent) {
  if (!hueBarRef.value) return
  const rect = hueBarRef.value.getBoundingClientRect()
  const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX

  const x = Math.max(0, Math.min(rect.width, clientX - rect.left))
  const hue = Math.round((x / rect.width) * 360)
  hsv.value.h = Math.min(360, Math.max(0, hue))
  emitColor()
}

function onHueMouseDown(e: MouseEvent) {
  if (e.button !== 0) return
  isDraggingHue = true
  updateHueFromEvent(e)
  window.addEventListener('mousemove', onHueMouseMove)
  window.addEventListener('mouseup', onHueMouseUp)
}

function onHueMouseMove(e: MouseEvent) {
  if (!isDraggingHue) return
  updateHueFromEvent(e)
}

function onHueMouseUp() {
  isDraggingHue = false
  window.removeEventListener('mousemove', onHueMouseMove)
  window.removeEventListener('mouseup', onHueMouseUp)
}

// 3. Eyedropper API
async function triggerEyeDropper() {
  if (!isEyeDropperSupported) return
  try {
    const eyeDropper = new (window as any).EyeDropper()
    const result = await eyeDropper.open()
    if (result?.sRGBHex) {
      hsv.value = hexToHsv(result.sRGBHex)
      emitColor()
    }
  } catch {
    // EyeDropper cancelled by user
  }
}

// 4. Hex Input Typing
function onHexChange(e: Event) {
  const target = e.target as HTMLInputElement
  let val = target.value.trim()
  if (!val.startsWith('#')) {
    val = `#${val}`
  }
  if (/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(val)) {
    hsv.value = hexToHsv(val)
    emitColor()
  } else {
    hexInput.value = currentHex.value
  }
}

// 5. RGB Input Typing
function onRgbChange(channel: 'r' | 'g' | 'b', e: Event) {
  const target = e.target as HTMLInputElement
  const num = Math.max(0, Math.min(255, Number(target.value) || 0))
  const rgb = { ...currentRgb.value, [channel]: num }
  const hex = rgbToHex(rgb.r, rgb.g, rgb.b)
  hsv.value = hexToHsv(hex)
  emitColor()
}

// 6. Quick Presets
const quickPresets = [
  '#A855F7', // Purple
  '#3B82F6', // Blue
  '#06B6D4', // Cyan
  '#10B981', // Emerald
  '#EAB308', // Yellow
  '#F97316', // Orange
  '#EF4444', // Red
  '#EC4899', // Pink
]

function selectPreset(hex: string) {
  hsv.value = hexToHsv(hex)
  emitColor()
}

onBeforeUnmount(() => {
  window.removeEventListener('mousemove', onSatMouseMove)
  window.removeEventListener('mouseup', onSatMouseUp)
  window.removeEventListener('mousemove', onHueMouseMove)
  window.removeEventListener('mouseup', onHueMouseUp)
})
</script>

<template>
  <div class="chatify-custom-color-picker chatify:w-full chatify:max-w-xs chatify:rounded-2xl chatify:border chatify:border-white/10 chatify:bg-[#121215] chatify:p-3 chatify:shadow-2xl chatify:backdrop-blur-xl">
    <!-- Header -->
    <div class="chatify:mb-2.5 chatify:flex chatify:items-center chatify:justify-between">
      <div class="chatify:flex chatify:items-center chatify:gap-2">
        <span
          class="chatify:h-3.5 chatify:w-3.5 chatify:rounded-full chatify:border chatify:border-white/20 chatify:shadow-sm"
          :style="{ backgroundColor: currentHex }"
        />
        <span class="chatify:text-xs chatify:font-semibold chatify:text-white">Custom Color</span>
      </div>

      <button
        type="button"
        class="chatify:flex chatify:h-6 chatify:w-6 chatify:items-center chatify:justify-center chatify:rounded-lg chatify:text-zinc-400 chatify:transition hover:chatify:bg-white/10 hover:chatify:text-white"
        aria-label="Close color picker"
        @click="emit('close')"
      >
        <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- 2D Saturation / Value Canvas -->
    <div
      ref="satAreaRef"
      class="chatify:relative chatify:h-36 chatify:w-full chatify:cursor-crosshair chatify:overflow-hidden chatify:rounded-xl chatify:border chatify:border-white/10"
      :style="{ backgroundColor: `hsl(${hsv.h}, 100%, 50%)` }"
      @mousedown="onSatMouseDown"
    >
      <!-- Gradients: White horizontal & Black vertical -->
      <div class="chatify:absolute chatify:inset-0 chatify:bg-gradient-to-r chatify:from-white chatify:to-transparent" />
      <div class="chatify:absolute chatify:inset-0 chatify:bg-gradient-to-t chatify:from-black chatify:to-transparent" />

      <!-- Drag Handle -->
      <div
        class="chatify:pointer-events-none chatify:absolute chatify:h-4 chatify:w-4 chatify:-translate-x-1/2 chatify:-translate-y-1/2 chatify:rounded-full chatify:border-2 chatify:border-white chatify:shadow-[0_0_4px_rgba(0,0,0,0.8)]"
        :style="{
          left: `${hsv.s}%`,
          top: `${100 - hsv.v}%`,
          backgroundColor: currentHex,
        }"
      />
    </div>

    <!-- Hue Slider & Dropper Row -->
    <div class="chatify:mt-3 chatify:flex chatify:items-center chatify:gap-2.5">
      <!-- Eyedropper Button -->
      <button
        v-if="isEyeDropperSupported"
        type="button"
        class="chatify:flex chatify:h-7 chatify:w-7 chatify:shrink-0 chatify:items-center chatify:justify-center chatify:rounded-lg chatify:border chatify:border-white/10 chatify:bg-white/[0.04] chatify:text-zinc-300 chatify:transition hover:chatify:bg-white/10 hover:chatify:text-white"
        title="Pick color from screen"
        aria-label="Pick color from screen"
        @click="triggerEyeDropper"
      >
        <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4 4.001 4.001 0 013.882-3.996l7.854-7.854a2.828 2.828 0 114 4l-7.854 7.854A4.001 4.001 0 017 21z" />
        </svg>
      </button>

      <!-- Rainbow Hue Track -->
      <div
        ref="hueBarRef"
        class="chatify:relative chatify:h-4 chatify:flex-1 chatify:cursor-pointer chatify:rounded-full chatify:border chatify:border-white/10"
        style="background: linear-gradient(to right, #ff0000 0%, #ffff00 17%, #00ff00 33%, #00ffff 50%, #0000ff 67%, #ff00ff 83%, #ff0000 100%);"
        @mousedown="onHueMouseDown"
      >
        <div
          class="chatify:pointer-events-none chatify:absolute chatify:top-1/2 chatify:h-5 chatify:w-5 chatify:-translate-x-1/2 chatify:-translate-y-1/2 chatify:rounded-full chatify:border-2 chatify:border-white chatify:shadow-md"
          :style="{
            left: `${(hsv.h / 360) * 100}%`,
            backgroundColor: `hsl(${hsv.h}, 100%, 50%)`,
          }"
        />
      </div>

      <!-- Live Color Preview Pill -->
      <div
        class="chatify:h-6 chatify:w-6 chatify:shrink-0 chatify:rounded-full chatify:border chatify:border-white/20 chatify:shadow-inner"
        :style="{ backgroundColor: currentHex }"
      />
    </div>

    <!-- Inputs Row (HEX / RGB) -->
    <div class="chatify:mt-3 chatify:flex chatify:items-center chatify:gap-2">
      <!-- Toggle HEX / RGB Mode -->
      <button
        type="button"
        class="chatify:rounded-lg chatify:border chatify:border-white/10 chatify:bg-white/[0.04] chatify:px-2 chatify:py-1.5 chatify:text-[10px] chatify:font-bold chatify:uppercase chatify:tracking-wider chatify:text-zinc-300 chatify:transition hover:chatify:bg-white/10"
        @click="mode = mode === 'hex' ? 'rgb' : 'hex'"
      >
        {{ mode }}
      </button>

      <!-- HEX Mode Input -->
      <div v-if="mode === 'hex'" class="chatify:relative chatify:flex-1">
        <input
          v-model="hexInput"
          type="text"
          maxlength="7"
          spellcheck="false"
          class="chatify:w-full chatify:rounded-lg chatify:border chatify:border-white/10 chatify:bg-black/40 chatify:px-2.5 chatify:py-1 chatify:font-mono chatify:text-xs chatify:font-medium chatify:text-white chatify:outline-none focus:chatify:border-white/30"
          @change="onHexChange"
        />
      </div>

      <!-- RGB Mode Inputs -->
      <div v-else class="chatify:flex chatify:flex-1 chatify:items-center chatify:gap-1.5">
        <input
          type="number"
          min="0"
          max="255"
          :value="currentRgb.r"
          class="chatify:w-full chatify:rounded-lg chatify:border chatify:border-white/10 chatify:bg-black/40 chatify:px-1.5 chatify:py-1 chatify:text-center chatify:font-mono chatify:text-xs chatify:text-white chatify:outline-none focus:chatify:border-white/30"
          @change="onRgbChange('r', $event)"
        />
        <input
          type="number"
          min="0"
          max="255"
          :value="currentRgb.g"
          class="chatify:w-full chatify:rounded-lg chatify:border chatify:border-white/10 chatify:bg-black/40 chatify:px-1.5 chatify:py-1 chatify:text-center chatify:font-mono chatify:text-xs chatify:text-white chatify:outline-none focus:chatify:border-white/30"
          @change="onRgbChange('g', $event)"
        />
        <input
          type="number"
          min="0"
          max="255"
          :value="currentRgb.b"
          class="chatify:w-full chatify:rounded-lg chatify:border chatify:border-white/10 chatify:bg-black/40 chatify:px-1.5 chatify:py-1 chatify:text-center chatify:font-mono chatify:text-xs chatify:text-white chatify:outline-none focus:chatify:border-white/30"
          @change="onRgbChange('b', $event)"
        />
      </div>
    </div>

    <!-- Quick Presets -->
    <div class="chatify:mt-3 chatify:flex chatify:items-center chatify:justify-between chatify:border-t chatify:border-white/10 chatify:pt-2.5">
      <div class="chatify:flex chatify:items-center chatify:gap-1.5">
        <button
          v-for="color in quickPresets"
          :key="color"
          type="button"
          class="chatify:h-4.5 chatify:w-4.5 chatify:rounded-full chatify:border chatify:border-white/15 chatify:transition-transform hover:chatify:scale-125"
          :style="{ backgroundColor: color }"
          :title="color"
          @click="selectPreset(color)"
        />
      </div>

      <button
        type="button"
        class="chatify:rounded-lg chatify:bg-white/10 chatify:px-2.5 chatify:py-1 chatify:text-[10px] chatify:font-semibold chatify:text-white chatify:transition hover:chatify:bg-white/20"
        @click="emit('close')"
      >
        Done
      </button>
    </div>
  </div>
</template>
