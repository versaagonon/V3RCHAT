<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useConfigStore } from '../../stores/config'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import type { AvatarBorder } from '../../types'
import UserAvatar from '../ui/UserAvatar.vue'

const emit = defineEmits<{
  saved: []
}>()

const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { user, savedAvatarUrl, avatarUploading } = storeToRefs(configStore)

const fileInput = ref<HTMLInputElement | null>(null)
const name = ref(user.value?.attributes.name ?? '')
const username = ref(user.value?.attributes.username ?? '')
const email = ref(user.value?.attributes.email ?? '')
const currentPassword = ref('')
const showPassword = ref(false)
const saving = ref(false)
const errors = ref<Record<string, string[]>>({})

// Avatar Borders
const availableBorders = ref<AvatarBorder[]>([])
const activeBorderId = ref<number | null>(user.value?.attributes.avatar_border?.id ?? null)
const previewBorderUrl = ref<string | null>(null)
const equippingBorder = ref(false)

onMounted(async () => {
  if (configStore.api) {
    try {
      const res = await configStore.api.getUserBorders()
      availableBorders.value = res.data.data.available_borders
      activeBorderId.value = res.data.data.active_border_id
    } catch (e) {
      console.error('Failed to load user borders', e)
    }
  }
})

async function selectBorder(border: AvatarBorder | null) {
  if (equippingBorder.value) return
  equippingBorder.value = true

  const targetId = border ? border.id : null
  previewBorderUrl.value = border ? border.image_url : null

  const res = await configStore.equipAvatarBorder(targetId)
  if (res.success) {
    activeBorderId.value = targetId
  }
  previewBorderUrl.value = null
  equippingBorder.value = false
}

// Track original values to detect modifications
const originalName = computed(() => user.value?.attributes.name ?? '')
const originalUsername = computed(() => user.value?.attributes.username ?? '')
const originalEmail = computed(() => user.value?.attributes.email ?? '')

const isNameDirty = computed(() => name.value.trim() !== originalName.value.trim())
const isUsernameDirty = computed(() => (username.value.trim() || '') !== (originalUsername.value.trim() || ''))
const isEmailDirty = computed(() => email.value.trim().toLowerCase() !== originalEmail.value.trim().toLowerCase())

const isFormDirty = computed(() => isNameDirty.value || isUsernameDirty.value || isEmailDirty.value)

// Watch for external user store changes (e.g. initial boot or re-fetch)
watch(user, (nextUser) => {
  if (nextUser && !saving.value) {
    if (!isNameDirty.value) name.value = nextUser.attributes.name ?? ''
    if (!isUsernameDirty.value) username.value = nextUser.attributes.username ?? ''
    if (!isEmailDirty.value) email.value = nextUser.attributes.email ?? ''
  }
}, { immediate: true })

const canRemoveAvatar = computed(() => Boolean(savedAvatarUrl.value) && Boolean(user.value?.attributes.avatar))

function openAvatarPicker() {
  if (avatarUploading.value || saving.value) {
    return
  }
  fileInput.value?.click()
}

async function onFileSelected(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) {
    return
  }

  await configStore.uploadAvatar(file)
  ;(event.target as HTMLInputElement).value = ''
}

async function removeAvatar() {
  if (avatarUploading.value || saving.value) {
    return
  }

  await configStore.removeAvatar()
}

async function handleSave() {
  errors.value = {}

  // Client-side quick validation
  if (!name.value.trim()) {
    errors.value.name = [t('ui.settings.name_required', 'Name cannot be empty.')]
    return
  }

  if (isEmailDirty.value && !currentPassword.value) {
    errors.value.current_password = [
      t('ui.settings.password_required_for_email', 'Current password is required to change email address.'),
    ]
    return
  }

  saving.value = true

  const result = await configStore.updateProfile({
    name: name.value.trim(),
    username: username.value.trim() || null,
    email: email.value.trim(),
    current_password: isEmailDirty.value ? currentPassword.value : undefined,
  })

  saving.value = false

  if (result.success) {
    currentPassword.value = ''
    errors.value = {}
    emit('saved')
  } else if (result.errors) {
    errors.value = result.errors
  }
}
</script>

