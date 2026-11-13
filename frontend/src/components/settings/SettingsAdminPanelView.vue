<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useConfigStore } from '../../stores/config'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import type { AvatarBorder, Nameplate, ProfileEffect } from '../../types'
import UserAvatar from '../ui/UserAvatar.vue'
import UserNameplate from '../ui/UserNameplate.vue'

const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { user } = storeToRefs(configStore)

const isAdmin = computed(() => (user.value?.attributes.role ?? 'node') === 'admin')

const activeTab = ref<'gift' | 'nameplates' | 'effects' | 'announcements'>('gift')

// Data
const borders = ref<AvatarBorder[]>([])
const loadingBorders = ref(false)
const selectedBorderId = ref<number | null>(null)

// Nameplates
const nameplates = ref<Nameplate[]>([])
const loadingNameplates = ref(false)
const selectedNameplateId = ref<number | null>(null)
const nameplateGiftQuery = ref('')
const nameplateGiftTitle = ref('')
const nameplateGiftDescription = ref('')
const giftingNameplate = ref(false)
const nameplateGiftSuccess = ref<string | null>(null)
const nameplateGiftError = ref<string | null>(null)
const nameplateUserSuggestions = ref<AdminUserSuggestion[]>([])
const newNameplateName = ref('')
const newNameplateFile = ref<File | null>(null)
const newNameplatePreview = ref<string | null>(null)
const uploadingNameplate = ref(false)
const nameplateUploadSuccess = ref<string | null>(null)
const nameplateUploadError = ref<string | null>(null)
const nameplateInputRef = ref<HTMLInputElement | null>(null)

// Profile Effects (paired 450x880 APNG: pair-1 entrance, pair-2 loop)
const profileEffects = ref<ProfileEffect[]>([])
const loadingProfileEffects = ref(false)
const selectedProfileEffectId = ref<number | null>(null)
const effectGiftQuery = ref('')
const effectGiftTitle = ref('')
const effectGiftDescription = ref('')
const giftingEffect = ref(false)
const effectGiftSuccess = ref<string | null>(null)
const effectGiftError = ref<string | null>(null)
const effectUserSuggestions = ref<AdminUserSuggestion[]>([])
const newEffectName = ref('')
const newEffectPair1 = ref<File | null>(null)
const newEffectPair2 = ref<File | null>(null)
const newEffectPair1Preview = ref<string | null>(null)
const newEffectPair2Preview = ref<string | null>(null)
const uploadingEffect = ref(false)
const effectUploadSuccess = ref<string | null>(null)
const effectUploadError = ref<string | null>(null)
const effectPair1InputRef = ref<HTMLInputElement | null>(null)
const effectPair2InputRef = ref<HTMLInputElement | null>(null)

// Gift Form
type AdminUserSuggestion = {
  id: number
  name: string
  username: string | null
  email: string
  role?: string
  borders_count: number
}

const userQuery = ref('')
const giftTitle = ref('')
const giftDescription = ref('')
const gifting = ref(false)
const giftSuccess = ref<string | null>(null)
const giftError = ref<string | null>(null)
const userSuggestions = ref<AdminUserSuggestion[]>([])
const searchingUsers = ref(false)

// Upload Form
const newBorderName = ref('')
const newBorderFile = ref<File | null>(null)
const newBorderPreview = ref<string | null>(null)
const uploadingBorder = ref(false)
const uploadSuccess = ref<string | null>(null)
const uploadError = ref<string | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)

// Announcement Form
const announcementTitle = ref('')
const announcementBody = ref('')
const announcementFile = ref<File | null>(null)
const announcementPreview = ref<string | null>(null)
const broadcasting = ref(false)
const broadcastSuccess = ref<string | null>(null)
const broadcastError = ref<string | null>(null)
const announcementInputRef = ref<HTMLInputElement | null>(null)

const selectedBorder = computed(() => borders.value.find((b) => b.id === selectedBorderId.value) ?? null)

onMounted(async () => {
  if (isAdmin.value) {
    await fetchBorders()
    await fetchNameplates()
    await fetchProfileEffects()
  }
})

async function fetchNameplates() {
  if (!configStore.api) return
  loadingNameplates.value = true
  try {
    const res = await configStore.api.getAdminNameplates()
    nameplates.value = res.data.data
    if (nameplates.value.length > 0 && selectedNameplateId.value === null) {
      selectedNameplateId.value = nameplates.value[0].id
    }
  } catch (err: any) {
    console.error('Failed to load admin nameplates', err)
  } finally {
    loadingNameplates.value = false
  }
}

async function handleGiftNameplate() {
  nameplateGiftError.value = null
  nameplateGiftSuccess.value = null

  if (!selectedNameplateId.value) {
    nameplateGiftError.value = 'Please select a nameplate to gift.'
    return
  }

  const cleanQuery = nameplateGiftQuery.value.trim().replace(/^@/, '')
  if (!cleanQuery) {
    nameplateGiftError.value = 'Please enter a target username or email.'
    return
  }

  giftingNameplate.value = true
  try {
    const res = await configStore.api!.giftNameplate({
      nameplate_id: selectedNameplateId.value,
      user_query: cleanQuery,
      gift_title: nameplateGiftTitle.value.trim() || undefined,
      gift_description: nameplateGiftDescription.value.trim() || undefined,
    })
    nameplateGiftSuccess.value = res.data.message
    nameplateGiftQuery.value = ''
    nameplateGiftTitle.value = ''
    nameplateGiftDescription.value = ''
    nameplateUserSuggestions.value = []
    await fetchNameplates()
  } catch (err: any) {
    nameplateGiftError.value = err?.response?.data?.message || 'Failed to gift nameplate.'
  } finally {
    giftingNameplate.value = false
  }
}

function onNameplateFileSelected(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  newNameplateFile.value = file
  if (newNameplatePreview.value) {
    URL.revokeObjectURL(newNameplatePreview.value)
  }
  newNameplatePreview.value = URL.createObjectURL(file)
}

async function handleUploadNameplate() {
  nameplateUploadError.value = null
  nameplateUploadSuccess.value = null

  if (!newNameplateName.value.trim()) {
    nameplateUploadError.value = 'Please provide a title for the nameplate.'
    return
  }

  if (!newNameplateFile.value) {
    nameplateUploadError.value = 'Please select a nameplate image (448x84 / 16:3).'
    return
  }

  uploadingNameplate.value = true
  try {
    const form = new FormData()
    form.append('name', newNameplateName.value.trim())
    form.append('nameplate', newNameplateFile.value)

    const res = await configStore.api!.uploadAdminNameplate(form)
    nameplateUploadSuccess.value = `Nameplate "${res.data.data.name}" added successfully!`
    newNameplateName.value = ''
    newNameplateFile.value = null
    if (newNameplatePreview.value) {
      URL.revokeObjectURL(newNameplatePreview.value)
      newNameplatePreview.value = null
    }
    if (nameplateInputRef.value) nameplateInputRef.value.value = ''

    await fetchNameplates()
    selectedNameplateId.value = res.data.data.id
  } catch (err: any) {
    nameplateUploadError.value = err?.response?.data?.message || 'Failed to upload nameplate.'
  } finally {
    uploadingNameplate.value = false
  }
}

async function deleteNameplate(id: number) {
  if (!configStore.api) return
  if (!confirm('Are you sure you want to delete this custom nameplate?')) return

  try {
    await configStore.api.deleteAdminNameplate(id)
    await fetchNameplates()
    if (selectedNameplateId.value === id) {
      selectedNameplateId.value = nameplates.value[0]?.id ?? null
    }
  } catch (err: any) {
    alert(err?.response?.data?.message || 'Failed to delete nameplate.')
  }
}

