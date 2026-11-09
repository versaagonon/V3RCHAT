<script setup lang="ts">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useConfigStore } from '../../stores/config'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import { getFontById } from '../../themes/fonts'
import type { InterfaceDensity } from '../../themes/types'

const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { preferences, fontOptions, user } = storeToRefs(configStore)

const query = ref('')

const currentDensity = computed<InterfaceDensity>(() => {
  return preferences.value.density ?? 'comfortable'
})

const currentFontSize = computed<number>(() => {
  return preferences.value.fontSize ?? 14
})

const currentUiScale = computed<number>(() => {
  return preferences.value.uiScale ?? 100
})

const currentFontStack = computed<string>(() => {
  return getFontById(preferences.value.fontFamily).stack
})

const currentUserName = computed<string>(() => {
  return user.value?.attributes?.name ?? 'Boim'
})

const filteredFonts = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) {
    return fontOptions.value
  }

  return fontOptions.value.filter((font) => font.label.toLowerCase().includes(q))
})

function selectDensity(density: InterfaceDensity) {
  configStore.setDensity(density)
}

function onFontSizeInput(event: Event) {
  const target = event.target as HTMLInputElement
  const val = Number(target.value)
  if (Number.isFinite(val)) {
    configStore.setFontSize(val)
  }
}

function onUiScaleInput(event: Event) {
  const target = event.target as HTMLInputElement
  const val = Number(target.value)
  if (Number.isFinite(val)) {
    configStore.setUiScale(val)
  }
}

function selectFont(fontId: string) {
  configStore.setFontFamily(fontId)
}
</script>

