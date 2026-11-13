<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import { useConfigStore } from '../../stores/config'
import UserAvatar from '../ui/UserAvatar.vue'

const emit = defineEmits<{
  open: []
}>()

const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { user } = storeToRefs(configStore)

const displayName = computed(() => user.value?.attributes.name ?? '')
const username = computed(() => user.value?.attributes.username ? `@${user.value.attributes.username}` : '')
const email = computed(() => user.value?.attributes.email ?? '')
</script>

<template>
  <div v-if="user" class="chatify:mb-3 chatify:flex chatify:items-center chatify:gap-3 chatify:rounded-xl chatify:p-1.5 chatify:transition hover:chatify:bg-white/[0.04]">
    <div
      class="chatify:flex chatify:min-w-0 chatify:flex-1 chatify:cursor-pointer chatify:items-center chatify:gap-3"
      role="button"
      tabindex="0"
      :aria-label="t('ui.settings.edit_profile', 'Edit Profile')"
      @click="emit('open')"
      @keydown.enter="emit('open')"
    >
      <UserAvatar size="md" :alt="displayName" />

      <div class="chatify:min-w-0 chatify:flex-1">
        <p class="chatify:truncate chatify:text-base chatify:font-semibold chatify:text-chatify-text">
          {{ displayName }}
        </p>
        <p v-if="username || email" class="chatify:truncate chatify:text-xs chatify:text-chatify-muted">
          {{ username || email }}
        </p>
      </div>

      <svg class="chatify:h-4 chatify:w-4 chatify:shrink-0 chatify:text-chatify-muted/50 chatify:transition group-hover:chatify:text-chatify-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </div>
  </div>
</template>