async function fetchProfileEffects() {
  if (!configStore.api) return
  loadingProfileEffects.value = true
  try {
    const res = await configStore.api.getAdminProfileEffects()
    profileEffects.value = res.data.data
    if (profileEffects.value.length > 0 && selectedProfileEffectId.value === null) {
      selectedProfileEffectId.value = profileEffects.value[0].id
    }
  } catch (err: any) {
    console.error('Failed to load admin profile effects', err)
  } finally {
    loadingProfileEffects.value = false
  }
}

async function handleGiftProfileEffect() {
  effectGiftError.value = null
  effectGiftSuccess.value = null

  if (!selectedProfileEffectId.value) {
    effectGiftError.value = 'Please select a profile effect to gift.'
    return
  }

  const cleanQuery = effectGiftQuery.value.trim().replace(/^@/, '')
  if (!cleanQuery) {
    effectGiftError.value = 'Please enter a target username or email.'
    return
  }

  giftingEffect.value = true
  try {
    const res = await configStore.api!.giftProfileEffect({
      profile_effect_id: selectedProfileEffectId.value,
      user_query: cleanQuery,
      gift_title: effectGiftTitle.value.trim() || undefined,
      gift_description: effectGiftDescription.value.trim() || undefined,
    })
    effectGiftSuccess.value = res.data.message
    effectGiftQuery.value = ''
    effectGiftTitle.value = ''
    effectGiftDescription.value = ''
    effectUserSuggestions.value = []
    await fetchProfileEffects()
  } catch (err: any) {
    effectGiftError.value = err?.response?.data?.message || 'Failed to gift profile effect.'
  } finally {
    giftingEffect.value = false
  }
}

function onEffectPairSelected(kind: 'pair1' | 'pair2', event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  const preview = kind === 'pair1' ? newEffectPair1Preview : newEffectPair2Preview
  if (preview.value) {
    URL.revokeObjectURL(preview.value)
  }
  preview.value = URL.createObjectURL(file)

  if (kind === 'pair1') newEffectPair1.value = file
  else newEffectPair2.value = file
}

async function handleUploadProfileEffect() {
  effectUploadError.value = null
  effectUploadSuccess.value = null

  if (!newEffectName.value.trim()) {
    effectUploadError.value = 'Please provide a title for the profile effect.'
    return
  }

  if (!newEffectPair1.value || !newEffectPair2.value) {
    effectUploadError.value = 'Please select both pair-1 (entrance) and pair-2 (loop) images.'
    return
  }

  uploadingEffect.value = true
  try {
    const form = new FormData()
    form.append('name', newEffectName.value.trim())
    form.append('pair1', newEffectPair1.value)
    form.append('pair2', newEffectPair2.value)

    const res = await configStore.api!.uploadAdminProfileEffect(form)
    effectUploadSuccess.value = `Profile effect "${res.data.data.name}" added successfully!`
    newEffectName.value = ''
    newEffectPair1.value = null
    newEffectPair2.value = null
    if (newEffectPair1Preview.value) {
      URL.revokeObjectURL(newEffectPair1Preview.value)
      newEffectPair1Preview.value = null
    }
    if (newEffectPair2Preview.value) {
      URL.revokeObjectURL(newEffectPair2Preview.value)
      newEffectPair2Preview.value = null
    }
    if (effectPair1InputRef.value) effectPair1InputRef.value.value = ''
    if (effectPair2InputRef.value) effectPair2InputRef.value.value = ''

    await fetchProfileEffects()
    selectedProfileEffectId.value = res.data.data.id
  } catch (err: any) {
    effectUploadError.value = err?.response?.data?.message || 'Failed to upload profile effect.'
  } finally {
    uploadingEffect.value = false
  }
}

async function deleteProfileEffect(id: number) {
  if (!configStore.api) return
  if (!confirm('Are you sure you want to delete this custom profile effect?')) return

  try {
    await configStore.api.deleteAdminProfileEffect(id)
    await fetchProfileEffects()
    if (selectedProfileEffectId.value === id) {
      selectedProfileEffectId.value = profileEffects.value[0]?.id ?? null
    }
  } catch (err: any) {
    alert(err?.response?.data?.message || 'Failed to delete profile effect.')
  }
}

async function fetchBorders() {
  if (!configStore.api) return
  loadingBorders.value = true
  try {
    const res = await configStore.api.getAdminBorders()
    borders.value = res.data.data
    if (borders.value.length > 0 && selectedBorderId.value === null) {
      selectedBorderId.value = borders.value[0].id
    }
  } catch (err: any) {
    console.error('Failed to load admin borders', err)
  } finally {
    loadingBorders.value = false
  }
}

async function searchUsers(q: string) {
  if (!configStore.api) return
  searchingUsers.value = true
  try {
    const res = await configStore.api.searchAdminUsers(q)
    userSuggestions.value = res.data.data
  } catch (err) {
    console.error('Failed to search users', err)
  } finally {
    searchingUsers.value = false
  }
}

function onUserQueryInput() {
  giftError.value = null
  giftSuccess.value = null
  const q = userQuery.value.trim()
  if (!q) {
    userSuggestions.value = []
    return
  }
  searchUsers(q)
}

function selectUserSuggestion(u: AdminUserSuggestion) {
  userQuery.value = u.username ? `@${u.username}` : u.email
  userSuggestions.value = []
}

async function searchNameplateUsers(q: string) {
  if (!configStore.api) return
  try {
    const res = await configStore.api.searchAdminUsers(q)
    nameplateUserSuggestions.value = q.trim() ? res.data.data : []
  } catch (err) {
    console.error('Failed to search users', err)
  }
}

function onNameplateQueryInput() {
  nameplateGiftError.value = null
  nameplateGiftSuccess.value = null
  const q = nameplateGiftQuery.value.trim()
  if (!q) {
    nameplateUserSuggestions.value = []
    return
  }
  searchNameplateUsers(q)
}

function selectNameplateUserSuggestion(u: AdminUserSuggestion) {
  nameplateGiftQuery.value = u.username ? `@${u.username}` : u.email
  nameplateUserSuggestions.value = []
}

async function searchEffectUsers(q: string) {
  if (!configStore.api) return
  try {
    const res = await configStore.api.searchAdminUsers(q)
    effectUserSuggestions.value = q.trim() ? res.data.data : []
  } catch (err) {
    console.error('Failed to search users', err)
  }
}

function onEffectQueryInput() {
  effectGiftError.value = null
  effectGiftSuccess.value = null
  const q = effectGiftQuery.value.trim()
  if (!q) {
    effectUserSuggestions.value = []
    return
  }
  searchEffectUsers(q)
}

function selectEffectUserSuggestion(u: AdminUserSuggestion) {
  effectGiftQuery.value = u.username ? `@${u.username}` : u.email
  effectUserSuggestions.value = []
}

async function handleGift() {
  giftError.value = null
  giftSuccess.value = null

  if (!selectedBorderId.value) {
    giftError.value = 'Please select a border to gift.'
    return
  }

  const cleanQuery = userQuery.value.trim().replace(/^@/, '')
  if (!cleanQuery) {
    giftError.value = 'Please enter a target username or email.'
    return
  }

  gifting.value = true
  try {
    const res = await configStore.api!.giftBorder({
      border_id: selectedBorderId.value,
      user_query: cleanQuery,
      gift_title: giftTitle.value.trim() || undefined,
      gift_description: giftDescription.value.trim() || undefined,
    })
    giftSuccess.value = res.data.message
    userQuery.value = ''
    giftTitle.value = ''
    giftDescription.value = ''
    userSuggestions.value = []
    await fetchBorders()
  } catch (err: any) {
    giftError.value = err?.response?.data?.message || 'Failed to gift border.'
  } finally {
    gifting.value = false
  }
}

