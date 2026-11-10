<script setup lang="ts">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { PATTERN_TILE_SIZE } from '../../themes/patterns'
import { useConfigStore } from '../../stores/config'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { preferences, pendingWallpaperPreview, wallpaperPatterns } = storeToRefs(configStore)

const fileInput = ref<HTMLInputElement | null>(null)

const storedImageUrl = computed(
  () => pendingWallpaperPreview.value ?? preferences.value.wallpaper.imageUrl,
)

const hasStoredImage = computed(() => Boolean(storedImageUrl.value))
const showImageControls = computed(() => preferences.value.wallpaper.kind === 'image')

function selectNone() {
  configStore.setWallpaper({ kind: 'none' })
}

function selectPattern(patternId: string) {
  configStore.setWallpaper({ kind: 'pattern', patternId })
}

function onUploadTileClick() {
  if (hasStoredImage.value && configStore.selectWallpaperImage()) {
    return
  }

  fileInput.value?.click()
}

function onFileSelected(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) {
    return
  }

  configStore.stageWallpaper(file)
  ;(event.target as HTMLInputElement).value = ''
}

function removeStoredImage(event: Event) {
  event.stopPropagation()
  configStore.clearWallpaperImage()
}

function toggleBlur() {
  configStore.setWallpaper({ blurEnabled: !preferences.value.wallpaper.blurEnabled })
}

function setBlurAmount(event: Event) {
  const value = Number((event.target as HTMLInputElement).value)
  configStore.setWallpaper({ blurAmount: value })
}

function setDarknessAmount(event: Event) {
  const value = Number((event.target as HTMLInputElement).value)
  configStore.setWallpaper({ darknessAmount: value })
}

function setDarknessPreset(value: number) {
  configStore.setWallpaper({ darknessAmount: value })
}

const previewUrl = (url: string) => `url("${url}")`

const currentBlurPx = computed(() => {
  if (preferences.value.wallpaper.kind !== 'image' || !preferences.value.wallpaper.blurEnabled) {
    return '0px'
  }
  return `${Math.round((preferences.value.wallpaper.blurAmount ?? 50) * 0.3)}px`
})

const currentTileBlurPx = computed(() => {
  if (preferences.value.wallpaper.kind !== 'image' || !preferences.value.wallpaper.blurEnabled) {
    return '0px'
  }
  return `${Math.round((preferences.value.wallpaper.blurAmount ?? 50) * 0.12)}px`
})

const currentDarknessOpacity = computed(() => {
  if (preferences.value.wallpaper.kind !== 'image') {
    return 0
  }
  return Math.min(1, Math.max(0, (preferences.value.wallpaper.darknessAmount ?? 0) / 100))
})

const activePattern = computed(() => {
  if (preferences.value.wallpaper.kind !== 'pattern') {
    return null
  }
  return wallpaperPatterns.value.find((p) => p.id === preferences.value.wallpaper.patternId) ?? null
})
</script>

