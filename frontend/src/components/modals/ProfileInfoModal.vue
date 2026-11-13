<script setup lang="ts">
import { computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useConfigStore } from '../../stores/config'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import { CHATIFY_TELEPORT_TARGET } from '../../constants/dom'
import LiquidGlassButton from '../ui/LiquidGlassButton.vue'
import ProfileEffectOverlay from '../ui/ProfileEffectOverlay.vue'
import SettingsGroup from '../settings/SettingsGroup.vue'
import SettingsRow from '../settings/SettingsRow.vue'

const uiStore = useUiStore()
const configStore = useConfigStore()
const conversationsStore = useConversationsStore()
const { t } = useChatifyI18n()
const { activeModal } = storeToRefs(uiStore)
const { user, savedAvatarUrl, defaultAvatarUrl, showOnlineStatus } = storeToRefs(configStore)

const open = computed(() => activeModal.value === 'profileInfo')

const displayName = computed(() => user.value?.attributes.name ?? '')
const username = computed(() => user.value?.attributes.username ? `@${user.value.attributes.username}` : '')
const email = computed(() => user.value?.attributes.email ?? '')
const isAdmin = computed(() => (user.value?.attributes.role ?? 'node') === 'admin')

const avatarSrc = computed(
  () => savedAvatarUrl.value || user.value?.attributes.avatar || defaultAvatarUrl.value || '',
)

// Banner style
const bannerStyle = computed(() => {
  const banner = user.value?.attributes.profile_banner
  if (banner) {
    return {
      backgroundImage: `url(${banner})`,
      backgroundSize: 'cover',
      backgroundPosition: 'center',
    }
  }
  return {
    background: 'linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%)',
  }
})

// Member since formatted
const memberSince = computed(() => {
  const raw = user.value?.attributes.created_at
  if (!raw) return null
  try {
    return new Date(raw).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    })
  } catch {
    return null
  }
})

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && open.value) {
    event.preventDefault()
    uiStore.closeModal()
  }
}

watch(open, (isOpen) => {
  if (isOpen) document.addEventListener('keydown', onKeydown)
  else document.removeEventListener('keydown', onKeydown)
}, { immediate: true })

async function toggleOnlineStatus() {
  await configStore.setShowOnlineStatus(!showOnlineStatus.value)
}

function goToEditProfile() {
  uiStore.closeModal()
  uiStore.requestSettingsScreen('profile')
  conversationsStore.setSidebarTab('settings')
}
</script>