function onAnnouncementFileSelected(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  announcementFile.value = file
  if (announcementPreview.value) {
    URL.revokeObjectURL(announcementPreview.value)
  }
  announcementPreview.value = URL.createObjectURL(file)
}

async function handleBroadcast() {
  broadcastError.value = null
  broadcastSuccess.value = null

  if (!announcementTitle.value.trim()) {
    broadcastError.value = 'Please provide an announcement title.'
    return
  }

  if (!announcementBody.value.trim()) {
    broadcastError.value = 'Please provide an announcement message body.'
    return
  }

  broadcasting.value = true
  try {
    const form = new FormData()
    form.append('title', announcementTitle.value.trim())
    form.append('body', announcementBody.value.trim())
    if (announcementFile.value) {
      form.append('image', announcementFile.value)
    }

    const res = await configStore.api!.broadcastAnnouncement(form)
    broadcastSuccess.value = res.data.message
    announcementTitle.value = ''
    announcementBody.value = ''
    announcementFile.value = null
    if (announcementPreview.value) {
      URL.revokeObjectURL(announcementPreview.value)
      announcementPreview.value = null
    }
    if (announcementInputRef.value) announcementInputRef.value.value = ''
  } catch (err: any) {
    broadcastError.value = err?.response?.data?.message || 'Failed to broadcast announcement.'
  } finally {
    broadcasting.value = false
  }
}

function onFileSelected(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  newBorderFile.value = file
  if (newBorderPreview.value) {
    URL.revokeObjectURL(newBorderPreview.value)
  }
  newBorderPreview.value = URL.createObjectURL(file)
}

async function handleUploadBorder() {
  uploadError.value = null
  uploadSuccess.value = null

  if (!newBorderName.value.trim()) {
    uploadError.value = 'Please provide a title for the border.'
    return
  }

  if (!newBorderFile.value) {
    uploadError.value = 'Please select an animated PNG or APNG image.'
    return
  }

  uploadingBorder.value = true
  try {
    const form = new FormData()
    form.append('name', newBorderName.value.trim())
    form.append('border', newBorderFile.value)

    const res = await configStore.api!.uploadAdminBorder(form)
    uploadSuccess.value = `Border "${res.data.data.name}" added successfully!`
    newBorderName.value = ''
    newBorderFile.value = null
    if (newBorderPreview.value) {
      URL.revokeObjectURL(newBorderPreview.value)
      newBorderPreview.value = null
    }
    if (fileInputRef.value) fileInputRef.value.value = ''

    await fetchBorders()
    selectedBorderId.value = res.data.data.id
  } catch (err: any) {
    uploadError.value = err?.response?.data?.message || 'Failed to upload border.'
  } finally {
    uploadingBorder.value = false
  }
}

async function deleteBorder(id: number) {
  if (!configStore.api) return
  if (!confirm('Are you sure you want to delete this custom border?')) return

  try {
    await configStore.api.deleteAdminBorder(id)
    await fetchBorders()
    if (selectedBorderId.value === id) {
      selectedBorderId.value = borders.value[0]?.id ?? null
    }
  } catch (err: any) {
    alert(err?.response?.data?.message || 'Failed to delete border.')
  }
}
</script>

