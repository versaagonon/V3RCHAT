<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useConfigStore } from '../../stores/config'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import type { ChatifyUser } from '../../types'

const props = withDefaults(
  defineProps<{
    size?: 'xxs' | 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    alt?: string
    src?: string | null
    borderUrl?: string | null
    user?: ChatifyUser | null
  }>(),
  {
    size: 'sm',
    alt: '',
    src: null,
    borderUrl: null,
    user: null,
  },
)

const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { user: currentUser, avatarUploadProgress, avatarUploading, avatarRemoving } = storeToRefs(configStore)

const targetUser = computed(() => props.user ?? currentUser.value)

const sizeClass = computed(() => {
  switch (props.size) {
    case 'xl':
      return 'chatify:h-28 chatify:w-28'
    case 'lg':
      return 'chatify:h-24 chatify:w-24'
    case 'md':
      return 'chatify:h-16 chatify:w-16'
    case 'xs':
      return 'chatify:h-8 chatify:w-8'
    case 'xxs':
      return 'chatify:h-6 chatify:w-6'
    default:
      return 'chatify:h-10 chatify:w-10'
  }
})

const iconClass = computed(() => {
  switch (props.size) {
    case 'xl':
      return 'chatify:h-12 chatify:w-12'
    case 'lg':
      return 'chatify:h-10 chatify:w-10'
    case 'md':
      return 'chatify:h-8 chatify:w-8'
    case 'xs':
      return 'chatify:h-4 chatify:w-4'
    case 'xxs':
      return 'chatify:h-3 chatify:w-3'
    default:
      return 'chatify:h-5 chatify:w-5'
  }
})

const progressLabelClass = computed(() => {
  switch (props.size) {
    case 'xl':
    case 'lg':
      return 'chatify:text-sm'
    case 'md':
      return 'chatify:text-xs'
    default:
      return 'chatify:text-[10px]'
  }
})

const displayName = computed(() => props.alt || targetUser.value?.attributes?.name || t('ui.user.default'))
const avatarSrc = computed(() => props.src ?? targetUser.value?.attributes?.avatar ?? '')
const hasAvatar = computed(() => Boolean(avatarSrc.value))

const borderSrc = computed(() => {
  if (props.borderUrl !== null) {
    return props.borderUrl
  }
  return targetUser.value?.attributes?.avatar_border?.image_url ?? null
})

const isUploadingCurrent = computed(() => !props.user && avatarUploading.value)
const showProgress = computed(() => isUploadingCurrent.value && avatarUploadProgress.value !== null)
</script>

<template>
  <div
    class="chatify:relative chatify:shrink-0 chatify:rounded-full"
    :class="[
      sizeClass,
      isUploadingCurrent ? 'chatify-avatar-uploading' : 'chatify-avatar-ring',
    ]"
  >
    <!-- Avatar Image Circle -->
    <div class="chatify:relative chatify:h-full chatify:w-full chatify:overflow-hidden chatify:rounded-full chatify:bg-chatify-sidebar">
      <img
        v-if="hasAvatar"
        :src="avatarSrc"
        :alt="displayName"
        class="chatify:h-full chatify:w-full chatify:object-cover"
        :class="isUploadingCurrent ? 'chatify-avatar-pulse' : ''"
      />
      <div
        v-else
        class="chatify:flex chatify:h-full chatify:w-full chatify:items-center chatify:justify-center chatify:text-chatify-muted"
        :class="isUploadingCurrent ? 'chatify-avatar-pulse' : ''"
      >
        <svg :class="iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
      </div>

      <div
        v-if="isUploadingCurrent"
        class="chatify:absolute chatify:inset-0 chatify:flex chatify:items-center chatify:justify-center chatify:bg-black/45"
        aria-live="polite"
      >
        <span
          v-if="showProgress"
          class="chatify:font-semibold chatify:text-white"
          :class="progressLabelClass"
        >
          {{ avatarUploadProgress }}%
        </span>
        <span
          v-else-if="avatarRemoving"
          class="chatify:font-medium chatify:text-white/90"
          :class="progressLabelClass"
        >
          …
        </span>
      </div>
    </div>

    <!-- 1:1 Animated Avatar Border Overlay (288:288) -->
    <img
      v-if="borderSrc"
      :src="borderSrc"
      alt=""
      class="chatify-avatar-border chatify:pointer-events-none chatify:absolute chatify:inset-[-10%] chatify:h-[120%] chatify:w-[120%] chatify:max-w-none chatify:object-contain chatify:z-10"
      aria-hidden="true"
    />
    <slot />
  </div>
</template>