<template>
  <div class="chatify-settings-profile-view chatify:space-y-4 chatify:p-1">
    <!-- Avatar Section (Clean, Container-less) -->
    <div class="chatify:flex chatify:flex-col chatify:items-center chatify:py-2 chatify:text-center">
      <div class="chatify:group chatify:relative chatify:cursor-pointer" @click="openAvatarPicker">
        <div class="chatify:relative chatify:rounded-full chatify:ring-2 chatify:ring-chatify-primary/40 chatify:transition-all chatify:duration-300 group-hover:chatify:ring-chatify-primary chatify:group-hover:shadow-[0_0_20px_rgba(33,128,243,0.35)]">
          <UserAvatar size="lg" :alt="name || originalName" :border-url="previewBorderUrl" />

          <!-- Camera overlay hover badge -->
          <div class="chatify:absolute chatify:inset-0 chatify:flex chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-black/50 chatify:opacity-0 chatify:backdrop-blur-[2px] chatify:transition-opacity chatify:duration-200 group-hover:chatify:opacity-100">
            <svg class="chatify:h-6 chatify:w-6 chatify:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
        </div>

        <input
          ref="fileInput"
          type="file"
          accept="image/png,image/jpeg,image/jpg,image/gif,image/webp"
          class="chatify:hidden"
          @change="onFileSelected"
        />
      </div>

      <!-- Avatar Action Buttons -->
      <div class="chatify:mt-3 chatify:flex chatify:items-center chatify:gap-2">
        <button
          type="button"
          class="chatify:inline-flex chatify:items-center chatify:gap-1.5 chatify:rounded-lg chatify:border chatify:border-white/10 chatify:bg-white/[0.06] chatify:px-3 chatify:py-1.5 chatify:text-xs chatify:font-medium chatify:text-chatify-text chatify:transition hover:chatify:bg-white/[0.12] disabled:chatify:opacity-50"
          :disabled="avatarUploading || saving"
          @click="openAvatarPicker"
        >
          <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          {{ t('ui.settings.change_photo', 'Change photo') }}
        </button>

        <button
          v-if="canRemoveAvatar"
          type="button"
          class="chatify:inline-flex chatify:items-center chatify:gap-1.5 chatify:rounded-lg chatify:border chatify:border-red-500/20 chatify:bg-red-500/10 chatify:px-3 chatify:py-1.5 chatify:text-xs chatify:font-medium chatify:text-red-400 chatify:transition hover:chatify:bg-red-500/20 disabled:chatify:opacity-50"
          :disabled="avatarUploading || saving"
          @click="removeAvatar"
        >
          <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          {{ t('ui.settings.remove_photo', 'Remove photo') }}
        </button>
      </div>

      <p class="chatify:mt-2 chatify:text-[11px] chatify:text-chatify-muted">
        {{ t('ui.settings.no_password_required', 'Profile photo can be changed without a password.') }}
      </p>
    </div>

    <!-- Animated Avatar Border Selector -->
    <div class="chatify:space-y-2 chatify:rounded-2xl chatify:border chatify:border-white/10 chatify:bg-white/[0.03] chatify:p-3.5">
      <div class="chatify:flex chatify:items-center chatify:justify-between">
        <div class="chatify:flex chatify:items-center chatify:gap-2">
          <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
            Animated Avatar Border
          </label>
          <span
            class="chatify:rounded chatify:px-1.5 chatify:py-0.5 chatify:text-[9px] chatify:font-bold chatify:uppercase"
            :class="(user?.attributes.role ?? 'node') === 'admin' ? 'chatify:bg-amber-400/20 chatify:text-amber-300' : 'chatify:bg-white/10 chatify:text-zinc-400'"
          >
            {{ (user?.attributes.role ?? 'node') === 'admin' ? 'Admin All Unlocked' : 'Node Role' }}
          </span>
        </div>
        <span class="chatify:text-[10px] chatify:text-chatify-muted">
          {{ activeBorderId ? (availableBorders.find((b) => b.id === activeBorderId)?.name || 'Equipped') : 'None' }}
        </span>
      </div>

      <!-- Border Selection Carousel -->
      <div v-if="availableBorders.length > 0" class="chatify:flex chatify:gap-2 chatify:overflow-x-auto chatify:py-1 chatify:scrollbar-thin">
        <!-- Option: None -->
        <button
          type="button"
          class="chatify:group chatify:relative chatify:flex chatify:h-16 chatify:w-16 chatify:shrink-0 chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:border chatify:transition"
          :class="activeBorderId === null ? 'chatify:border-chatify-primary chatify:bg-chatify-primary/10' : 'chatify:border-white/10 chatify:bg-white/[0.04] hover:chatify:bg-white/[0.08]'"
          :disabled="equippingBorder"
          @click="selectBorder(null)"
        >
          <span class="chatify:text-xs chatify:font-medium chatify:text-chatify-muted">None</span>
        </button>

        <!-- Option: Each Unlocked Border -->
        <button
          v-for="border in availableBorders"
          :key="border.id"
          type="button"
          class="chatify:group chatify:relative chatify:flex chatify:h-16 chatify:w-16 chatify:shrink-0 chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:border chatify:p-1 chatify:transition"
          :class="activeBorderId === border.id ? 'chatify:border-chatify-primary chatify:bg-chatify-primary/15 chatify:ring-2 chatify:ring-chatify-primary/40' : 'chatify:border-white/10 chatify:bg-white/[0.04] hover:chatify:bg-white/[0.08]'"
          :disabled="equippingBorder"
          :title="border.name"
          @click="selectBorder(border)"
        >
          <div class="chatify:relative chatify:h-9 chatify:w-9">
            <div class="chatify:h-full chatify:w-full chatify:rounded-full chatify:bg-zinc-800" />
            <img
              :src="border.image_url"
              :alt="border.name"
              class="chatify:pointer-events-none chatify:absolute chatify:inset-[-18%] chatify:h-[136%] chatify:w-[136%] chatify:max-w-none chatify:object-contain chatify:z-10"
            />
          </div>
          <span class="chatify:mt-0.5 chatify:w-full chatify:truncate chatify:text-center chatify:text-[9px] chatify:font-medium chatify:text-chatify-text">
            {{ border.name }}
          </span>
        </button>
      </div>

      <!-- Notice when node user has no borders gifted yet -->
      <div v-else class="chatify:rounded-xl chatify:border chatify:border-white/5 chatify:bg-white/[0.02] chatify:p-3 chatify:text-center">
        <p class="chatify:text-xs chatify:text-chatify-muted">
          No animated borders unlocked yet. An administrator can gift exclusive animated borders to your profile.
        </p>
      </div>
    </div>

    <!-- General Error Banner -->
    <div
      v-if="errors.general?.length"
      class="chatify:flex chatify:items-start chatify:gap-2 chatify:rounded-xl chatify:border chatify:border-red-500/30 chatify:bg-red-500/10 chatify:p-3 chatify:text-xs chatify:text-red-400"
    >
      <svg class="chatify:h-4 chatify:w-4 chatify:shrink-0 chatify:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span>{{ errors.general[0] }}</span>
    </div>

    <!-- Edit Profile Form -->
    <form class="chatify:space-y-3.5" @submit.prevent="handleSave">
      <!-- 1. Full Name -->
      <div class="chatify:space-y-1.5">
        <div class="chatify:flex chatify:items-center chatify:justify-between">
          <label class="chatify:text-xs chatify:font-medium chatify:text-chatify-text">
            {{ t('ui.settings.name', 'Full Name') }}
          </label>
          <span class="chatify:inline-flex chatify:items-center chatify:rounded-full chatify:bg-emerald-500/10 chatify:px-2 chatify:py-0.5 chatify:text-[10px] chatify:font-medium chatify:text-emerald-400">
            No password required
          </span>
        </div>
        <div class="chatify:relative">
          <div class="chatify:pointer-events-none chatify:absolute chatify:inset-y-0 chatify:left-0 chatify:flex chatify:items-center chatify:pl-3 chatify:text-chatify-muted">
            <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <input
            v-model="name"
            type="text"
            required
            maxlength="255"
            placeholder="Enter display name..."
            class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2.5 chatify:pl-9 chatify:pr-3 chatify:text-sm chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
            :class="errors.name ? 'chatify:!border-red-500/60 chatify:!ring-red-500/20' : ''"
          />
        </div>
        <p v-if="errors.name?.length" class="chatify:text-[11px] chatify:text-red-400">
          {{ errors.name[0] }}
        </p>
      </div>

      <!-- 2. Username -->
      <div class="chatify:space-y-1.5">
        <div class="chatify:flex chatify:items-center chatify:justify-between">
          <label class="chatify:text-xs chatify:font-medium chatify:text-chatify-text">
            {{ t('ui.settings.username', 'Username') }}
          </label>
          <span class="chatify:inline-flex chatify:items-center chatify:rounded-full chatify:bg-emerald-500/10 chatify:px-2 chatify:py-0.5 chatify:text-[10px] chatify:font-medium chatify:text-emerald-400">
            No password required
          </span>
        </div>
        <div class="chatify:relative">
          <div class="chatify:pointer-events-none chatify:absolute chatify:inset-y-0 chatify:left-0 chatify:flex chatify:items-center chatify:pl-3.5 chatify:text-sm chatify:font-semibold chatify:text-chatify-muted">
            @
          </div>
          <input
            v-model="username"
            type="text"
            maxlength="50"
            placeholder="your_username"
            class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2.5 chatify:pl-9 chatify:pr-3 chatify:text-sm chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
            :class="errors.username ? 'chatify:!border-red-500/60 chatify:!ring-red-500/20' : ''"
          />
        </div>
        <p v-if="errors.username?.length" class="chatify:text-[11px] chatify:text-red-400">
          {{ errors.username[0] }}
        </p>
        <p v-else class="chatify:text-[11px] chatify:text-chatify-muted">
          Letters, numbers, dashes, and underscores (min 3 characters).
        </p>
      </div>

      <!-- 3. Email Address -->
      <div class="chatify:space-y-1.5">
        <div class="chatify:flex chatify:items-center chatify:justify-between">
          <label class="chatify:text-xs chatify:font-medium chatify:text-chatify-text">
            {{ t('ui.settings.email', 'Email') }}
          </label>
          <span
            class="chatify:inline-flex chatify:items-center chatify:rounded-full chatify:px-2 chatify:py-0.5 chatify:text-[10px] chatify:font-medium"
            :class="isEmailDirty ? 'chatify:bg-amber-500/15 chatify:text-amber-400 chatify:border chatify:border-amber-500/30' : 'chatify:bg-white/[0.06] chatify:text-chatify-muted'"
          >
            {{ isEmailDirty ? 'Password required when changed' : 'Security verified' }}
          </span>
        </div>
        <div class="chatify:relative">
          <div class="chatify:pointer-events-none chatify:absolute chatify:inset-y-0 chatify:left-0 chatify:flex chatify:items-center chatify:pl-3 chatify:text-chatify-muted">
            <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
          <input
            v-model="email"
            type="email"
            required
            maxlength="255"
            placeholder="name@example.com"
            class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2.5 chatify:pl-9 chatify:pr-3 chatify:text-sm chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
            :class="errors.email ? 'chatify:!border-red-500/60 chatify:!ring-red-500/20' : ''"
          />
        </div>
        <p v-if="errors.email?.length" class="chatify:text-[11px] chatify:text-red-400">
          {{ errors.email[0] }}
        </p>
      </div>

      <!-- 4. Current Password Field (Dynamically Revealed When Email Changes) -->
      <Transition
        enter-active-class="chatify:transition chatify:duration-250 chatify:ease-out"
        enter-from-class="chatify:opacity-0 chatify:-translate-y-2 chatify:scale-98"
        enter-to-class="chatify:opacity-100 chatify:translate-y-0 chatify:scale-100"
        leave-active-class="chatify:transition chatify:duration-200 chatify:ease-in"
        leave-from-class="chatify:opacity-100 chatify:translate-y-0 chatify:scale-100"
        leave-to-class="chatify:opacity-0 chatify:-translate-y-2 chatify:scale-98"
      >
        <div v-if="isEmailDirty" class="chatify:space-y-2 chatify:rounded-xl chatify:border chatify:border-amber-500/30 chatify:bg-amber-500/[0.08] chatify:p-3.5">
          <div class="chatify:flex chatify:items-center chatify:gap-2 chatify:text-amber-400">
            <svg class="chatify:h-4 chatify:w-4 chatify:shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span class="chatify:text-xs chatify:font-semibold">Security Confirmation Required</span>
          </div>

          <p class="chatify:text-[11px] chatify:leading-relaxed chatify:text-amber-200/90">
            To safeguard your account, please enter your <strong>current password</strong> to confirm your new email address.
          </p>

          <div class="chatify:relative chatify:mt-1">
            <div class="chatify:pointer-events-none chatify:absolute chatify:inset-y-0 chatify:left-0 chatify:flex chatify:items-center chatify:pl-3 chatify:text-amber-400/70">
              <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
              </svg>
            </div>
            <input
              v-model="currentPassword"
              :type="showPassword ? 'text' : 'password'"
              required
              placeholder="Enter current password..."
              class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2.5 chatify:pl-9 chatify:pr-10 chatify:text-sm chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
              :class="errors.current_password ? 'chatify:!border-red-500/70 chatify:!ring-red-500/20' : 'chatify:border-amber-500/40'"
            />
            <button
              type="button"
              class="chatify:absolute chatify:inset-y-0 chatify:right-0 chatify:flex chatify:items-center chatify:pr-3 chatify:text-chatify-muted hover:chatify:text-chatify-text"
              @click="showPassword = !showPassword"
            >
              <svg v-if="showPassword" class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
              </svg>
              <svg v-else class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
          </div>
          <p v-if="errors.current_password?.length" class="chatify:text-[11px] chatify:font-medium chatify:text-red-400">
            {{ errors.current_password[0] }}
          </p>
        </div>
      </Transition>

      <!-- Submit / Save Button -->
      <div class="chatify:pt-2">
        <button
          type="submit"
          class="chatify:flex chatify:w-full chatify:items-center chatify:justify-center chatify:gap-2 chatify:rounded-xl chatify:bg-chatify-primary chatify:py-2.5 chatify:px-4 chatify:text-sm chatify:font-semibold chatify:text-white chatify:shadow-lg chatify:shadow-chatify-primary/20 chatify:transition hover:chatify:opacity-95 disabled:chatify:opacity-50"
          :disabled="saving || !isFormDirty"
        >
          <svg
            v-if="saving"
            class="chatify:h-4 chatify:w-4 chatify:animate-spin"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle class="chatify:opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="chatify:opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
          </svg>
          <span>{{ saving ? t('ui.common.saving', 'Saving...') : t('ui.common.save', 'Save Changes') }}</span>
        </button>
      </div>
    </form>
  </div>
</template>