<template>
  <div class="chatify-settings-admin-panel chatify:space-y-4 chatify:p-1">
    <!-- Non-admin Security Lock Banner -->
    <div
      v-if="!isAdmin"
      class="chatify:flex chatify:flex-col chatify:items-center chatify:rounded-2xl chatify:border chatify:border-red-500/30 chatify:bg-red-500/10 chatify:p-6 chatify:text-center"
    >
      <svg class="chatify:h-12 chatify:w-12 chatify:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
      </svg>
      <h4 class="chatify:mt-3 chatify:text-base chatify:font-bold chatify:text-red-300">Access Restricted</h4>
      <p class="chatify:mt-1 chatify:text-xs chatify:text-red-300/80">
        You are currently on a Node role. Admin Panel access is strictly reserved for administrators.
      </p>
    </div>

    <div v-else class="chatify:space-y-4">
      <div class="chatify:flex chatify:items-center chatify:justify-between chatify:border-b chatify:border-white/10 chatify:pb-2">
        <h3 class="chatify:text-sm chatify:font-semibold chatify:text-white">Administrator Control Panel</h3>
      </div>

      <!-- Navigation Tabs -->
      <div class="chatify:flex chatify:rounded-xl chatify:border chatify:border-white/10 chatify:bg-[#121214] chatify:p-1">
        <button
          type="button"
          class="chatify:inline-flex chatify:flex-1 chatify:items-center chatify:justify-center chatify:gap-1.5 chatify:rounded-lg chatify:py-1.5 chatify:text-center chatify:text-xs chatify:font-medium chatify:transition"
          :class="activeTab === 'gift' ? 'chatify:bg-zinc-800 chatify:text-white chatify:shadow-sm' : 'chatify:text-zinc-400 hover:chatify:text-zinc-200'"
          @click="activeTab = 'gift'"
        >
          <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V4a2 2 0 10-4 0v4H3v4h18V8h-5V4a2 2 0 10-4 0v4zm0 13H4a1 1 0 01-1-1v-8h9v9zm0 0h8a1 1 0 001-1v-8h-9v9z" />
          </svg>
          <span>Gift Border</span>
        </button>
        <button
          type="button"
          class="chatify:inline-flex chatify:flex-1 chatify:items-center chatify:justify-center chatify:gap-1.5 chatify:rounded-lg chatify:py-1.5 chatify:text-center chatify:text-xs chatify:font-medium chatify:transition"
          :class="activeTab === 'nameplates' ? 'chatify:bg-zinc-800 chatify:text-white chatify:shadow-sm' : 'chatify:text-zinc-400 hover:chatify:text-zinc-200'"
          @click="activeTab = 'nameplates'"
        >
          <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 12h10M7 17h6M4 4h16v16H4z" />
          </svg>
          <span>Nameplates ({{ nameplates.length }})</span>
        </button>
        <button
          type="button"
          class="chatify:inline-flex chatify:flex-1 chatify:items-center chatify:justify-center chatify:gap-1.5 chatify:rounded-lg chatify:py-1.5 chatify:text-center chatify:text-xs chatify:font-medium chatify:transition"
          :class="activeTab === 'effects' ? 'chatify:bg-zinc-800 chatify:text-white chatify:shadow-sm' : 'chatify:text-zinc-400 hover:chatify:text-zinc-200'"
          @click="activeTab = 'effects'"
        >
          <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
          </svg>
          <span>Effects ({{ profileEffects.length }})</span>
        </button>
        <button
          type="button"
          class="chatify:inline-flex chatify:flex-1 chatify:items-center chatify:justify-center chatify:gap-1.5 chatify:rounded-lg chatify:py-1.5 chatify:text-center chatify:text-xs chatify:font-medium chatify:transition"
          :class="activeTab === 'announcements' ? 'chatify:bg-zinc-800 chatify:text-white chatify:shadow-sm' : 'chatify:text-zinc-400 hover:chatify:text-zinc-200'"
          @click="activeTab = 'announcements'"
        >
          <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
          </svg>
          <span>Announce</span>
        </button>
      </div>

      <!-- TAB 1: GIFT BORDER -->
      <div v-if="activeTab === 'gift'" class="chatify:space-y-4">
        <!-- 1. Border Selection -->
        <div class="chatify:space-y-1.5">
          <div class="chatify:flex chatify:items-center chatify:justify-between">
            <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
              1. Select Animated Border to Gift
            </label>
            <span class="chatify:text-[11px] chatify:text-chatify-muted">
              {{ selectedBorder ? selectedBorder.name : 'None selected' }}
            </span>
          </div>

          <!-- Border Horizontal Picker -->
          <div class="chatify:flex chatify:gap-2 chatify:overflow-x-auto chatify:py-2 chatify:scrollbar-thin">
            <button
              v-for="border in borders"
              :key="border.id"
              type="button"
              class="chatify:group chatify:relative chatify:flex chatify:h-20 chatify:w-20 chatify:shrink-0 chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:border chatify:p-1.5 chatify:transition"
              :class="selectedBorderId === border.id ? 'chatify:border-chatify-primary chatify:bg-chatify-primary/10 chatify:shadow-lg' : 'chatify:border-white/10 chatify:bg-white/[0.04] hover:chatify:bg-white/[0.08]'"
              @click="selectedBorderId = border.id"
            >
              <!-- Delete Custom Border -->
              <span
                v-if="!border.is_system"
                class="chatify:absolute chatify:top-1 chatify:right-1 chatify:z-20 chatify:flex chatify:h-4 chatify:w-4 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-red-500/80 chatify:text-white chatify:opacity-80 hover:chatify:opacity-100"
                title="Delete custom border"
                @click.stop="deleteBorder(border.id)"
              >
                <svg class="chatify:h-2.5 chatify:w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </span>

              <div class="chatify:relative chatify:h-12 chatify:w-12">
                <div class="chatify:h-full chatify:w-full chatify:rounded-full chatify:bg-zinc-800" />
                <img
                  :src="border.image_url"
                  :alt="border.name"
                  class="chatify-avatar-border chatify:pointer-events-none chatify:absolute chatify:inset-[-10%] chatify:h-[120%] chatify:w-[120%] chatify:max-w-none chatify:object-contain chatify:z-10"
                />
              </div>
              <span class="chatify:mt-1 chatify:w-full chatify:truncate chatify:text-center chatify:text-[10px] chatify:font-medium chatify:text-chatify-text">
                {{ border.name }}
              </span>
            </button>
          </div>
        </div>

        <!-- 2. Target User Input -->
        <div class="chatify:space-y-2">
          <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
            2. Enter Target User (Username or Email)
          </label>
          <div class="chatify:relative">
            <div class="chatify:pointer-events-none chatify:absolute chatify:inset-y-0 chatify:left-0 chatify:flex chatify:items-center chatify:pl-3 chatify:text-chatify-muted">
              <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <input
              v-model="userQuery"
              type="text"
              placeholder="e.g. example or example@gmail.com"
              class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2.5 chatify:pl-9 chatify:pr-3 chatify:text-sm chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
              @input="onUserQueryInput"
            />
          </div>

          <!-- User quick select suggestions (only while searching) -->
          <div v-if="userQuery.trim() && userSuggestions.length > 0" class="chatify:space-y-1 chatify:pt-1">
            <span class="chatify:text-[10px] chatify:text-chatify-muted chatify:uppercase">Matching Users:</span>
            <div class="chatify:flex chatify:flex-wrap chatify:gap-1.5">
              <button
                v-for="u in userSuggestions"
                :key="u.id"
                type="button"
                class="chatify:inline-flex chatify:items-center chatify:gap-1.5 chatify:rounded-lg chatify:border chatify:border-white/10 chatify:bg-white/[0.04] chatify:px-2 chatify:py-1 chatify:text-[11px] chatify:text-chatify-text chatify:transition hover:chatify:bg-chatify-primary/20 hover:chatify:border-chatify-primary/40"
                @click="selectUserSuggestion(u)"
              >
                <span class="chatify:font-medium">{{ u.name }}</span>
                <span class="chatify:text-[10px] chatify:text-chatify-muted">
                  {{ u.username ? `@${u.username}` : u.email }}
                </span>
                <span v-if="u.role === 'admin'" class="chatify:rounded chatify:bg-amber-400/20 chatify:px-1 chatify:text-[9px] chatify:font-semibold chatify:text-amber-300">Admin</span>
              </button>
            </div>
          </div>

          <!-- Optional Gift Notification Details -->
          <div class="chatify:grid chatify:grid-cols-1 chatify:gap-2 chatify:pt-1">
            <div class="chatify:space-y-1">
              <label class="chatify:text-[11px] chatify:font-medium chatify:text-chatify-muted">
                Gift Notification Title (Optional)
              </label>
              <input
                v-model="giftTitle"
                type="text"
                placeholder="e.g. Special Border Award"
                class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2 chatify:px-3 chatify:text-xs chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
              />
            </div>
            <div class="chatify:space-y-1">
              <label class="chatify:text-[11px] chatify:font-medium chatify:text-chatify-muted">
                Gift Message / Description (Optional)
              </label>
              <textarea
                v-model="giftDescription"
                rows="2"
                placeholder="e.g. You have been awarded this exclusive animated border by the administrator."
                class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2 chatify:px-3 chatify:text-xs chatify:text-chatify-text chatify:transition focus:chatify:outline-none chatify:resize-none"
              />
            </div>
          </div>
        </div>

        <!-- Feedback Banners -->
        <div v-if="giftSuccess" class="chatify:flex chatify:items-center chatify:gap-2 chatify:rounded-xl chatify:border chatify:border-emerald-500/30 chatify:bg-emerald-500/10 chatify:p-3 chatify:text-xs chatify:text-emerald-300">
          <svg class="chatify:h-4 chatify:w-4 chatify:shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>{{ giftSuccess }}</span>
        </div>

        <div v-if="giftError" class="chatify:flex chatify:items-center chatify:gap-2 chatify:rounded-xl chatify:border chatify:border-red-500/30 chatify:bg-red-500/10 chatify:p-3 chatify:text-xs chatify:text-red-400">
          <svg class="chatify:h-4 chatify:w-4 chatify:shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ giftError }}</span>
        </div>

        <!-- Submit Button -->
        <button
          type="button"
          class="chatify:flex chatify:w-full chatify:items-center chatify:justify-center chatify:gap-2 chatify:rounded-xl chatify:bg-gradient-to-r chatify:from-amber-500 chatify:to-orange-500 chatify:py-2.5 chatify:px-4 chatify:text-sm chatify:font-semibold chatify:text-black chatify:shadow-lg chatify:transition hover:chatify:opacity-95 disabled:chatify:opacity-50"
          :disabled="gifting || !selectedBorderId || !userQuery.trim()"
          @click="handleGift"
        >
          <span v-if="gifting">Gifting Border...</span>
          <template v-else>
            <svg class="chatify:h-4 chatify:w-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V4a2 2 0 10-4 0v4H3v4h18V8h-5V4a2 2 0 10-4 0v4zm0 13H4a1 1 0 01-1-1v-8h9v9zm0 0h8a1 1 0 001-1v-8h-9v9z" />
            </svg>
            <span>Gift Animated Border to User</span>
          </template>
        </button>
        <!-- Section: Add / Upload New Border -->
        <div class="chatify:space-y-3 chatify:border-t chatify:border-white/10 chatify:pt-4">
          <div class="chatify:flex chatify:items-center chatify:justify-between">
            <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
              Add New Animated Border
            </label>
            <span class="chatify:text-[11px] chatify:text-chatify-muted">APNG / PNG (1:1)</span>
          </div>

          <div class="chatify:space-y-1.5">
            <label class="chatify:text-[11px] chatify:font-medium chatify:text-chatify-muted">
              Border Title / Name
            </label>
            <input
              v-model="newBorderName"
              type="text"
              maxlength="50"
              placeholder="e.g. Cyber Neon Aura"
              class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2.5 chatify:px-3 chatify:text-sm chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
            />
          </div>

          <div class="chatify:space-y-1.5">
            <label class="chatify:text-[11px] chatify:font-medium chatify:text-chatify-muted">
              Animated Image File (288x288 / 1:1 APNG or PNG)
            </label>
            <div
              class="chatify:flex chatify:cursor-pointer chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-2xl chatify:border-2 chatify:border-dashed chatify:border-white/15 chatify:p-5 chatify:transition hover:chatify:border-chatify-primary/50 hover:chatify:bg-white/[0.02]"
              @click="fileInputRef?.click()"
            >
              <!-- Live Preview if file chosen -->
              <div v-if="newBorderPreview" class="chatify:relative chatify:h-20 chatify:w-20">
                <div class="chatify:h-full chatify:w-full chatify:rounded-full chatify:bg-zinc-800" />
                <img
                  :src="newBorderPreview"
                  alt="Preview"
                  class="chatify-avatar-border chatify:pointer-events-none chatify:absolute chatify:inset-[-10%] chatify:h-[120%] chatify:w-[120%] chatify:max-w-none chatify:object-contain chatify:z-10"
                />
              </div>
              <div v-else class="chatify:flex chatify:flex-col chatify:items-center">
                <svg class="chatify:h-7 chatify:w-7 chatify:text-chatify-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="chatify:mt-2 chatify:text-xs chatify:font-medium chatify:text-chatify-text">
                  Click to browse APNG or PNG border
                </p>
                <p class="chatify:text-[10px] chatify:text-chatify-muted">Square 1:1 aspect ratio (recommended 288x288)</p>
              </div>

              <input
                ref="fileInputRef"
                type="file"
                accept="image/png,image/apng,image/webp"
                class="chatify:hidden"
                @change="onFileSelected"
              />
            </div>
          </div>

          <!-- Feedback Banners -->
          <div v-if="uploadSuccess" class="chatify:flex chatify:items-center chatify:gap-2 chatify:rounded-xl chatify:border chatify:border-emerald-500/30 chatify:bg-emerald-500/10 chatify:p-3 chatify:text-xs chatify:text-emerald-300">
            <svg class="chatify:h-4 chatify:w-4 chatify:shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ uploadSuccess }}</span>
          </div>

          <div v-if="uploadError" class="chatify:flex chatify:items-center chatify:gap-2 chatify:rounded-xl chatify:border chatify:border-red-500/30 chatify:bg-red-500/10 chatify:p-3 chatify:text-xs chatify:text-red-400">
            <svg class="chatify:h-4 chatify:w-4 chatify:shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ uploadError }}</span>
          </div>

          <button
            type="button"
            class="chatify:flex chatify:w-full chatify:items-center chatify:justify-center chatify:gap-2 chatify:rounded-xl chatify:bg-chatify-primary chatify:py-2.5 chatify:px-4 chatify:text-sm chatify:font-semibold chatify:text-white chatify:shadow-lg chatify:transition hover:chatify:opacity-95 disabled:chatify:opacity-50"
            :disabled="uploadingBorder || !newBorderName.trim() || !newBorderFile"
            @click="handleUploadBorder"
          >
            <span v-if="uploadingBorder">Uploading Border...</span>
            <span v-else>Upload & Save Animated Border</span>
          </button>
        </div>
      </div>

      <!-- TAB 4: NAMEPLATES (gift / upload / catalog) -->
      <div v-if="activeTab === 'nameplates'" class="chatify:space-y-4">
        <!-- Section: Gift -->
        <div class="chatify:space-y-2">
          <div class="chatify:flex chatify:items-center chatify:justify-between">
            <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
              1. Select Nameplate to Gift
            </label>
            <span class="chatify:text-[11px] chatify:text-chatify-muted">
              {{ selectedNameplateId ? (nameplates.find((n) => n.id === selectedNameplateId)?.name || 'Selected') : 'None selected' }}
            </span>
          </div>

          <div class="chatify:flex chatify:gap-2 chatify:overflow-x-auto chatify:py-2 chatify:scrollbar-thin">
            <button
              v-for="plate in nameplates"
              :key="plate.id"
              type="button"
              class="chatify:group chatify:relative chatify:flex chatify:h-20 chatify:w-28 chatify:shrink-0 chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:border chatify:p-1.5 chatify:transition"
              :class="selectedNameplateId === plate.id ? 'chatify:border-chatify-primary chatify:bg-chatify-primary/10 chatify:shadow-lg' : 'chatify:border-white/10 chatify:bg-white/[0.04] hover:chatify:bg-white/[0.08]'"
              @click="selectedNameplateId = plate.id"
            >
              <span class="chatify-nameplate-preview chatify:flex chatify:w-full chatify:items-center chatify:justify-center">
                <UserNameplate
                  :nameplate="plate"
                  :name="plate.name"
                  mode="text"
                  width="6rem"
                />
              </span>
              <span class="chatify:mt-1 chatify:w-full chatify:truncate chatify:text-center chatify:text-[10px] chatify:font-medium chatify:text-chatify-text">
                {{ plate.name }}
              </span>
            </button>
          </div>

          <input
            v-model="nameplateGiftQuery"
            type="text"
            placeholder="Target username or email (e.g. @node1)"
            class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2.5 chatify:px-3 chatify:text-sm chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
            @input="onNameplateQueryInput"
          />

          <!-- Nameplate user quick select suggestions (only while searching) -->
          <div
            v-if="nameplateGiftQuery.trim() && nameplateUserSuggestions.length > 0"
            class="chatify:space-y-1 chatify:pt-1"
          >
            <span class="chatify:text-[10px] chatify:text-chatify-muted chatify:uppercase">Matching Users:</span>
            <div class="chatify:flex chatify:flex-wrap chatify:gap-1.5">
              <button
                v-for="u in nameplateUserSuggestions"
                :key="u.id"
                type="button"
                class="chatify:inline-flex chatify:items-center chatify:gap-1.5 chatify:rounded-lg chatify:border chatify:border-white/10 chatify:bg-white/[0.04] chatify:px-2 chatify:py-1 chatify:text-[11px] chatify:text-chatify-text chatify:transition hover:chatify:bg-chatify-primary/20 hover:chatify:border-chatify-primary/40"
                @click="selectNameplateUserSuggestion(u)"
              >
                <span class="chatify:font-medium">{{ u.name }}</span>
                <span class="chatify:text-[10px] chatify:text-chatify-muted">
                  {{ u.username ? `@${u.username}` : u.email }}
                </span>
                <span v-if="u.role === 'admin'" class="chatify:rounded chatify:bg-amber-400/20 chatify:px-1 chatify:text-[9px] chatify:font-semibold chatify:text-amber-300">Admin</span>
              </button>
            </div>
          </div>

          <input
            v-model="nameplateGiftTitle"
            type="text"
            placeholder="Gift notification title (optional)"
            class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2 chatify:px-3 chatify:text-xs chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
          />
          <textarea
            v-model="nameplateGiftDescription"
            rows="2"
            placeholder="Gift message (optional)"
            class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2 chatify:px-3 chatify:text-xs chatify:text-chatify-text chatify:transition focus:chatify:outline-none chatify:resize-none"
          />

          <div v-if="nameplateGiftSuccess" class="chatify:rounded-xl chatify:border chatify:border-emerald-500/30 chatify:bg-emerald-500/10 chatify:p-3 chatify:text-xs chatify:text-emerald-300">
            {{ nameplateGiftSuccess }}
          </div>
          <div v-if="nameplateGiftError" class="chatify:rounded-xl chatify:border chatify:border-red-500/30 chatify:bg-red-500/10 chatify:p-3 chatify:text-xs chatify:text-red-400">
            {{ nameplateGiftError }}
          </div>

          <button
            type="button"
            class="chatify:flex chatify:w-full chatify:items-center chatify:justify-center chatify:gap-2 chatify:rounded-xl chatify:bg-gradient-to-r chatify:from-amber-500 chatify:to-orange-500 chatify:py-2.5 chatify:px-4 chatify:text-sm chatify:font-semibold chatify:text-black chatify:shadow-lg chatify:transition hover:chatify:opacity-95 disabled:chatify:opacity-50"
            :disabled="giftingNameplate || !selectedNameplateId || !nameplateGiftQuery.trim()"
            @click="handleGiftNameplate"
          >
            <span>{{ giftingNameplate ? 'Gifting Nameplate...' : 'Gift Nameplate to User' }}</span>
          </button>
        </div>

        <!-- Section: Upload -->
        <div class="chatify:space-y-2 chatify:border-t chatify:border-white/10 chatify:pt-3">
          <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
            2. Upload New Nameplate
          </label>
          <input
            v-model="newNameplateName"
            type="text"
            maxlength="50"
            placeholder="Nameplate title (e.g. Crimson Crest)"
            class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2.5 chatify:px-3 chatify:text-sm chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
          />

          <div
            class="chatify:flex chatify:cursor-pointer chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-2xl chatify:border-2 chatify:border-dashed chatify:border-white/15 chatify:p-4 chatify:transition hover:chatify:border-chatify-primary/50 hover:chatify:bg-white/[0.02]"
            @click="nameplateInputRef?.click()"
          >
            <span v-if="newNameplatePreview" class="chatify-nameplate-preview chatify:flex chatify:items-center chatify:justify-center">
              <UserNameplate
                :nameplate="{ image_url: newNameplatePreview }"
                :name="newNameplateName || 'Preview'"
                mode="text"
                width="14rem"
              />
            </span>
            <template v-else>
              <svg class="chatify:h-7 chatify:w-7 chatify:text-chatify-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              <p class="chatify:mt-2 chatify:text-xs chatify:font-medium chatify:text-chatify-text">Click to browse nameplate image</p>
              <p class="chatify:text-[10px] chatify:text-chatify-muted">16:3 aspect ratio (recommended 448x84)</p>
            </template>
            <input
              ref="nameplateInputRef"
              type="file"
              accept="image/png,image/apng,image/webp"
              class="chatify:hidden"
              @change="onNameplateFileSelected"
            />
          </div>

          <div v-if="nameplateUploadSuccess" class="chatify:rounded-xl chatify:border chatify:border-emerald-500/30 chatify:bg-emerald-500/10 chatify:p-3 chatify:text-xs chatify:text-emerald-300">
            {{ nameplateUploadSuccess }}
          </div>
          <div v-if="nameplateUploadError" class="chatify:rounded-xl chatify:border chatify:border-red-500/30 chatify:bg-red-500/10 chatify:p-3 chatify:text-xs chatify:text-red-400">
            {{ nameplateUploadError }}
          </div>

          <button
            type="button"
            class="chatify:flex chatify:w-full chatify:items-center chatify:justify-center chatify:gap-2 chatify:rounded-xl chatify:bg-chatify-primary chatify:py-2.5 chatify:px-4 chatify:text-sm chatify:font-semibold chatify:text-white chatify:shadow-lg chatify:transition hover:chatify:opacity-95 disabled:chatify:opacity-50"
            :disabled="uploadingNameplate || !newNameplateName.trim() || !newNameplateFile"
            @click="handleUploadNameplate"
          >
            <span>{{ uploadingNameplate ? 'Uploading Nameplate...' : 'Upload & Save Nameplate' }}</span>
          </button>
        </div>

        <!-- Section: Catalog -->
        <div class="chatify:space-y-2 chatify:border-t chatify:border-white/10 chatify:pt-3">
          <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
            3. Nameplate Catalog ({{ nameplates.length }})
          </label>
          <div class="chatify:grid chatify:grid-cols-1 chatify:gap-2.5 chatify:sm:grid-cols-2">
            <div
              v-for="plate in nameplates"
              :key="plate.id"
              class="chatify:relative chatify:flex chatify:flex-col chatify:items-center chatify:rounded-xl chatify:border chatify:border-white/10 chatify:bg-[#121214] chatify:p-3 chatify:text-center"
            >
              <div class="chatify:absolute chatify:top-2 chatify:left-2">
                <span
                  class="chatify:rounded chatify:px-1.5 chatify:py-0.5 chatify:text-[9px] chatify:font-semibold"
                  :class="plate.is_system ? 'chatify:bg-white/10 chatify:text-zinc-400' : 'chatify:bg-emerald-500/20 chatify:text-emerald-300'"
                >
                  {{ plate.is_system ? 'System' : 'Custom' }}
                </span>
              </div>

              <button
                v-if="!plate.is_system"
                type="button"
                class="chatify:absolute chatify:top-2 chatify:right-2 chatify:text-red-400 hover:chatify:text-red-300"
                title="Delete custom nameplate"
                @click="deleteNameplate(plate.id)"
              >
                <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>

              <span class="chatify-nameplate-preview chatify:my-3 chatify:flex chatify:w-full chatify:items-center chatify:justify-center">
                <UserNameplate
                  :nameplate="plate"
                  :name="plate.name"
                  mode="text"
                  width="10rem"
                />
              </span>

              <h5 class="chatify:w-full chatify:truncate chatify:text-xs chatify:font-semibold chatify:text-white">
                {{ plate.name }}
              </h5>
              <p class="chatify:mt-1 chatify:text-[10px] chatify:text-chatify-muted">
                {{ plate.users_count ?? 0 }} user(s) gifted
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB: PROFILE EFFECTS (paired 450x880 APNG: pair-1 entrance, pair-2 loop) -->
      <div v-if="activeTab === 'effects'" class="chatify:space-y-4">
        <!-- Section: Gift -->
        <div class="chatify:space-y-2">
          <div class="chatify:flex chatify:items-center chatify:justify-between">
            <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
              1. Select Profile Effect to Gift
            </label>
            <span class="chatify:text-[11px] chatify:text-chatify-muted">
              {{ selectedProfileEffectId ? (profileEffects.find((e) => e.id === selectedProfileEffectId)?.name || 'Selected') : 'None selected' }}
            </span>
          </div>

          <div v-if="loadingProfileEffects" class="chatify:flex chatify:items-center chatify:justify-center chatify:py-4">
            <svg class="chatify:h-4 chatify:w-4 chatify:animate-spin chatify:text-chatify-muted" fill="none" viewBox="0 0 24 24">
              <circle class="chatify:opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="chatify:opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
            </svg>
          </div>

          <div v-else-if="profileEffects.length > 0" class="chatify:flex chatify:gap-2 chatify:overflow-x-auto chatify:py-2 chatify:scrollbar-thin">
            <button
              v-for="effect in profileEffects"
              :key="effect.id"
              type="button"
              class="chatify:group chatify:relative chatify:flex chatify:h-24 chatify:w-16 chatify:shrink-0 chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:border chatify:p-1.5 chatify:transition"
              :class="selectedProfileEffectId === effect.id ? 'chatify:border-chatify-primary chatify:bg-chatify-primary/10 chatify:shadow-lg' : 'chatify:border-white/10 chatify:bg-white/[0.04] hover:chatify:bg-white/[0.08]'"
              @click="selectedProfileEffectId = effect.id"
            >
              <span
                v-if="!effect.is_system"
                class="chatify:absolute chatify:top-1 chatify:right-1 chatify:z-20 chatify:flex chatify:h-4 chatify:w-4 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-red-500/80 chatify:text-white chatify:opacity-80 hover:chatify:opacity-100"
                title="Delete custom profile effect"
                @click.stop="deleteProfileEffect(effect.id)"
              >
                <svg class="chatify:h-2.5 chatify:w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </span>

              <span class="chatify:relative chatify:block chatify:h-16 chatify:w-10 chatify:overflow-hidden chatify:rounded-lg chatify:bg-black/40">
                <img
                  :src="effect.thumbnail_url"
                  :alt="effect.name"
                  class="chatify:h-full chatify:w-full chatify:object-contain"
                  draggable="false"
                />
              </span>
              <span class="chatify:mt-1 chatify:w-full chatify:truncate chatify:text-center chatify:text-[10px] chatify:font-medium chatify:text-chatify-text">
                {{ effect.name }}
              </span>
            </button>
          </div>

          <p v-else class="chatify:py-2 chatify:text-center chatify:text-[11px] chatify:text-chatify-muted">
            No profile effects yet. Upload one below.
          </p>

          <input
            v-model="effectGiftQuery"
            type="text"
            placeholder="Target username or email (e.g. @node1)"
            class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2.5 chatify:px-3 chatify:text-sm chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
            @input="onEffectQueryInput"
          />

          <!-- Profile effect user quick select suggestions (only while searching) -->
          <div
            v-if="effectGiftQuery.trim() && effectUserSuggestions.length > 0"
            class="chatify:space-y-1 chatify:pt-1"
          >
            <span class="chatify:text-[10px] chatify:text-chatify-muted chatify:uppercase">Matching Users:</span>
            <div class="chatify:flex chatify:flex-wrap chatify:gap-1.5">
              <button
                v-for="u in effectUserSuggestions"
                :key="u.id"
                type="button"
                class="chatify:inline-flex chatify:items-center chatify:gap-1.5 chatify:rounded-lg chatify:border chatify:border-white/10 chatify:bg-white/[0.04] chatify:px-2 chatify:py-1 chatify:text-[11px] chatify:text-chatify-text chatify:transition hover:chatify:bg-chatify-primary/20 hover:chatify:border-chatify-primary/40"
                @click="selectEffectUserSuggestion(u)"
              >
                <span class="chatify:font-medium">{{ u.name }}</span>
                <span class="chatify:text-[10px] chatify:text-chatify-muted">
                  {{ u.username ? `@${u.username}` : u.email }}
                </span>
                <span v-if="u.role === 'admin'" class="chatify:rounded chatify:bg-amber-400/20 chatify:px-1 chatify:text-[9px] chatify:font-semibold chatify:text-amber-300">Admin</span>
              </button>
            </div>
          </div>

          <input
            v-model="effectGiftTitle"
            type="text"
            placeholder="Gift notification title (optional)"
            class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2 chatify:px-3 chatify:text-xs chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
          />
          <textarea
            v-model="effectGiftDescription"
            rows="2"
            placeholder="Gift message (optional)"
            class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2 chatify:px-3 chatify:text-xs chatify:text-chatify-text chatify:transition focus:chatify:outline-none chatify:resize-none"
          />

          <div v-if="effectGiftSuccess" class="chatify:rounded-xl chatify:border chatify:border-emerald-500/30 chatify:bg-emerald-500/10 chatify:p-3 chatify:text-xs chatify:text-emerald-300">
            {{ effectGiftSuccess }}
          </div>
          <div v-if="effectGiftError" class="chatify:rounded-xl chatify:border chatify:border-red-500/30 chatify:bg-red-500/10 chatify:p-3 chatify:text-xs chatify:text-red-400">
            {{ effectGiftError }}
          </div>

          <button
            type="button"
            class="chatify:flex chatify:w-full chatify:items-center chatify:justify-center chatify:gap-2 chatify:rounded-xl chatify:bg-gradient-to-r chatify:from-amber-500 chatify:to-orange-500 chatify:py-2.5 chatify:px-4 chatify:text-sm chatify:font-semibold chatify:text-black chatify:shadow-lg chatify:transition hover:chatify:opacity-95 disabled:chatify:opacity-50"
            :disabled="giftingEffect || !selectedProfileEffectId || !effectGiftQuery.trim()"
            @click="handleGiftProfileEffect"
          >
            <span>{{ giftingEffect ? 'Gifting Profile Effect...' : 'Gift Profile Effect to User' }}</span>
          </button>
        </div>

        <!-- Section: Upload -->
        <div class="chatify:space-y-2 chatify:border-t chatify:border-white/10 chatify:pt-3">
          <div class="chatify:flex chatify:items-center chatify:justify-between">
            <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
              2. Upload New Profile Effect
            </label>
            <span class="chatify:text-[11px] chatify:text-chatify-muted">450x880 APNG pair</span>
          </div>

          <input
            v-model="newEffectName"
            type="text"
            maxlength="50"
            placeholder="Profile effect title (e.g. Crimson Rain)"
            class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2.5 chatify:px-3 chatify:text-sm chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
          />

          <div class="chatify:grid chatify:grid-cols-2 chatify:gap-2">
            <!-- Pair 1 — entrance -->
            <div
              class="chatify:flex chatify:cursor-pointer chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:border-2 chatify:border-dashed chatify:border-white/15 chatify:p-2 chatify:transition hover:chatify:border-chatify-primary/50 hover:chatify:bg-white/[0.02]"
              @click="effectPair1InputRef?.click()"
            >
              <div v-if="newEffectPair1Preview" class="chatify:relative chatify:h-32 chatify:w-full chatify:overflow-hidden chatify:rounded-lg chatify:bg-black/40">
                <img :src="newEffectPair1Preview" alt="Pair 1 preview" class="chatify:h-full chatify:w-full chatify:object-contain" />
              </div>
              <template v-else>
                <svg class="chatify:h-6 chatify:w-6 chatify:text-chatify-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="chatify:mt-1.5 chatify:text-[11px] chatify:font-medium chatify:text-chatify-text">Pair 1 — Entrance</p>
                <p class="chatify:text-[10px] chatify:text-chatify-muted">450x880 APNG</p>
              </template>
              <input
                ref="effectPair1InputRef"
                type="file"
                accept="image/png,image/apng,image/webp,image/gif"
                class="chatify:hidden"
                @change="onEffectPairSelected('pair1', $event)"
              />
            </div>

            <!-- Pair 2 — loop -->
            <div
              class="chatify:flex chatify:cursor-pointer chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:border-2 chatify:border-dashed chatify:border-white/15 chatify:p-2 chatify:transition hover:chatify:border-chatify-primary/50 hover:chatify:bg-white/[0.02]"
              @click="effectPair2InputRef?.click()"
            >
              <div v-if="newEffectPair2Preview" class="chatify:relative chatify:h-32 chatify:w-full chatify:overflow-hidden chatify:rounded-lg chatify:bg-black/40">
                <img :src="newEffectPair2Preview" alt="Pair 2 preview" class="chatify:h-full chatify:w-full chatify:object-contain" />
              </div>
              <template v-else>
                <svg class="chatify:h-6 chatify:w-6 chatify:text-chatify-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <p class="chatify:mt-1.5 chatify:text-[11px] chatify:font-medium chatify:text-chatify-text">Pair 2 — Loop</p>
                <p class="chatify:text-[10px] chatify:text-chatify-muted">450x880 APNG</p>
              </template>
              <input
                ref="effectPair2InputRef"
                type="file"
                accept="image/png,image/apng,image/webp,image/gif"
                class="chatify:hidden"
                @change="onEffectPairSelected('pair2', $event)"
              />
            </div>
          </div>

          <div v-if="effectUploadSuccess" class="chatify:rounded-xl chatify:border chatify:border-emerald-500/30 chatify:bg-emerald-500/10 chatify:p-3 chatify:text-xs chatify:text-emerald-300">
            {{ effectUploadSuccess }}
          </div>
          <div v-if="effectUploadError" class="chatify:rounded-xl chatify:border chatify:border-red-500/30 chatify:bg-red-500/10 chatify:p-3 chatify:text-xs chatify:text-red-400">
            {{ effectUploadError }}
          </div>

          <button
            type="button"
            class="chatify:flex chatify:w-full chatify:items-center chatify:justify-center chatify:gap-2 chatify:rounded-xl chatify:bg-chatify-primary chatify:py-2.5 chatify:px-4 chatify:text-sm chatify:font-semibold chatify:text-white chatify:shadow-lg chatify:transition hover:chatify:opacity-95 disabled:chatify:opacity-50"
            :disabled="uploadingEffect || !newEffectName.trim() || !newEffectPair1 || !newEffectPair2"
            @click="handleUploadProfileEffect"
          >
            <span>{{ uploadingEffect ? 'Uploading Profile Effect...' : 'Upload & Save Profile Effect' }}</span>
          </button>
        </div>

        <!-- Section: Catalog -->
        <div class="chatify:space-y-2 chatify:border-t chatify:border-white/10 chatify:pt-3">
          <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
            3. Profile Effect Catalog ({{ profileEffects.length }})
          </label>
          <div class="chatify:grid chatify:grid-cols-2 chatify:gap-2.5 chatify:sm:grid-cols-3">
            <div
              v-for="effect in profileEffects"
              :key="effect.id"
              class="chatify:relative chatify:flex chatify:flex-col chatify:items-center chatify:rounded-xl chatify:border chatify:border-white/10 chatify:bg-[#121214] chatify:p-2 chatify:text-center"
            >
              <div class="chatify:absolute chatify:top-2 chatify:left-2 chatify:z-10">
                <span
                  class="chatify:rounded chatify:px-1.5 chatify:py-0.5 chatify:text-[9px] chatify:font-semibold"
                  :class="effect.is_system ? 'chatify:bg-white/10 chatify:text-zinc-400' : 'chatify:bg-emerald-500/20 chatify:text-emerald-300'"
                >
                  {{ effect.is_system ? 'System' : 'Custom' }}
                </span>
              </div>

              <button
                v-if="!effect.is_system"
                type="button"
                class="chatify:absolute chatify:top-2 chatify:right-2 chatify:z-10 chatify:text-red-400 hover:chatify:text-red-300"
                title="Delete custom profile effect"
                @click="deleteProfileEffect(effect.id)"
              >
                <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>

              <div class="chatify:mt-5 chatify:h-36 chatify:w-full chatify:overflow-hidden chatify:rounded-lg chatify:bg-black/40">
                <img
                  :src="effect.thumbnail_url"
                  :alt="effect.name"
                  class="chatify:h-full chatify:w-full chatify:object-contain"
                  draggable="false"
                />
              </div>

              <h5 class="chatify:mt-2 chatify:w-full chatify:truncate chatify:text-xs chatify:font-semibold chatify:text-white">
                {{ effect.name }}
              </h5>
              <p class="chatify:mt-0.5 chatify:text-[10px] chatify:text-chatify-muted">
                {{ effect.users_count ?? 0 }} user(s) gifted
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 5: BROADCAST ANNOUNCEMENT / INFORMATION -->
      <div v-if="activeTab === 'announcements'" class="chatify:space-y-4">
        <div class="chatify:rounded-xl chatify:border chatify:border-blue-500/20 chatify:bg-blue-500/5 chatify:p-3 chatify:text-xs chatify:text-blue-300">
          <p class="chatify:font-medium">Broadcast Information & System Announcements</p>
          <p class="chatify:text-[11px] chatify:text-blue-200/70">
            Publish announcements directly to every user's notification center. All node and admin accounts will receive this update.
          </p>
        </div>

        <div class="chatify:space-y-1.5">
          <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
            Announcement Title
          </label>
          <input
            v-model="announcementTitle"
            type="text"
            maxlength="255"
            placeholder="e.g. Server Maintenance Notice"
            class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2.5 chatify:px-3 chatify:text-sm chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
          />
        </div>

        <div class="chatify:space-y-1.5">
          <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
            Description / Message Body
          </label>
          <textarea
            v-model="announcementBody"
            rows="4"
            placeholder="Write full announcement details here..."
            class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2.5 chatify:px-3 chatify:text-sm chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
          />
        </div>

        <div class="chatify:space-y-1.5">
          <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
            Banner Photo (Optional)
          </label>
          <div
            class="chatify:flex chatify:cursor-pointer chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-2xl chatify:border-2 chatify:border-dashed chatify:border-white/15 chatify:p-5 chatify:transition hover:chatify:border-chatify-primary/50 hover:chatify:bg-white/[0.02]"
            @click="announcementInputRef?.click()"
          >
            <div v-if="announcementPreview" class="chatify:relative chatify:max-h-36 chatify:overflow-hidden chatify:rounded-lg">
              <img :src="announcementPreview" alt="Announcement preview" class="chatify:max-h-36 chatify:w-auto chatify:object-cover" />
            </div>
            <div v-else class="chatify:flex chatify:flex-col chatify:items-center">
              <svg class="chatify:h-7 chatify:w-7 chatify:text-chatify-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <p class="chatify:mt-1.5 chatify:text-xs chatify:font-medium chatify:text-chatify-text">Click to choose image (Optional)</p>
              <p class="chatify:text-[10px] chatify:text-chatify-muted">PNG, JPG, or WEBP up to 10MB</p>
            </div>

            <input
              ref="announcementInputRef"
              type="file"
              accept="image/*"
              class="chatify:hidden"
              @change="onAnnouncementFileSelected"
            />
          </div>
        </div>

        <!-- Feedback Banners -->
        <div v-if="broadcastSuccess" class="chatify:flex chatify:items-center chatify:gap-2 chatify:rounded-xl chatify:border chatify:border-emerald-500/30 chatify:bg-emerald-500/10 chatify:p-3 chatify:text-xs chatify:text-emerald-300">
          <svg class="chatify:h-4 chatify:w-4 chatify:shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>{{ broadcastSuccess }}</span>
        </div>

        <div v-if="broadcastError" class="chatify:flex chatify:items-center chatify:gap-2 chatify:rounded-xl chatify:border chatify:border-red-500/30 chatify:bg-red-500/10 chatify:p-3 chatify:text-xs chatify:text-red-400">
          <svg class="chatify:h-4 chatify:w-4 chatify:shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ broadcastError }}</span>
        </div>

        <button
          type="button"
          class="chatify:flex chatify:w-full chatify:items-center chatify:justify-center chatify:gap-2 chatify:rounded-xl chatify:bg-chatify-primary chatify:py-2.5 chatify:px-4 chatify:text-sm chatify:font-semibold chatify:text-white chatify:shadow-lg chatify:transition hover:chatify:opacity-95 disabled:chatify:opacity-50"
          :disabled="broadcasting || !announcementTitle.trim() || !announcementBody.trim()"
          @click="handleBroadcast"
        >
          <svg v-if="broadcasting" class="chatify:h-4 chatify:w-4 chatify:animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="chatify:opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="chatify:opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
          </svg>
          <span>{{ broadcasting ? 'Broadcasting...' : 'Broadcast Announcement to All Users' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>
