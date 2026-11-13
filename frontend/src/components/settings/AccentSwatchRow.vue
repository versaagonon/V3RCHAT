<script setup lang="ts">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { accentSwatchesForTheme } from '../../themes/presets'
import type { ThemeId } from '../../themes/types'
import { useConfigStore } from '../../stores/config'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import CustomColorPicker from '../ui/CustomColorPicker.vue'

const props = defineProps<{
  themeId: ThemeId
}>()

const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { preferences, colors } = storeToRefs(configStore)

const swatches = computed(() => accentSwatchesForTheme(props.themeId, colors.value))

const accent = computed(() => preferences.value.accentColor.toLowerCase())

const isThemeActive = computed(() => preferences.value.themeId === props.themeId)

function isSwatchActive(color: string): boolean {
  return isThemeActive.value && accent.value === color.toLowerCase()
}

const isCustomColor = computed(() => {
  if (!isThemeActive.value) {
    return false
  }

  return !swatches.value.some((color) => color.toLowerCase() === accent.value)
})

const isPickerOpen = ref(false)

function select(color: string) {
  configStore.setTheme(props.themeId)
  configStore.setAccentColor(color)
}

function togglePicker() {
  isPickerOpen.value = !isPickerOpen.value
  if (isPickerOpen.value) {
    configStore.setTheme(props.themeId)
  }
}

function onCustomColorSelected(value: string) {
  configStore.setTheme(props.themeId)
  configStore.setAccentColor(value)
}

const mixDots = [
  { color: '#833ab4', top: '18%', left: '50%' },
  { color: '#fd1d1d', top: '32%', left: '72%' },
  { color: '#fcb045', top: '58%', left: '68%' },
  { color: '#4ade80', top: '72%', left: '50%' },
  { color: '#22d3ee', top: '58%', left: '32%' },
  { color: '#ec4899', top: '32%', left: '28%' },
  { color: '#6366f1', top: '50%', left: '50%' },
]
</script>

<template>
  <div class="chatify-accent-swatches-box chatify:relative chatify:w-full chatify:rounded-2xl chatify:border chatify:border-white/10 chatify:bg-[#18181b]/90 chatify:p-3.5 chatify:shadow-inner">
    <!-- Header with label and current color hex -->
    <div class="chatify:mb-3 chatify:flex chatify:items-center chatify:justify-between">
      <span class="chatify:text-xs chatify:font-semibold chatify:text-zinc-300">
        {{ t('ui.settings.accent_color', 'Accent Color') }}
      </span>
      <span class="chatify:rounded-md chatify:bg-white/[0.06] chatify:px-2 chatify:py-0.5 chatify:font-mono chatify:text-[10px] chatify:font-medium chatify:uppercase chatify:text-zinc-400">
        {{ preferences.accentColor }}
      </span>
    </div>

    <!-- Wrapped Color Swatches (wraps to next line when row is full) -->
    <div class="chatify:flex chatify:flex-wrap chatify:items-center chatify:gap-2.5">
      <div
        v-for="color in swatches"
        :key="color"
        class="chatify:flex chatify:items-center chatify:justify-center"
      >
        <button
          type="button"
          class="chatify-accent-swatch chatify:relative chatify:flex chatify:h-8 chatify:w-8 chatify:shrink-0 chatify:items-center chatify:justify-center chatify:rounded-full chatify:transition-transform hover:chatify:scale-110 active:chatify:scale-95"
          :aria-label="t('ui.settings.select_accent_color', { hex: color })"
          :aria-pressed="isSwatchActive(color)"
          @click="select(color)"
        >
          <span
            v-if="isSwatchActive(color)"
            class="chatify-accent-swatch-ring chatify:absolute chatify:inset-0 chatify:rounded-full chatify:ring-2 chatify:ring-offset-2 chatify:ring-offset-[#18181b]"
            :style="{ borderColor: color, '--tw-ring-color': color }"
          />
          <span
            class="chatify:rounded-full chatify:shadow-sm chatify:transition-all"
            :class="isSwatchActive(color) ? 'chatify:h-3.5 chatify:w-3.5' : 'chatify:h-8 chatify:w-8'"
            :style="{ backgroundColor: color }"
          />
        </button>
      </div>

      <!-- Custom Color Picker Trigger (Multi-colored mix circle) -->
      <div class="chatify:flex chatify:items-center chatify:justify-center">
        <button
          type="button"
          class="chatify-accent-swatch chatify:relative chatify:flex chatify:h-8 chatify:w-8 chatify:shrink-0 chatify:cursor-pointer chatify:items-center chatify:justify-center chatify:rounded-full chatify:transition-transform hover:chatify:scale-110 active:chatify:scale-95"
          :class="{ 'chatify:ring-2 chatify:ring-white/80 chatify:ring-offset-2 chatify:ring-offset-[#18181b]': isCustomColor || isPickerOpen }"
          :aria-label="isCustomColor ? t('ui.settings.custom_color', { hex: preferences.accentColor }) : t('ui.settings.pick_custom_color')"
          @click="togglePicker"
        >
          <span
            v-if="isCustomColor"
            class="chatify-accent-swatch-ring chatify:absolute chatify:inset-0 chatify:rounded-full chatify:ring-2 chatify:ring-offset-2 chatify:ring-offset-[#18181b]"
            :style="{ borderColor: preferences.accentColor, '--tw-ring-color': preferences.accentColor }"
          />

          <span class="chatify-accent-swatch-mix chatify:relative chatify:h-8 chatify:w-8 chatify:rounded-full chatify:shadow-sm">
            <span
              v-for="(dot, index) in mixDots"
              :key="index"
              class="chatify-accent-swatch-mix-dot chatify:absolute chatify:rounded-full"
              :style="{
                backgroundColor: dot.color,
                top: dot.top,
                left: dot.left,
              }"
            />
          </span>
        </button>
      </div>
    </div>

    <!-- Enhanced Custom Color Picker Popup Drawer / Panel -->
    <Transition
      enter-active-class="chatify:transition chatify:duration-200 chatify:ease-out"
      enter-from-class="chatify:opacity-0 chatify:-translate-y-2 chatify:scale-95"
      enter-to-class="chatify:opacity-100 chatify:translate-y-0 chatify:scale-100"
      leave-active-class="chatify:transition chatify:duration-150 chatify:ease-in"
      leave-from-class="chatify:opacity-100 chatify:translate-y-0 chatify:scale-100"
      leave-to-class="chatify:opacity-0 chatify:-translate-y-2 chatify:scale-95"
    >
      <div
        v-if="isPickerOpen"
        class="chatify:mt-3 chatify:flex chatify:justify-center chatify:border-t chatify:border-white/10 chatify:pt-3"
      >
        <CustomColorPicker
          :model-value="preferences.accentColor"
          @update:model-value="onCustomColorSelected"
          @change="onCustomColorSelected"
          @close="isPickerOpen = false"
        />
      </div>
    </Transition>
  </div>
</template>
