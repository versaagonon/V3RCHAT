<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useConfigStore } from '../../stores/config'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import type { AvatarBorder } from '../../types'
import UserAvatar from '../ui/UserAvatar.vue'

const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { user } = storeToRefs(configStore)

const isAdmin = computed(() => (user.value?.attributes.role ?? 'node') === 'admin')

const activeTab = ref<'gift' | 'upload' | 'catalog'>('gift')

// Data
const borders = ref<AvatarBorder[]>([])
const loadingBorders = ref(false)
const selectedBorderId = ref<number | null>(null)

// Gift Form
const userQuery = ref('')
const gifting = ref(false)
const giftSuccess = ref<string | null>(null)
const giftError = ref<string | null>(null)
const userSuggestions = ref<Array<{ id: number; name: string; username: string | null; email: string; role?: string; borders_count: number }>>([])
const searchingUsers = ref(false)

// Upload Form
const newBorderName = ref('')
const newBorderFile = ref<File | null>(null)
const newBorderPreview = ref<string | null>(null)
const uploadingBorder = ref(false)
const uploadSuccess = ref<string | null>(null)
const uploadError = ref<string | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)

const selectedBorder = computed(() => borders.value.find((b) => b.id === selectedBorderId.value) ?? null)

onMounted(async () => {
  if (isAdmin.value) {
    await fetchBorders()
    await searchUsers('')
  }
})

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
  searchUsers(userQuery.value.trim())
}

