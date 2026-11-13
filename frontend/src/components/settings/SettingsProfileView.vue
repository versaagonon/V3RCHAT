<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useConfigStore } from '../../stores/config'
import { useToastStore } from '../../stores/toast'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import type { AvatarBorder, Nameplate, ProfileEffect } from '../../types'
import UserAvatar from '../ui/UserAvatar.vue'
import UserNameplate from '../ui/UserNameplate.vue'

const emit = defineEmits<{
  saved: []
}>()

const configStore = useConfigStore()
const toastStore = useToastStore()
const { t } = useChatifyI18n()
const { user, savedAvatarUrl, avatarUploading } = storeToRefs(configStore)

const fileInput = ref<HTMLInputElement | null>(null)
const bannerFileInput = ref<HTMLInputElement | null>(null)
const bannerUploading = ref(false)
const name = ref(user.value?.attributes.name ?? '')
const username = ref(user.value?.attributes.username ?? '')
const email = ref(user.value?.attributes.email ?? '')
const currentPassword = ref('')
const showPassword = ref(false)
const saving = ref(false)
const saveSuccess = ref(false)
const saveSuccessMessage = ref<string | null>(null)
const errors = ref<Record<string, string[]>>({})
let successTimer: ReturnType<typeof setTimeout> | null = null

// Avatar Borders
const availableBorders = ref<AvatarBorder[]>([])
const activeBorderId = ref<number | null>(user.value?.attributes.avatar_border?.id ?? null)
const previewBorderUrl = ref<string | null>(null)
const equippingBorder = ref(false)

// Nameplates (gift-only, mirrors borders)
const availableNameplates = ref<Nameplate[]>([])
const activeNameplateId = ref<number | null>(user.value?.attributes.nameplate?.id ?? null)
const previewNameplateUrl = ref<string | null>(null)
const equippingNameplate = ref(false)
const nameplatesError = ref<string | null>(null)
const bordersError = ref<string | null>(null)
const loadingPickers = ref(true)

// Profile effects (paired 450x880 APNG, gift-only like nameplates)
const availableProfileEffects = ref<ProfileEffect[]>([])
const activeProfileEffectId = ref<number | null>(user.value?.attributes.profile_effect?.id ?? null)
const equippingProfileEffect = ref(false)
const profileEffectsError = ref<string | null>(null)

const activeProfileEffectName = computed(
  () => availableProfileEffects.value.find((e) => e.id === activeProfileEffectId.value)?.name ?? null,
)

const activeNameplateName = computed(
  () => availableNameplates.value.find((n) => n.id === activeNameplateId.value)?.name ?? null,
)

const activeBorderName = computed(
  () => availableBorders.value.find((b) => b.id === activeBorderId.value)?.name ?? null,
)

// The combined preview shows a pending equip (preview*) immediately, falling back
// to whatever is currently equipped.
const previewNameplate = computed(() => {
  if (previewNameplateUrl.value) {
    return { image_url: previewNameplateUrl.value }
  }
  return availableNameplates.value.find((n) => n.id === activeNameplateId.value) ?? null
})

const combinedPreviewLabel = computed(() => {
  const plate = previewNameplate.value ? (activeNameplateName.value || 'Nameplate') : 'No nameplate'
  const border = (previewBorderUrl.value || activeBorderName.value) ? (activeBorderName.value || 'Border') : 'No border'
  return `${border} · ${plate}`
})

const combinedPreviewSubtitle = computed(() => {
  const parts: string[] = []
  if (previewBorderUrl.value || activeBorderName.value) parts.push(`${activeBorderName.value || 'border'} equipped`)
  if (previewNameplate.value) parts.push('nameplate active')
  return parts.length > 0 ? parts.join(' · ') : 'No decorations equipped'
})