<template>
  <div class="chatify:min-w-0 chatify:space-y-4">
    <!-- Live Wallpaper Preview Card -->
    <div class="chatify:space-y-2">
      <div class="chatify:flex chatify:items-center chatify:justify-between chatify:px-0.5">
        <label class="chatify:text-xs chatify:font-semibold chatify:tracking-wider chatify:text-chatify-muted chatify:uppercase">
          {{ t('ui.settings.wallpaper_preview', 'Live Chat Preview') }}
        </label>
        <span class="chatify:text-[11px] chatify:font-medium chatify:text-chatify-muted">
          <template v-if="preferences.wallpaper.kind === 'image'">
            {{ preferences.wallpaper.blurEnabled ? `Blur: ${preferences.wallpaper.blurAmount}%` : 'Sharp' }}
            · Darkness: {{ preferences.wallpaper.darknessAmount ?? 0 }}%
          </template>
          <template v-else-if="preferences.wallpaper.kind === 'pattern'">
            Pattern: {{ activePattern?.name || preferences.wallpaper.patternId }}
          </template>
          <template v-else>
            Default
          </template>
        </span>
      </div>

      <div class="chatify:relative chatify:h-44 chatify:w-full chatify:overflow-hidden chatify:rounded-2xl chatify:border chatify:border-white/10 chatify:shadow-2xl chatify:shadow-black/50">
        <!-- 1. Background Base -->
        <div class="chatify:absolute chatify:inset-0 chatify:bg-chatify-panel" />

        <!-- 2. Pattern Layer (if pattern selected) -->
        <div
          v-if="preferences.wallpaper.kind === 'pattern' && activePattern"
          class="chatify-wallpaper-tile-pattern chatify:absolute chatify:inset-0"
          :style="{
            backgroundColor: 'var(--chatify-chat-bg-pattern-tint, var(--chatify-color-chatify-primary))',
            maskImage: previewUrl(activePattern.url),
            WebkitMaskImage: previewUrl(activePattern.url),
            maskSize: PATTERN_TILE_SIZE,
            WebkitMaskSize: PATTERN_TILE_SIZE,
            maskRepeat: 'repeat',
            WebkitMaskRepeat: 'repeat',
          }"
        />

        <!-- 3. Custom Image Layer (if custom image selected) -->
        <div
          v-if="preferences.wallpaper.kind === 'image' && hasStoredImage"
          class="chatify:absolute chatify:inset-0 chatify:overflow-hidden"
        >
          <img
            :src="storedImageUrl || undefined"
            alt="Preview"
            class="chatify:h-full chatify:w-full chatify:object-cover chatify:transition-[filter,transform] chatify:duration-200"
            :style="{
              filter: `blur(${currentBlurPx})`,
              transform: preferences.wallpaper.blurEnabled ? 'scale(1.08)' : 'scale(1)',
            }"
          />
          <!-- Darkness Overlay Layer -->
          <div
            class="chatify:pointer-events-none chatify:absolute chatify:inset-0 chatify:bg-black chatify:transition-opacity chatify:duration-200"
            :style="{ opacity: currentDarknessOpacity }"
          />
        </div>

        <!-- 4. Mock Chat Bubbles Layer -->
        <div class="chatify:relative chatify:z-10 chatify:flex chatify:h-full chatify:flex-col chatify:justify-between chatify:p-3.5">
          <!-- Top Mock Date Badge -->
          <div class="chatify:flex chatify:justify-center">
            <span class="chatify:rounded-full chatify:bg-black/50 chatify:px-2.5 chatify:py-0.5 chatify:text-[10px] chatify:font-medium chatify:text-white/80 chatify:backdrop-blur-md">
              Today
            </span>
          </div>

          <!-- Message Bubbles -->
          <div class="chatify:space-y-2">
            <!-- Incoming message -->
            <div class="chatify:flex chatify:items-end chatify:gap-2 chatify:max-w-[85%]">
              <div class="chatify:flex chatify:h-6 chatify:w-6 chatify:shrink-0 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-primary/30 chatify:text-[10px] chatify:font-bold chatify:text-chatify-primary chatify:ring-1 chatify:ring-chatify-primary/40">
                V
              </div>
              <div class="chatify:relative chatify:rounded-2xl chatify:rounded-bl-sm chatify:bg-chatify-bubble-in chatify:px-3 chatify:py-2 chatify:text-xs chatify:text-chatify-text chatify:shadow-md chatify:backdrop-blur-sm">
                <p class="chatify:leading-relaxed">Hey! How does the wallpaper look?</p>
                <div class="chatify:mt-0.5 chatify:flex chatify:justify-end chatify:text-[9px] chatify:text-chatify-muted">
                  10:42 AM
                </div>
              </div>
            </div>

            <!-- Outgoing message -->
            <div class="chatify:flex chatify:justify-end">
              <div class="chatify:max-w-[85%] chatify:rounded-2xl chatify:rounded-br-sm chatify:bg-chatify-bubble-out chatify:px-3 chatify:py-2 chatify:text-xs chatify:text-white chatify:shadow-md">
                <p class="chatify:leading-relaxed">Crisp and super readable! 🔥</p>
                <div class="chatify:mt-0.5 chatify:flex chatify:items-center chatify:justify-end chatify:gap-1 chatify:text-[9px] chatify:text-white/80">
                  <span>10:43 AM</span>
                  <svg class="chatify:h-3 chatify:w-3" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0l7-7zm2.5 0a.5.5 0 0 0-.708-.708L7.5 10.293l-.646-.647a.5.5 0 0 0-.708.708l1 1a.5.5 0 0 0 .708 0l7-7z"/>
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 6-Tile Wallpaper Selector Grid -->
    <div class="chatify:grid chatify:min-w-0 chatify:grid-cols-3 chatify:gap-2">
      <button
        type="button"
        class="chatify:flex chatify:h-24 chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:border chatify:bg-chatify-bubble-in chatify:text-xs chatify:font-medium chatify:transition"
        :class="preferences.wallpaper.kind === 'none'
          ? 'chatify-tile-selected chatify:text-chatify-primary'
          : 'chatify-border-soft chatify:text-chatify-muted'"
        @click="selectNone"
      >
        {{ $t('ui.settings.none') }}
      </button>

      <button
        v-for="pattern in wallpaperPatterns"
        :key="pattern.id"
        type="button"
        class="chatify:relative chatify:h-24 chatify:overflow-hidden chatify:rounded-xl chatify:border chatify:transition"
        :class="preferences.wallpaper.kind === 'pattern' && preferences.wallpaper.patternId === pattern.id
          ? 'chatify-tile-selected'
          : 'chatify-border-soft'"
        @click="selectPattern(pattern.id)"
      >
        <div class="chatify:absolute chatify:inset-0 chatify:bg-chatify-panel" />
        <div
          class="chatify-wallpaper-tile-pattern chatify:absolute chatify:inset-0"
          :style="{
            backgroundColor: 'var(--chatify-color-chatify-muted)',
            maskImage: previewUrl(pattern.url),
            WebkitMaskImage: previewUrl(pattern.url),
            maskSize: PATTERN_TILE_SIZE,
            WebkitMaskSize: PATTERN_TILE_SIZE,
            maskRepeat: 'repeat',
            WebkitMaskRepeat: 'repeat',
          }"
        />
      </button>

      <button
        type="button"
        class="chatify:relative chatify:h-24 chatify:overflow-hidden chatify:rounded-xl chatify:border chatify:transition"
        :class="preferences.wallpaper.kind === 'image'
          ? 'chatify-tile-selected'
          : 'chatify-border-soft'"
        @click="onUploadTileClick"
      >
        <div v-if="hasStoredImage" class="chatify:relative chatify:h-full chatify:w-full chatify:overflow-hidden">
          <img
            :src="storedImageUrl || undefined"
            :alt="t('ui.settings.custom_wallpaper')"
            class="chatify:h-full chatify:w-full chatify:object-cover chatify:transition-[filter,transform] chatify:duration-200"
            :style="{
              filter: (preferences.wallpaper.kind === 'image' && preferences.wallpaper.blurEnabled)
                ? `blur(${currentTileBlurPx})`
                : 'none',
              transform: (preferences.wallpaper.kind === 'image' && preferences.wallpaper.blurEnabled)
                ? 'scale(1.1)'
                : 'scale(1)',
            }"
          />
          <!-- Darkness overlay on tile -->
          <div
            v-if="preferences.wallpaper.kind === 'image'"
            class="chatify:pointer-events-none chatify:absolute chatify:inset-0 chatify:bg-black chatify:transition-opacity chatify:duration-200"
            :style="{ opacity: currentDarknessOpacity }"
          />
        </div>
        <div
          v-else
          class="chatify:flex chatify:h-full chatify:w-full chatify:flex-col chatify:items-center chatify:justify-center chatify:bg-chatify-sidebar chatify:text-xs chatify:text-chatify-muted"
        >
          <svg class="chatify:mb-1.5 chatify:h-5 chatify:w-5 chatify:text-chatify-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          {{ $t('ui.settings.upload') }}
        </div>

        <button
          v-if="hasStoredImage"
          type="button"
          class="chatify:absolute chatify:end-1 chatify:top-1 chatify:z-10 chatify:flex chatify:h-5 chatify:w-5 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-black/60 chatify:text-white chatify:hover:bg-black/80"
          :aria-label="t('ui.settings.remove_wallpaper')"
          @click="removeStoredImage"
        >
          <svg class="chatify:h-3 chatify:w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </button>

      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        class="chatify:hidden"
        @change="onFileSelected"
      />
    </div>

    <!-- Image Adjustment Controls (Blur & Darkness) -->
    <div v-if="showImageControls" class="chatify:space-y-4 chatify:rounded-2xl chatify:border chatify:border-white/10 chatify:bg-white/[0.03] chatify:p-4 chatify:backdrop-blur-sm">
      <!-- 1. Blur Wallpaper Toggle & Intensity Slider -->
      <div class="chatify:space-y-3">
        <div class="chatify:flex chatify:items-center chatify:justify-between chatify:gap-3">
          <div class="chatify:flex chatify:items-center chatify:gap-2">
            <svg class="chatify:h-4 chatify:w-4 chatify:text-chatify-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <span class="chatify:text-sm chatify:font-medium chatify:text-chatify-text">{{ $t('ui.settings.blur_wallpaper') }}</span>
          </div>
          <button
            type="button"
            class="chatify-settings-toggle chatify:shrink-0"
            :class="preferences.wallpaper.blurEnabled ? 'chatify-settings-toggle-on' : ''"
            role="switch"
            :aria-checked="preferences.wallpaper.blurEnabled"
            @click="toggleBlur"
          />
        </div>

        <div v-if="preferences.wallpaper.blurEnabled" class="chatify:space-y-2 chatify:pl-6 chatify:pt-1">
          <div class="chatify:flex chatify:items-center chatify:justify-between chatify:text-xs chatify:text-chatify-muted">
            <span>{{ $t('ui.settings.blur_intensity') }}</span>
            <span class="chatify:font-mono chatify:font-semibold chatify:text-chatify-primary">{{ preferences.wallpaper.blurAmount }}%</span>
          </div>
          <input
            type="range"
            min="0"
            max="100"
            step="1"
            class="chatify-settings-range chatify:w-full"
            :value="preferences.wallpaper.blurAmount"
            @input="setBlurAmount"
          />
        </div>
      </div>

      <div class="chatify:h-px chatify:w-full chatify:bg-white/10" />

      <!-- 2. Darkness / Dimming Control Slider -->
      <div class="chatify:space-y-3">
        <div class="chatify:flex chatify:items-center chatify:justify-between chatify:gap-3">
          <div class="chatify:flex chatify:items-center chatify:gap-2">
            <svg class="chatify:h-4 chatify:w-4 chatify:text-chatify-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
            <span class="chatify:text-sm chatify:font-medium chatify:text-chatify-text">{{ t('ui.settings.wallpaper_darkness', 'Darkness') }}</span>
          </div>
          <span class="chatify:font-mono chatify:text-xs chatify:font-semibold chatify:text-chatify-primary">
            {{ preferences.wallpaper.darknessAmount ?? 0 }}%
          </span>
        </div>

        <p class="chatify:text-[11px] chatify:text-chatify-muted">
          {{ t('ui.settings.wallpaper_darkness_hint', 'Darken custom wallpaper for higher chat bubble contrast.') }}
        </p>

        <div class="chatify:space-y-2">
          <input
            type="range"
            min="0"
            max="100"
            step="1"
            class="chatify-settings-range chatify:w-full"
            :value="preferences.wallpaper.darknessAmount ?? 0"
            @input="setDarknessAmount"
          />

          <!-- Quick Presets -->
          <div class="chatify:flex chatify:items-center chatify:gap-1.5 chatify:pt-1">
            <button
              v-for="preset in [0, 20, 40, 60, 80]"
              :key="preset"
              type="button"
              class="chatify:flex-1 chatify:rounded-lg chatify:border chatify:py-1 chatify:text-center chatify:text-[10px] chatify:font-medium chatify:transition"
              :class="(preferences.wallpaper.darknessAmount ?? 0) === preset
                ? 'chatify:border-chatify-primary chatify:bg-chatify-primary/20 chatify:text-chatify-primary'
                : 'chatify:border-white/5 chatify:bg-white/[0.04] chatify:text-chatify-muted hover:chatify:bg-white/[0.08] hover:chatify:text-chatify-text'"
              @click="setDarknessPreset(preset)"
            >
              {{ preset }}%
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