function selectUserSuggestion(u: { username: string | null; email: string }) {
  userQuery.value = u.username ? `@${u.username}` : u.email
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
    })
    giftSuccess.value = res.data.message
    userQuery.value = ''
    await fetchBorders()
    await searchUsers('')
  } catch (err: any) {
    giftError.value = err?.response?.data?.message || 'Failed to gift border.'
  } finally {
    gifting.value = false
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
    activeTab.value = 'catalog'
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
      <!-- Admin Clearance Header -->
      <div class="chatify:flex chatify:items-center chatify:justify-between chatify:rounded-xl chatify:border chatify:border-amber-500/30 chatify:bg-amber-500/10 chatify:p-3">
        <div class="chatify:flex chatify:items-center chatify:gap-2.5">
          <div class="chatify:flex chatify:h-7 chatify:w-7 chatify:items-center chatify:justify-center chatify:rounded-lg chatify:bg-amber-500/20 chatify:text-amber-400">
            <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <div>
            <h4 class="chatify:text-xs chatify:font-bold chatify:text-amber-300">Administrator Control Panel</h4>
            <p class="chatify:text-[10px] chatify:text-amber-200/80">Animated avatar borders distribution & management</p>
          </div>
        </div>
        <span class="chatify:inline-flex chatify:items-center chatify:rounded-full chatify:bg-amber-400/20 chatify:px-2 chatify:py-0.5 chatify:text-[10px] chatify:font-bold chatify:text-amber-300 chatify:uppercase">
          Admin
        </span>
      </div>

      <!-- Navigation Tabs -->
      <div class="chatify:flex chatify:rounded-xl chatify:border chatify:border-white/10 chatify:bg-[#121214] chatify:p-1">
        <button
          type="button"
          class="chatify:flex-1 chatify:rounded-lg chatify:py-1.5 chatify:text-center chatify:text-xs chatify:font-medium chatify:transition"
          :class="activeTab === 'gift' ? 'chatify:bg-zinc-800 chatify:text-white chatify:shadow-sm' : 'chatify:text-zinc-400 hover:chatify:text-zinc-200'"
          @click="activeTab = 'gift'"
        >
          🎁 Gift Border
        </button>
        <button
          type="button"
          class="chatify:flex-1 chatify:rounded-lg chatify:py-1.5 chatify:text-center chatify:text-xs chatify:font-medium chatify:transition"
          :class="activeTab === 'upload' ? 'chatify:bg-zinc-800 chatify:text-white chatify:shadow-sm' : 'chatify:text-zinc-400 hover:chatify:text-zinc-200'"
          @click="activeTab = 'upload'"
        >
          ➕ Add Border
        </button>
        <button
          type="button"
          class="chatify:flex-1 chatify:rounded-lg chatify:py-1.5 chatify:text-center chatify:text-xs chatify:font-medium chatify:transition"
          :class="activeTab === 'catalog' ? 'chatify:bg-zinc-800 chatify:text-white chatify:shadow-sm' : 'chatify:text-zinc-400 hover:chatify:text-zinc-200'"
          @click="activeTab = 'catalog'"
        >
          📚 Catalog ({{ borders.length }})
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
              <div class="chatify:relative chatify:h-12 chatify:w-12">
                <div class="chatify:h-full chatify:w-full chatify:rounded-full chatify:bg-zinc-800" />
                <img
                  :src="border.image_url"
                  :alt="border.name"
                  class="chatify:pointer-events-none chatify:absolute chatify:inset-[-18%] chatify:h-[136%] chatify:w-[136%] chatify:max-w-none chatify:object-contain chatify:z-10"
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
              placeholder="e.g. tokesuntik or tokesuntik@gmail.com"
              class="chatify-settings-input chatify:w-full chatify:rounded-xl chatify:py-2.5 chatify:pl-9 chatify:pr-3 chatify:text-sm chatify:text-chatify-text chatify:transition focus:chatify:outline-none"
              @input="onUserQueryInput"
            />
          </div>

          <!-- User quick select suggestions -->
          <div v-if="userSuggestions.length > 0" class="chatify:space-y-1 chatify:pt-1">
            <span class="chatify:text-[10px] chatify:text-chatify-muted chatify:uppercase">Registered Node Users:</span>
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
          <span v-else>✨ Gift Animated Border to User</span>
        </button>
      </div>

      <!-- TAB 2: UPLOAD NEW BORDER -->
      <div v-if="activeTab === 'upload'" class="chatify:space-y-4">
        <div class="chatify:space-y-1.5">
          <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
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
          <label class="chatify:text-xs chatify:font-semibold chatify:text-chatify-text">
            Animated Image File (288x288 / 1:1 APNG or PNG)
          </label>
          <div
            class="chatify:flex chatify:cursor-pointer chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-2xl chatify:border-2 chatify:border-dashed chatify:border-white/15 chatify:p-6 chatify:transition hover:chatify:border-chatify-primary/50 hover:chatify:bg-white/[0.02]"
            @click="fileInputRef?.click()"
          >
            <!-- Live Preview if file chosen -->
            <div v-if="newBorderPreview" class="chatify:relative chatify:h-24 chatify:w-24">
              <div class="chatify:h-full chatify:w-full chatify:rounded-full chatify:bg-zinc-800" />
              <img
                :src="newBorderPreview"
                alt="Preview"
                class="chatify:pointer-events-none chatify:absolute chatify:inset-[-18%] chatify:h-[136%] chatify:w-[136%] chatify:max-w-none chatify:object-contain chatify:z-10"
              />
            </div>
            <div v-else class="chatify:flex chatify:flex-col chatify:items-center">
              <svg class="chatify:h-8 chatify:w-8 chatify:text-chatify-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

      <!-- TAB 3: BORDERS CATALOG -->
      <div v-if="activeTab === 'catalog'" class="chatify:space-y-3">
        <div class="chatify:grid chatify:grid-cols-2 chatify:gap-2.5 chatify:sm:grid-cols-3">
          <div
            v-for="b in borders"
            :key="b.id"
            class="chatify:relative chatify:flex chatify:flex-col chatify:items-center chatify:rounded-xl chatify:border chatify:border-white/10 chatify:bg-[#121214] chatify:p-3 chatify:text-center"
          >
            <!-- Badge -->
            <div class="chatify:absolute chatify:top-2 chatify:left-2">
              <span
                class="chatify:rounded chatify:px-1.5 chatify:py-0.5 chatify:text-[9px] chatify:font-semibold"
                :class="b.is_system ? 'chatify:bg-white/10 chatify:text-zinc-400' : 'chatify:bg-emerald-500/20 chatify:text-emerald-300'"
              >
                {{ b.is_system ? 'System' : 'Custom' }}
              </span>
            </div>

            <!-- Delete Custom Border -->
            <button
              v-if="!b.is_system"
              type="button"
              class="chatify:absolute chatify:top-2 chatify:right-2 chatify:text-red-400 hover:chatify:text-red-300"
              title="Delete custom border"
              @click="deleteBorder(b.id)"
            >
              <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>

            <!-- Border Preview Frame -->
            <div class="chatify:relative chatify:my-3 chatify:h-16 chatify:w-16">
              <div class="chatify:h-full chatify:w-full chatify:rounded-full chatify:bg-zinc-800" />
              <img
                :src="b.image_url"
                :alt="b.name"
                class="chatify:pointer-events-none chatify:absolute chatify:inset-[-18%] chatify:h-[136%] chatify:w-[136%] chatify:max-w-none chatify:object-contain chatify:z-10"
              />
            </div>

            <h5 class="chatify:w-full chatify:truncate chatify:text-xs chatify:font-semibold chatify:text-white">
              {{ b.name }}
            </h5>
            <p class="chatify:mt-1 chatify:text-[10px] chatify:text-chatify-muted">
              {{ b.users_count ?? 0 }} user(s) gifted
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