onMounted(async () => {
  if (!configStore.api) return

  // Load borders and nameplates independently so one failing endpoint cannot
  // blank out the other picker (a missing table used to swallow both).
  const [borderResult, nameplateResult, effectResult] = await Promise.allSettled([
    configStore.api.getUserBorders(),
    configStore.api.getUserNameplates(),
    configStore.api.getUserProfileEffects(),
  ])

  if (borderResult.status === 'fulfilled') {
    availableBorders.value = borderResult.value.data.data.available_borders ?? []
    activeBorderId.value = borderResult.value.data.data.active_border_id ?? null
  } else {
    console.error('Failed to load user borders', borderResult.reason)
    bordersError.value = 'Could not load avatar borders.'
  }

  if (nameplateResult.status === 'fulfilled') {
    availableNameplates.value = nameplateResult.value.data.data.available_nameplates ?? []
    activeNameplateId.value = nameplateResult.value.data.data.active_nameplate_id ?? null
  } else {
    console.error('Failed to load user nameplates', nameplateResult.reason)
    nameplatesError.value = 'Could not load nameplates.'
  }

  if (effectResult.status === 'fulfilled') {
    availableProfileEffects.value = effectResult.value.data.data.available_profile_effects ?? []
    activeProfileEffectId.value = effectResult.value.data.data.active_profile_effect_id ?? null
  } else {
    console.error('Failed to load user profile effects', effectResult.reason)
    profileEffectsError.value = 'Could not load profile effects.'
  }

  loadingPickers.value = false
})

async function reloadNameplates() {
  if (!configStore.api) return
  nameplatesError.value = null
  try {
    const res = await configStore.api.getUserNameplates()
    availableNameplates.value = res.data.data.available_nameplates ?? []
    activeNameplateId.value = res.data.data.active_nameplate_id ?? null
  } catch (e) {
    console.error('Failed to reload nameplates', e)
    nameplatesError.value = 'Could not load nameplates.'
  }
}

async function selectNameplate(nameplate: Nameplate | null) {
  if (equippingNameplate.value) return
  equippingNameplate.value = true

  const targetId = nameplate ? nameplate.id : null
  previewNameplateUrl.value = nameplate ? nameplate.image_url : null

  const res = await configStore.equipNameplate(targetId)
  if (res.success) {
    activeNameplateId.value = targetId
    toastStore.show({
      message: nameplate ? `Equipped "${nameplate.name}" nameplate!` : 'Nameplate removed.',
      icon: 'success',
      placement: 'top',
    })
  } else {
    toastStore.show({
      message: res.message || 'Failed to equip nameplate.',
      icon: 'error',
      placement: 'top',
    })
  }
  previewNameplateUrl.value = null
  equippingNameplate.value = false
}

async function reloadProfileEffects() {
  if (!configStore.api) return
  profileEffectsError.value = null
  try {
    const res = await configStore.api.getUserProfileEffects()
    availableProfileEffects.value = res.data.data.available_profile_effects ?? []
    activeProfileEffectId.value = res.data.data.active_profile_effect_id ?? null
  } catch (e) {
    console.error('Failed to reload profile effects', e)
    profileEffectsError.value = 'Could not load profile effects.'
  }
}

async function selectProfileEffect(effect: ProfileEffect | null) {
  if (equippingProfileEffect.value) return
  equippingProfileEffect.value = true

  const targetId = effect ? effect.id : null

  const res = await configStore.equipProfileEffect(targetId)
  if (res.success) {
    activeProfileEffectId.value = targetId
    toastStore.show({
      message: effect ? `Equipped "${effect.name}" profile effect!` : 'Profile effect removed.',
      icon: 'success',
      placement: 'top',
    })
  } else {
    toastStore.show({
      message: res.message || 'Failed to equip profile effect.',
      icon: 'error',
      placement: 'top',
    })
  }
  equippingProfileEffect.value = false
}