<template>
  <Teleport :to="CHATIFY_TELEPORT_TARGET">
    <Transition name="chatify-modal">
      <div
        v-if="open"
        class="chatify:fixed chatify:inset-0 chatify:z-50 chatify:flex chatify:items-center chatify:justify-center chatify:bg-black/60 chatify:p-4"
        @click.self="uiStore.closeModal()"
      >
        <div
          role="dialog"
          aria-modal="true"
          :aria-label="displayName || 'Profile'"
          class="chatify-modal-panel chatify-contact-info-panel chatify:relative chatify:flex chatify:flex-col chatify:overflow-hidden chatify:rounded-2xl chatify:bg-chatify-bubble-in chatify:text-chatify-text chatify:shadow-2xl"
        >
          <!-- Close button -->
          <div class="chatify:absolute chatify:right-3 chatify:top-3 chatify:z-30">
            <LiquidGlassButton
              :aria-label="t('ui.modals.close')"
              :title="t('ui.modals.close')"
              size="2.1rem"
              @click="uiStore.closeModal()"
            >
              <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </LiquidGlassButton>
          </div>

          <!-- Paired profile effect: pair-1 entrance, then pair-2 with a 3s rest between plays. -->
          <ProfileEffectOverlay :effect="user?.attributes.profile_effect ?? null" />

          <div v-if="user" class="chatify:flex chatify:h-full chatify:flex-col chatify:overflow-y-auto">

            <!-- ── BANNER (200px fixed height) ── -->
            <div
              class="chatify:relative chatify:w-full chatify:shrink-0"
              style="height: 200px;"
              :style="bannerStyle"
            >
              <!-- Bottom fade into card bg -->
              <div
                class="chatify:absolute chatify:inset-x-0 chatify:bottom-0"
                style="height: 80px; background: linear-gradient(to bottom, transparent, #111115);"
              />
            </div>

            <!-- ── AVATAR OVERLAPPING BANNER ── -->
            <div
              class="chatify:relative chatify:flex chatify:items-end chatify:px-6"
              style="margin-top: -68px; z-index: 20;"
            >
              <div class="chatify:relative chatify:shrink-0" style="width: 104px; height: 104px;">
                <!-- Avatar wrapper with solid-bg ring to separate from banner -->
                <div
                  class="chatify:absolute chatify:inset-0 chatify:rounded-full chatify:p-[4px]"
                  style="background: #111115;"
                >
                  <div
                    class="chatify:relative chatify:h-full chatify:w-full chatify:overflow-hidden chatify:rounded-full chatify:bg-chatify-sidebar"
                  >
                    <img
                      v-if="avatarSrc"
                      :src="avatarSrc"
                      :alt="displayName"
                      class="chatify:h-full chatify:w-full chatify:object-cover"
                    />
                    <div
                      v-else
                      class="chatify:flex chatify:h-full chatify:w-full chatify:items-center chatify:justify-center chatify:text-chatify-muted"
                    >
                      <svg class="chatify:h-10 chatify:w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                    </div>
                  </div>
                </div>

                <!-- Avatar border overlay -->
                <img
                  v-if="user.attributes.avatar_border?.image_url"
                  :src="user.attributes.avatar_border.image_url"
                  alt=""
                  aria-hidden="true"
                  class="chatify-avatar-border chatify:pointer-events-none chatify:absolute chatify:z-20"
                />

                <!-- Online dot (self) -->
                <span
                  class="chatify:absolute chatify:z-30 chatify:rounded-full chatify:shadow-sm"
                  :class="showOnlineStatus ? 'chatify:bg-[#23a55a]' : 'chatify:bg-[#80848e]'"
                  style="bottom: 3px; right: 7px; width: 24px; height: 24px; border: 3.5px solid #111115;"
                  :title="showOnlineStatus ? 'Online' : 'Offline'"
                />
              </div>
            </div>

            <!-- ── PROFILE INFO ── -->
            <div class="chatify:flex chatify:flex-col chatify:gap-1 chatify:px-6 chatify:pt-4">
              <div class="chatify:flex chatify:items-center chatify:gap-2 chatify:flex-wrap">
                <h2 class="chatify:text-2xl chatify:font-bold chatify:leading-tight chatify:text-chatify-text">
                  {{ displayName }}
                </h2>
                <span
                  v-if="isAdmin"
                  class="chatify:inline-flex chatify:items-center chatify:rounded-md chatify:bg-chatify-primary/15 chatify:px-2 chatify:py-0.5 chatify:text-[11px] chatify:font-bold chatify:uppercase chatify:tracking-wide chatify:text-chatify-primary"
                >
                  Admin
                </span>
              </div>

              <p v-if="username || email" class="chatify:text-sm chatify:text-chatify-muted">
                {{ username || email }}
              </p>
            </div>

            <!-- ── DIVIDER ── -->
            <div class="chatify:mx-6 chatify:my-5 chatify:border-t chatify:border-white/[0.08]" />

            <!-- ── META: Member Since ── -->
            <div class="chatify:flex chatify:flex-col chatify:gap-1 chatify:px-6">
              <div v-if="memberSince">
                <p class="chatify:text-[11px] chatify:font-semibold chatify:uppercase chatify:tracking-widest chatify:text-chatify-muted">
                  Member Since
                </p>
                <p class="chatify:mt-1 chatify:text-sm chatify:font-semibold chatify:text-chatify-text">
                  {{ memberSince }}
                </p>
              </div>
            </div>

            <!-- ── PROFILE MENU (directly under Member Since) ── -->
            <div class="chatify:px-6 chatify:pb-8 chatify:pt-5">
              <SettingsGroup>
                <SettingsRow
                  :label="t('ui.settings.edit_profile', 'Edit Profile')"
                  chevron
                  @click="goToEditProfile"
                >
                  <template #icon>
                    <svg class="chatify:h-4 chatify:w-4 chatify:shrink-0 chatify:text-chatify-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </template>
                </SettingsRow>

                <SettingsRow
                  :label="t('ui.settings.show_online_status', 'Online Status')"
                  toggle
                  :toggle-on="showOnlineStatus"
                  @toggle="toggleOnlineStatus"
                >
                  <template #icon>
                    <span
                      class="chatify:h-2.5 chatify:w-2.5 chatify:shrink-0 chatify:rounded-full"
                      :class="showOnlineStatus ? 'chatify:bg-[#23a55a]' : 'chatify:bg-zinc-500'"
                    />
                  </template>
                </SettingsRow>
              </SettingsGroup>
            </div>
          </div>

          <!-- Loading skeleton -->
          <div v-else class="chatify:flex chatify:h-full chatify:items-center chatify:justify-center chatify:text-chatify-muted">
            <svg class="chatify:h-6 chatify:w-6 chatify:animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="chatify:opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="chatify:opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
            </svg>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