<template>
  <div class="chatify:space-y-5 chatify:px-1 chatify:pb-8 chatify:min-h-0">
    <!-- Interface Density -->
    <div class="chatify:space-y-2">
      <div class="chatify:flex chatify:items-center chatify:justify-between">
        <label class="chatify:text-sm chatify:font-medium chatify:text-chatify-text">
          {{ t('ui.settings.interface_density', 'Interface density') }}
        </label>
      </div>

      <div class="chatify:flex chatify:rounded-xl chatify:bg-[#121214] chatify:p-1 chatify:border chatify:border-white/5">
        <button
          type="button"
          class="chatify:flex-1 chatify:py-2 chatify:px-3 chatify:text-xs chatify:sm:text-sm chatify:font-medium chatify:rounded-lg chatify:transition-all chatify:duration-150 chatify:text-center"
          :class="currentDensity === 'compact'
            ? 'chatify:bg-zinc-800 chatify:text-white chatify:shadow-sm chatify:border chatify:border-white/10'
            : 'chatify:text-zinc-400 hover:chatify:text-zinc-200 hover:chatify:bg-white/5'"
          @click="selectDensity('compact')"
        >
          {{ t('ui.settings.density_compact', 'Compact') }}
        </button>

        <button
          type="button"
          class="chatify:flex-1 chatify:py-2 chatify:px-3 chatify:text-xs chatify:sm:text-sm chatify:font-medium chatify:rounded-lg chatify:transition-all chatify:duration-150 chatify:text-center"
          :class="currentDensity === 'comfortable'
            ? 'chatify:bg-zinc-800 chatify:text-white chatify:shadow-sm chatify:border chatify:border-white/10'
            : 'chatify:text-zinc-400 hover:chatify:text-zinc-200 hover:chatify:bg-white/5'"
          @click="selectDensity('comfortable')"
        >
          {{ t('ui.settings.density_comfortable', 'Comfortable') }}
        </button>

        <button
          type="button"
          class="chatify:flex-1 chatify:py-2 chatify:px-3 chatify:text-xs chatify:sm:text-sm chatify:font-medium chatify:rounded-lg chatify:transition-all chatify:duration-150 chatify:text-center"
          :class="currentDensity === 'spacious'
            ? 'chatify:bg-zinc-800 chatify:text-white chatify:shadow-sm chatify:border chatify:border-white/10'
            : 'chatify:text-zinc-400 hover:chatify:text-zinc-200 hover:chatify:bg-white/5'"
          @click="selectDensity('spacious')"
        >
          {{ t('ui.settings.density_spacious', 'Spacious') }}
        </button>
      </div>
    </div>

    <!-- Font Size Slider -->
    <div class="chatify:space-y-1.5">
      <div class="chatify:flex chatify:items-center chatify:justify-between chatify:text-xs chatify:text-chatify-muted">
        <label class="chatify:text-sm chatify:font-medium chatify:text-chatify-text">
          {{ t('ui.settings.font_size', 'Font size') }}
        </label>
      </div>

      <div class="chatify:flex chatify:items-center chatify:justify-between chatify:text-xs chatify:text-chatify-muted chatify:px-1">
        <span>Small</span>
        <span class="chatify:text-sm chatify:font-semibold chatify:text-white chatify:tracking-wide">
          {{ currentFontSize }}px
        </span>
        <span>Large</span>
      </div>

      <div class="chatify:px-1">
        <input
          type="range"
          min="12"
          max="20"
          step="1"
          :value="currentFontSize"
          class="v3r-volume-slider"
          aria-label="Font size"
          @input="onFontSizeInput"
        />
      </div>
    </div>

    <!-- UI Scale Slider -->
    <div class="chatify:space-y-1.5">
      <div class="chatify:flex chatify:items-center chatify:justify-between chatify:text-xs chatify:text-chatify-muted">
        <label class="chatify:text-sm chatify:font-medium chatify:text-chatify-text">
          {{ t('ui.settings.ui_scale', 'UI Scale') }}
        </label>
      </div>

      <div class="chatify:flex chatify:items-center chatify:justify-between chatify:text-xs chatify:text-chatify-muted chatify:px-1">
        <span>80%</span>
        <span class="chatify:text-sm chatify:font-semibold chatify:text-white chatify:tracking-wide">
          {{ currentUiScale }}%
        </span>
        <span>120%</span>
      </div>

      <div class="chatify:px-1">
        <input
          type="range"
          min="80"
          max="120"
          step="5"
          :value="currentUiScale"
          class="v3r-volume-slider"
          aria-label="UI Scale"
          @input="onUiScaleInput"
        />
      </div>
    </div>

    <!-- Preview Interface Card (placed above Font Family) -->
    <div class="chatify:space-y-2">
      <label class="chatify:text-sm chatify:font-medium chatify:text-chatify-text">
        {{ t('ui.settings.preview_interface', 'Preview interface') }}
      </label>

      <div
        class="chatify:rounded-xl chatify:border chatify:border-white/10 chatify:bg-[#121214] chatify:p-4 chatify:shadow-inner chatify:transition-all"
        :style="{ fontFamily: currentFontStack }"
      >
        <div
          class="chatify:transition-all"
          :class="{
            'chatify:space-y-2': currentDensity === 'compact',
            'chatify:space-y-3': currentDensity === 'comfortable',
            'chatify:space-y-4': currentDensity === 'spacious',
          }"
        >
          <div>
            <h4
              class="chatify:font-semibold chatify:text-white chatify:tracking-tight"
              :style="{ fontSize: `${currentFontSize + 1}px` }"
            >
              Hello, {{ currentUserName }}!
            </h4>
            <p
              class="chatify:text-zinc-400 chatify:mt-0.5"
              :style="{ fontSize: `${Math.max(11, currentFontSize - 2)}px` }"
            >
              Your interface preview
            </p>
          </div>

          <div
            class="chatify:rounded-lg chatify:border chatify:border-white/5 chatify:bg-zinc-800/80 chatify:text-zinc-200 chatify:shadow-sm chatify:transition-all"
            :class="{
              'chatify:px-3 chatify:py-2': currentDensity === 'compact',
              'chatify:px-4 chatify:py-2.5': currentDensity === 'comfortable',
              'chatify:px-5 chatify:py-3.5': currentDensity === 'spacious',
            }"
            :style="{ fontSize: `${currentFontSize}px` }"
          >
            {{ t('ui.settings.sample_message_content', 'Sample message content') }}
          </div>
        </div>

        <div class="chatify:mt-3.5 chatify:pt-2.5 chatify:border-t chatify:border-white/5 chatify:text-xs chatify:text-zinc-400">
          Density: {{ currentDensity }} · Font: {{ currentFontSize }}px · Scale: {{ currentUiScale }}%
        </div>
      </div>
    </div>

    <!-- Font Family Selector (placed below preview) -->
    <div class="chatify:space-y-2">
      <div class="chatify:flex chatify:items-center chatify:justify-between">
        <label class="chatify:text-sm chatify:font-medium chatify:text-chatify-text">
          {{ t('ui.settings.font', 'Font family') }}
        </label>
      </div>

      <input
        v-model="query"
        type="search"
        :placeholder="t('ui.settings.search', 'Search fonts...')"
        class="chatify-settings-input chatify:w-full chatify:rounded-lg chatify:px-3 chatify:py-2 chatify:text-sm chatify:text-chatify-text"
      />

      <div class="chatify:max-h-40 chatify:overflow-y-auto chatify:overflow-x-hidden chatify:rounded-xl chatify:border chatify-border-soft">
        <button
          v-for="font in filteredFonts"
          :key="font.id"
          type="button"
          class="chatify-list-item chatify:flex chatify:w-full chatify:items-center chatify:gap-3 chatify:border-b chatify-border-soft chatify:px-3.5 chatify:py-2.5 chatify:text-start chatify:last:border-b-0"
          @click="selectFont(font.id)"
        >
          <span
            class="chatify:flex chatify:h-4 chatify:w-4 chatify:shrink-0 chatify:items-center chatify:justify-center chatify:rounded-full chatify:border-2"
            :class="preferences.fontFamily === font.id ? 'chatify:border-chatify-primary' : 'chatify:border-chatify-muted'"
          >
            <span
              v-if="preferences.fontFamily === font.id"
              class="chatify:h-2 chatify:w-2 chatify:rounded-full chatify:bg-chatify-primary"
            />
          </span>
          <span class="chatify:truncate chatify:text-sm chatify:text-chatify-text" :style="{ fontFamily: font.stack }">
            {{ font.label }}
          </span>
        </button>
      </div>
    </div>
  </div>
</template>