async function selectBorder(border: AvatarBorder | null) {
  if (equippingBorder.value) return
  equippingBorder.value = true
  saveSuccess.value = false
  saveSuccessMessage.value = null

  const targetId = border ? border.id : null
  previewBorderUrl.value = border ? border.image_url : null

  const res = await configStore.equipAvatarBorder(targetId)
  if (res.success) {
    activeBorderId.value = targetId
    toastStore.show({
      message: border ? `Equipped "${border.name}" border!` : 'Avatar border removed.',
      icon: 'success',
      placement: 'top',
    })
  } else {
    toastStore.show({
      message: 'Failed to equip avatar border.',
      icon: 'error',
      placement: 'top',
    })
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

const canRemoveAvatar = computed(
  () => Boolean(savedAvatarUrl.value) && Boolean(user.value?.attributes.avatar_is_custom),
)

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

  toastStore.show({
    message: 'Uploading profile photo...',
    icon: 'info',
    placement: 'top',
    duration: 2000,
  })

  const success = await configStore.uploadAvatar(file)
  ;(event.target as HTMLInputElement).value = ''
  if (success) {
    toastStore.show({
      message: 'Profile photo updated successfully!',
      icon: 'success',
      placement: 'top',
    })
  }
}

async function removeAvatar() {
  if (avatarUploading.value || saving.value) {
    return
  }

  const success = await configStore.removeAvatar()
  if (success) {
    toastStore.show({
      message: 'Profile photo removed.',
      icon: 'success',
      placement: 'top',
    })
  }
}

function openBannerPicker() {
  if (bannerUploading.value || saving.value) return
  bannerFileInput.value?.click()
}

async function onBannerSelected(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  bannerUploading.value = true
  toastStore.show({ message: 'Uploading profile banner...', icon: 'info', placement: 'top', duration: 2000 })

  const success = await configStore.uploadProfileBanner(file)
  ;(event.target as HTMLInputElement).value = ''
  bannerUploading.value = false

  if (success) {
    toastStore.show({ message: 'Profile banner updated!', icon: 'success', placement: 'top' })
  }
}

async function handleRemoveBanner() {
  if (bannerUploading.value || saving.value) return
  bannerUploading.value = true
  const success = await configStore.removeProfileBanner()
  bannerUploading.value = false
  if (success) {
    toastStore.show({ message: 'Profile banner removed.', icon: 'success', placement: 'top' })
  }
}

async function handleSave() {
  errors.value = {}
  saveSuccess.value = false
  saveSuccessMessage.value = null
  if (successTimer) {
    clearTimeout(successTimer)
    successTimer = null
  }

  // Client-side quick validation
  if (!name.value.trim()) {
    errors.value.name = [t('ui.settings.name_required', 'Name cannot be empty.')]
    toastStore.show({
      message: 'Name cannot be empty.',
      icon: 'error',
      placement: 'top',
    })
    return
  }

  if (isEmailDirty.value && !currentPassword.value) {
    errors.value.current_password = [
      t('ui.settings.password_required_for_email', 'Current password is required to change email address.'),
    ]
    toastStore.show({
      message: 'Current password is required to change email address.',
      icon: 'error',
      placement: 'top',
    })
    return
  }

  saving.value = true

  try {
    const result = await configStore.updateProfile({
      name: name.value.trim(),
      username: username.value.trim() || null,
      email: email.value.trim(),
      current_password: isEmailDirty.value ? currentPassword.value : undefined,
    })

    if (result.success) {
      currentPassword.value = ''
      errors.value = {}
      saveSuccess.value = true
      saveSuccessMessage.value = 'Profile updated successfully! All changes have been saved.'
      toastStore.show({
        message: 'Profile saved successfully!',
        icon: 'success',
        placement: 'top',
        duration: 3500,
      })
      successTimer = setTimeout(() => {
        saveSuccess.value = false
      }, 6000)
    } else if (result.errors) {
      errors.value = result.errors
      const firstError = Object.values(result.errors)[0]?.[0] || 'Failed to update profile.'
      toastStore.show({
        message: firstError,
        icon: 'error',
        placement: 'top',
      })
    }
  } catch (err: any) {
    const msg = err?.response?.data?.message || 'Failed to update profile.'
    errors.value = { general: [msg] }
    toastStore.show({
      message: msg,
      icon: 'error',
      placement: 'top',
    })
  } finally {
    saving.value = false
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

    <!-- ── Profile Banner Section ── -->
    <div class="chatify:space-y-2">
      <div class="chatify:flex chatify:items-center chatify:justify-between">
        <div class="chatify:flex chatify:items-center chatify:gap-2">
          <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">Profile Banner</label>
        </div>
        <span class="chatify:text-[10px] chatify:text-chatify-muted">
          {{ user?.attributes.profile_banner ? 'Custom banner set' : 'None' }}
        </span>
      </div>

      <!-- Banner Preview -->
      <div
        class="chatify:relative chatify:w-full chatify:overflow-hidden chatify:rounded-xl chatify:border chatify:border-white/10"
        style="height: 100px;"
      >
        <img
          v-if="user?.attributes.profile_banner"
          :src="user.attributes.profile_banner"
          alt="Profile banner"
          class="chatify:h-full chatify:w-full chatify:object-cover"
        />
        <div
          v-else
          class="chatify:flex chatify:h-full chatify:w-full chatify:items-center chatify:justify-center chatify:text-chatify-muted"
          style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);"
        >
          <svg class="chatify:h-6 chatify:w-6 chatify:opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span class="chatify:ml-2 chatify:text-xs chatify:opacity-50">No banner set</span>
        </div>

        <!-- Uploading overlay -->
        <div
          v-if="bannerUploading"
          class="chatify:absolute chatify:inset-0 chatify:flex chatify:items-center chatify:justify-center chatify:bg-black/60 chatify:backdrop-blur-sm"
        >
          <svg class="chatify:h-6 chatify:w-6 chatify:animate-spin chatify:text-white" fill="none" viewBox="0 0 24 24">
            <circle class="chatify:opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="chatify:opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
          </svg>
        </div>
      </div>

      <!-- Banner Action Buttons -->
      <div class="chatify:flex chatify:items-center chatify:gap-2">
        <button
          type="button"
          class="chatify:inline-flex chatify:items-center chatify:gap-1.5 chatify:rounded-lg chatify:border chatify:border-white/10 chatify:bg-white/[0.06] chatify:px-3 chatify:py-1.5 chatify:text-xs chatify:font-medium chatify:text-chatify-text chatify:transition hover:chatify:bg-white/[0.12] disabled:chatify:opacity-50"
          :disabled="bannerUploading || saving"
          @click="openBannerPicker"
        >
          <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          {{ user?.attributes.profile_banner ? 'Change Banner' : 'Upload Banner' }}
        </button>

        <button
          v-if="user?.attributes.profile_banner"
          type="button"
          class="chatify:inline-flex chatify:items-center chatify:gap-1.5 chatify:rounded-lg chatify:border chatify:border-red-500/20 chatify:bg-red-500/10 chatify:px-3 chatify:py-1.5 chatify:text-xs chatify:font-medium chatify:text-red-400 chatify:transition hover:chatify:bg-red-500/20 disabled:chatify:opacity-50"
          :disabled="bannerUploading || saving"
          @click="handleRemoveBanner"
        >
          <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          Remove Banner
        </button>

        <input
          ref="bannerFileInput"
          type="file"
          accept="image/png,image/jpeg,image/jpg,image/gif,image/webp"
          class="chatify:hidden"
          @change="onBannerSelected"
        />
      </div>

      <p class="chatify:text-[11px] chatify:text-chatify-muted">
        Recommended size: 1500 × 500px. PNG, JPG, GIF, or WebP.
      </p>
    </div>

    <!-- ══ ALL-IN-ONE PREVIEW ══
         Mirrors a conversation-list row exactly: nameplate as the row background,
         avatar with its animated border on the left, name + message preview on the
         right. One source of truth for "what will people see". -->
    <div class="chatify:space-y-2">
      <div class="chatify:flex chatify:items-center chatify:justify-between">
        <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
          Preview in chat list
        </label>
        <span class="chatify:text-[10px] chatify:text-chatify-muted">
          {{ combinedPreviewLabel }}
        </span>
      </div>

      <!-- The mock row. Same geometry as ConversationListItem. -->
      <div class="chatify:relative chatify:flex chatify:w-full chatify:items-center chatify:gap-3 chatify:overflow-hidden chatify:rounded-xl chatify:border chatify:border-white/10 chatify:bg-white/[0.04] chatify:px-4 chatify:py-3">
        <UserNameplate
          :nameplate="previewNameplate"
          mode="fill"
          fit="cover"
        />

        <div class="chatify:relative chatify:z-10 chatify:shrink-0">
          <UserAvatar
            :src="savedAvatarUrl"
            :alt="name || originalName"
            :border-url="previewBorderUrl
              ? previewBorderUrl
              : (availableBorders.find((b) => b.id === activeBorderId)?.image_url ?? null)"
            size="sm"
          />
        </div>

        <div class="chatify:relative chatify:z-10 chatify:min-w-0 chatify:flex-1">
          <div class="chatify:flex chatify:items-center chatify:justify-between chatify:gap-2">
            <p class="chatify:truncate chatify:text-sm chatify:font-medium chatify:text-chatify-text">
              {{ name || originalName }}
            </p>
            <span class="chatify:shrink-0 chatify:text-xs chatify:text-chatify-muted">now</span>
          </div>
          <p class="chatify:truncate chatify:text-xs chatify:text-chatify-muted">
            {{ combinedPreviewSubtitle }}
          </p>
        </div>
      </div>

      <p class="chatify:text-[11px] chatify:text-chatify-muted">
        This is exactly how your row appears in the conversation list, including the
        nameplate behind it and the border around your avatar.
      </p>
    </div>

    <!-- Animated Avatar Border Selector -->
    <div class="chatify:space-y-2">
      <div class="chatify:flex chatify:items-center chatify:justify-between">
        <div class="chatify:flex chatify:items-center chatify:gap-2">
          <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
            Animated Avatar Border
          </label>
        </div>
        <span class="chatify:text-[10px] chatify:text-chatify-muted">
          {{ activeBorderId ? (availableBorders.find((b) => b.id === activeBorderId)?.name || 'Equipped') : 'None' }}
        </span>
      </div>

      <!-- Border Selection Carousel -->
      <div v-if="availableBorders.length > 0" class="chatify:flex chatify:gap-3 chatify:overflow-x-auto chatify:py-1 chatify:scrollbar-thin">
        <!-- Option: None -->
        <button
          type="button"
          class="chatify:group chatify:relative chatify:flex chatify:h-24 chatify:w-24 chatify:shrink-0 chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:transition"
          :class="activeBorderId === null ? 'chatify:ring-2 chatify:ring-chatify-primary/60' : 'hover:chatify:opacity-80'"
          :disabled="equippingBorder"
          @click="selectBorder(null)"
        >
          <span class="chatify:text-sm chatify:font-medium chatify:text-chatify-muted">None</span>
        </button>

        <!-- Option: Each Unlocked Border -->
        <button
          v-for="border in availableBorders"
          :key="border.id"
          type="button"
          class="chatify:group chatify:relative chatify:flex chatify:h-24 chatify:w-24 chatify:shrink-0 chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:p-1 chatify:transition"
          :class="activeBorderId === border.id ? 'chatify:ring-2 chatify:ring-chatify-primary/60' : 'hover:chatify:opacity-80'"
          :disabled="equippingBorder"
          :title="border.name"
          @click="selectBorder(border)"
        >
          <div class="chatify:relative chatify:h-16 chatify:w-16">
            <div class="chatify:h-full chatify:w-full chatify:rounded-full chatify:bg-zinc-800" />
            <img
              :src="border.image_url"
              :alt="border.name"
              class="chatify-avatar-border chatify:pointer-events-none chatify:absolute chatify:inset-[-10%] chatify:h-[120%] chatify:w-[120%] chatify:max-w-none chatify:object-contain chatify:z-10"
            />
          </div>
          <span class="chatify:mt-1 chatify:w-full chatify:truncate chatify:text-center chatify:text-[11px] chatify:font-medium chatify:text-chatify-text">
            {{ border.name }}
          </span>
        </button>
      </div>

      <!-- Notice when node user has no borders gifted yet -->
      <div v-else class="chatify:py-2 chatify:text-center">
        <p class="chatify:text-xs chatify:text-chatify-muted">
          No animated borders unlocked yet. An administrator can gift exclusive animated borders to your profile.
        </p>
      </div>
    </div>

    <!-- Nameplate Selector (gift-only, mirrors Animated Avatar Border) -->
    <div class="chatify:space-y-2">
      <div class="chatify:flex chatify:items-center chatify:justify-between">
        <div class="chatify:flex chatify:items-center chatify:gap-2">
          <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
            Nameplate
          </label>
        </div>
        <span class="chatify:text-[10px] chatify:text-chatify-muted">
          {{ activeNameplateName || 'None' }}
        </span>
      </div>

      <!-- Equipped nameplate is shown in the all-in-one preview above. -->

      <!-- Nameplate Selection Carousel -->
      <div v-if="availableNameplates.length > 0" class="chatify:flex chatify:gap-3 chatify:overflow-x-auto chatify:py-1 chatify:scrollbar-thin">
        <!-- Option: None -->
        <button
          type="button"
          class="chatify:group chatify:relative chatify:flex chatify:h-24 chatify:w-24 chatify:shrink-0 chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:transition"
          :class="activeNameplateId === null ? 'chatify:ring-2 chatify:ring-chatify-primary/60' : 'hover:chatify:opacity-80'"
          :disabled="equippingNameplate"
          @click="selectNameplate(null)"
        >
          <span class="chatify:text-sm chatify:font-medium chatify:text-chatify-muted">None</span>
        </button>

        <!-- Option: Each Unlocked Nameplate -->
        <button
          v-for="plate in availableNameplates"
          :key="plate.id"
          type="button"
          class="chatify:group chatify:relative chatify:flex chatify:h-24 chatify:w-24 chatify:shrink-0 chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:p-1 chatify:transition"
          :class="activeNameplateId === plate.id ? 'chatify:ring-2 chatify:ring-chatify-primary/60' : 'hover:chatify:opacity-80'"
          :disabled="equippingNameplate"
          :title="plate.name"
          @click="selectNameplate(plate)"
        >
          <span class="chatify-nameplate-preview chatify:flex chatify:w-full chatify:items-center chatify:justify-center">
            <UserNameplate
              :nameplate="plate"
              :name="plate.name"
              mode="text"
              width="5.5rem"
            />
          </span>
          <span class="chatify:mt-1 chatify:w-full chatify:truncate chatify:text-center chatify:text-[11px] chatify:font-medium chatify:text-chatify-text">
            {{ plate.name }}
          </span>
        </button>
      </div>

      <!-- Notice when node user has no nameplates gifted yet -->
      <div
        v-else-if="loadingPickers"
        class="chatify:flex chatify:items-center chatify:justify-center chatify:py-3"
      >
        <svg class="chatify:h-4 chatify:w-4 chatify:animate-spin chatify:text-chatify-muted" fill="none" viewBox="0 0 24 24">
          <circle class="chatify:opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="chatify:opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
        </svg>
      </div>

      <div
        v-else-if="nameplatesError"
        class="chatify:flex chatify:flex-col chatify:items-center chatify:gap-2 chatify:rounded-xl chatify:border chatify:border-red-500/25 chatify:bg-red-500/[0.07] chatify:p-3 chatify:text-center"
      >
        <p class="chatify:text-xs chatify:text-red-300">{{ nameplatesError }}</p>
        <button
          type="button"
          class="chatify:rounded-lg chatify:border chatify:border-white/15 chatify:bg-white/[0.06] chatify:px-3 chatify:py-1 chatify:text-[11px] chatify:font-medium chatify:text-chatify-text chatify:transition hover:chatify:bg-white/[0.12]"
          @click="reloadNameplates"
        >
          Retry
        </button>
      </div>

      <div v-else class="chatify:py-2 chatify:text-center">
        <p class="chatify:text-xs chatify:text-chatify-muted">
          No nameplates available. An administrator can gift exclusive nameplates to your profile.
        </p>
      </div>
    </div>

    <!-- Profile Effect Selector (paired 450x880 APNG, gift-only like nameplates) -->
    <div class="chatify:space-y-2">
      <div class="chatify:flex chatify:items-center chatify:justify-between">
        <div class="chatify:flex chatify:items-center chatify:gap-2">
          <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
            Profile Effect
          </label>
        </div>
        <span class="chatify:text-[10px] chatify:text-chatify-muted">
          {{ activeProfileEffectName || 'None' }}
        </span>
      </div>

      <div v-if="availableProfileEffects.length > 0" class="chatify:flex chatify:gap-3 chatify:overflow-x-auto chatify:py-1 chatify:scrollbar-thin">
        <!-- Option: None -->
        <button
          type="button"
          class="chatify:group chatify:relative chatify:flex chatify:h-28 chatify:w-20 chatify:shrink-0 chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:transition"
          :class="activeProfileEffectId === null ? 'chatify:ring-2 chatify:ring-chatify-primary/60' : 'hover:chatify:opacity-80'"
          :disabled="equippingProfileEffect"
          @click="selectProfileEffect(null)"
        >
          <span class="chatify:text-sm chatify:font-medium chatify:text-chatify-muted">None</span>
        </button>

        <!-- Option: Each Unlocked Profile Effect -->
        <button
          v-for="effect in availableProfileEffects"
          :key="effect.id"
          type="button"
          class="chatify:group chatify:relative chatify:flex chatify:h-28 chatify:w-20 chatify:shrink-0 chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:p-1 chatify:transition"
          :class="activeProfileEffectId === effect.id ? 'chatify:ring-2 chatify:ring-chatify-primary/60' : 'hover:chatify:opacity-80'"
          :disabled="equippingProfileEffect"
          :title="effect.name"
          @click="selectProfileEffect(effect)"
        >
          <span class="chatify:block chatify:h-20 chatify:w-12 chatify:overflow-hidden chatify:rounded-lg chatify:bg-black/40">
            <img
              :src="effect.thumbnail_url"
              :alt="effect.name"
              class="chatify:h-full chatify:w-full chatify:object-contain"
              draggable="false"
            />
          </span>
          <span class="chatify:mt-1 chatify:w-full chatify:truncate chatify:text-center chatify:text-[11px] chatify:font-medium chatify:text-chatify-text">
            {{ effect.name }}
          </span>
        </button>
      </div>

      <!-- Notice when node user has no profile effects gifted yet -->
      <div
        v-else-if="loadingPickers"
        class="chatify:flex chatify:items-center chatify:justify-center chatify:py-3"
      >
        <svg class="chatify:h-4 chatify:w-4 chatify:animate-spin chatify:text-chatify-muted" fill="none" viewBox="0 0 24 24">
          <circle class="chatify:opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="chatify:opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
        </svg>
      </div>

      <div
        v-else-if="profileEffectsError"
        class="chatify:flex chatify:flex-col chatify:items-center chatify:gap-2 chatify:rounded-xl chatify:border chatify:border-red-500/25 chatify:bg-red-500/[0.07] chatify:p-3 chatify:text-center"
      >
        <p class="chatify:text-xs chatify:text-red-300">{{ profileEffectsError }}</p>
        <button
          type="button"
          class="chatify:rounded-lg chatify:border chatify:border-white/15 chatify:bg-white/[0.06] chatify:px-3 chatify:py-1 chatify:text-[11px] chatify:font-medium chatify:text-chatify-text chatify:transition hover:chatify:bg-white/[0.12]"
          @click="reloadProfileEffects"
        >
          Retry
        </button>
      </div>

      <div v-else class="chatify:py-2 chatify:text-center">
        <p class="chatify:text-xs chatify:text-chatify-muted">
          No profile effects available. An administrator can gift exclusive profile effects to your profile.
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

    <!-- Loading Process Notification Banner -->
    <div
      v-if="saving"
      class="chatify:flex chatify:items-center chatify:gap-3 chatify:rounded-xl chatify:border chatify:border-chatify-primary/40 chatify:bg-chatify-primary/10 chatify:p-3.5 chatify:text-xs chatify:text-chatify-text chatify:animate-pulse chatify:shadow-md"
    >
      <svg class="chatify:h-5 chatify:w-5 chatify:shrink-0 chatify:animate-spin chatify:text-chatify-primary" fill="none" viewBox="0 0 24 24">
        <circle class="chatify:opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="chatify:opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
      </svg>
      <div>
        <div class="chatify:font-semibold">Saving Profile Changes...</div>
        <div class="chatify:text-[10px] chatify:text-chatify-muted">Please wait while your profile is being updated.</div>
      </div>
    </div>

    <!-- Success Notification Popup / Banner -->
    <Transition name="chatify-fade">
      <div
        v-if="saveSuccess"
        class="chatify:flex chatify:items-center chatify:justify-between chatify:gap-2.5 chatify:rounded-xl chatify:border chatify:border-emerald-500/40 chatify:bg-emerald-500/15 chatify:p-3.5 chatify:text-xs chatify:text-emerald-300 chatify:shadow-lg"
      >
        <div class="chatify:flex chatify:items-center chatify:gap-2.5">
          <div class="chatify:flex chatify:h-7 chatify:w-7 chatify:shrink-0 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-emerald-500/20 chatify:text-emerald-400">
            <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div>
            <div class="chatify:font-bold chatify:text-emerald-200">Profile Saved</div>
            <div class="chatify:text-[11px] chatify:text-emerald-300/80">{{ saveSuccessMessage || 'All profile changes have been successfully saved.' }}</div>
          </div>
        </div>
        <button
          type="button"
          class="chatify:text-emerald-400/80 hover:chatify:text-emerald-200"
          @click="saveSuccess = false"
        >
          <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </Transition>

    <!-- Edit Profile Form -->
    <form class="chatify:space-y-3.5" @submit.prevent="handleSave">
      <!-- 1. Full Name -->
      <div class="chatify:space-y-1.5">
        <div class="chatify:flex chatify:items-center chatify:justify-between">
          <label class="chatify:text-xs chatify:font-medium chatify:text-chatify-text">
            {{ t('ui.settings.name', 'Full Name') }}
          </label>
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
          class="chatify:flex chatify:w-full chatify:items-center chatify:justify-center chatify:gap-2 chatify:rounded-xl chatify:bg-chatify-primary chatify:py-2.5 chatify:px-4 chatify:text-sm chatify:font-semibold chatify:text-black chatify:shadow-lg chatify:shadow-chatify-primary/20 chatify:transition hover:chatify:opacity-95 chatify:disabled:opacity-50 chatify:disabled:cursor-not-allowed"
          :disabled="saving"
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
          <svg
            v-else
            class="chatify:h-4 chatify:w-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>{{ saving ? t('ui.common.saving', 'Saving...') : 'Save Changes' }}</span>
        </button>
      </div>
    </form>
  </div>
</template>
