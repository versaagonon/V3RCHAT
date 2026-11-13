<script setup lang="ts">
import { computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import type { ChatifyUser } from '../../types'
import { useContactsStore } from '../../stores/contacts'
import { useConversationsStore } from '../../stores/conversations'
import { usePresenceStore } from '../../stores/presence'
import { useUiStore } from '../../stores/ui'
import { useConfirmStore } from '../../stores/confirm'
import { useConfigStore } from '../../stores/config'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import { displayUserAvatar, displayUserName, isIdentityHidden } from '../../utils/userDisplay'
import { CHATIFY_TELEPORT_TARGET } from '../../constants/dom'
import UserAvatar from '../ui/UserAvatar.vue'
import LiquidGlassButton from '../ui/LiquidGlassButton.vue'
import ProfileEffectOverlay from '../ui/ProfileEffectOverlay.vue'

const uiStore = useUiStore()
const contactsStore = useContactsStore()
const conversationsStore = useConversationsStore()
const presenceStore = usePresenceStore()
const confirmStore = useConfirmStore()
const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { activeModal, modalContext } = storeToRefs(uiStore)
const { defaultAvatarUrl } = storeToRefs(configStore)

const open = computed(() => activeModal.value === 'contactInfo')
const user = computed(() => modalContext.value.user as ChatifyUser | null)
const hiddenIdentity = computed(() => isIdentityHidden(user.value))

const isOnline = computed(() =>
  user.value
  && !hiddenIdentity.value
  && !contactsStore.isMessagingBlocked(user.value.id)
  && presenceStore.canShowPresence()
  && presenceStore.visibleOnline(user.value.id),
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

async function startChat() {
  if (!user.value) return
  await conversationsStore.startDirect(user.value.id)
  uiStore.closeModal()
  uiStore.showThread()
}

async function toggleFavorite() {
  if (!user.value) return
  await contactsStore.toggleFavorite(user.value.id)
}

async function toggleBlock() {
  if (!user.value) return
  if (contactsStore.isBlocked(user.value.id)) {
    await contactsStore.unblockUser(user.value.id)
    return
  }
  const confirmed = await confirmStore.confirm({
    title: t('ui.confirm.block.title', { name: displayUserName(user.value) }),
    message: t('ui.confirm.block.message'),
    confirmLabel: t('ui.confirm.block.confirm'),
    variant: 'danger',
  })
  if (confirmed) await contactsStore.blockUser(user.value.id)
}

/**
 * Live specular glare for the glass action buttons — the hotspot follows the
 * pointer, matching the composer / floating-nav material.
 *
 * Values are written straight onto the element's inline custom properties rather
 * than through reactive refs, so a pointermove never triggers a Vue re-render.
 * The `liquid-glass-floating-nav` glare layer already reads these two vars.
 */
function onGlassGlare(event: PointerEvent) {
  const target = event.currentTarget as HTMLElement | null
  if (!target) return
  const rect = target.getBoundingClientRect()
  const x = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100))
  const y = Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100))
  target.style.setProperty('--glare-x', `${Math.round(x)}%`)
  target.style.setProperty('--glare-y', `${Math.round(y)}%`)
  target.style.setProperty('--glare-opacity', '0.95')
}

function resetGlassGlare(event: PointerEvent) {
  const target = event.currentTarget as HTMLElement | null
  if (!target) return
  target.style.setProperty('--glare-x', '50%')
  target.style.setProperty('--glare-y', '50%')
  target.style.setProperty('--glare-opacity', '0.65')
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
        <!--
          Sizing lives in `.chatify-contact-info-panel` (style.css): the card always
          keeps the native 450x880 / 1:1.9556 shape and fits the viewport with the
          same rule on mobile and desktop. The panel clips its rounded corners.
        -->
        <div
          role="dialog"
          aria-modal="true"
          :aria-label="user ? displayUserName(user) : 'Profile'"
          class="chatify-modal-panel chatify-contact-info-panel chatify:relative chatify:flex chatify:flex-col chatify:overflow-hidden chatify:rounded-2xl chatify:bg-chatify-bubble-in chatify:text-chatify-text chatify:shadow-2xl"
        >
          <!-- Close button — same liquid-glass component as the thread search / new chat buttons -->
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

          <!-- Paired 450x880 profile effect: pair-1 entrance, then pair-2 that
               rests 3s between plays. Topmost layer, pointer-events disabled. -->
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
            <!-- sits 60px above its natural flow position, overlapping the banner -->
            <div
              class="chatify:relative chatify:flex chatify:items-end chatify:px-6"
              style="margin-top: -68px; z-index: 20;"
            >
              <!--
                Avatar + border share one positioned box. The border plate renders at
                inset -10% / 120% so it reads as a ring AROUND the avatar; it must not
                sit inside the overflow-hidden circle or it gets clipped and drawn
                inwards instead of outwards.
              -->
              <div class="chatify:relative chatify:shrink-0" style="width: 104px; height: 104px;">
                <!-- Avatar wrapper with solid-bg ring to separate from banner -->
                <div
                  class="chatify:absolute chatify:inset-0 chatify:rounded-full chatify:p-[4px]"
                  style="background: #111115;"
                >
                  <!-- Avatar image -->
                  <div
                    class="chatify:relative chatify:h-full chatify:w-full chatify:overflow-hidden chatify:rounded-full chatify:bg-chatify-sidebar"
                  >
                    <img
                      v-if="displayUserAvatar(user, defaultAvatarUrl)"
                      :src="displayUserAvatar(user, defaultAvatarUrl)"
                      :alt="displayUserName(user)"
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

                <!-- Avatar border overlay: outside the clipped circle, above everything -->
                <img
                  v-if="user.attributes.avatar_border?.image_url"
                  :src="user.attributes.avatar_border.image_url"
                  alt=""
                  aria-hidden="true"
                  class="chatify-avatar-border chatify:pointer-events-none chatify:absolute chatify:z-20"
                />

                <!-- ● Online / Offline dot on avatar (bottom-right) -->
                <span
                  v-if="!hiddenIdentity && !contactsStore.isMessagingBlocked(user.id) && presenceStore.canShowPresence()"
                  class="chatify:absolute chatify:z-30 chatify:rounded-full chatify:transition-colors chatify:shadow-sm"
                  :class="isOnline ? 'chatify:bg-[#23a55a]' : 'chatify:bg-[#80848e]'"
                  style="bottom: 3px; right: 7px; width: 24px; height: 24px; border: 3.5px solid #111115;"
                  :title="isOnline ? 'Online' : 'Offline'"
                />
              </div>
            </div>

            <!-- ── PROFILE INFO ── -->
            <div class="chatify:flex chatify:flex-col chatify:gap-1 chatify:px-6 chatify:pt-4">
              <!-- Name + Admin badge -->
              <div class="chatify:flex chatify:items-center chatify:gap-2 chatify:flex-wrap">
                <h2 class="chatify:text-2xl chatify:font-bold chatify:leading-tight chatify:text-chatify-text">
                  {{ displayUserName(user) }}
                </h2>
                <span
                  v-if="user.attributes.role === 'admin'"
                  class="chatify:inline-flex chatify:items-center chatify:rounded-md chatify:bg-chatify-primary/15 chatify:px-2 chatify:py-0.5 chatify:text-[11px] chatify:font-bold chatify:uppercase chatify:tracking-wide chatify:text-chatify-primary"
                >
                  Admin
                </span>
              </div>

              <!-- Username -->
              <p v-if="user.attributes.username" class="chatify:text-sm chatify:text-chatify-muted">
                @{{ user.attributes.username }}
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

            <!-- FLEX SPACER pushes buttons to bottom -->
            <div class="chatify:flex-1" />

            <!-- ── ACTION BUTTONS ──
                 Glass material is the existing `liquid-glass-floating-nav` base plus its
                 four refraction layers — the same markup the floating nav and notification
                 switcher use, so nothing new is defined here. -->
            <div v-if="!hiddenIdentity" class="chatify:flex chatify:flex-col chatify:gap-2 chatify:px-6 chatify:pb-8 chatify:pt-4">
              <!-- Message -->
              <button
                type="button"
                class="liquid-glass-floating-nav chatify:transition-transform chatify:hover:scale-[1.01] chatify:active:scale-[0.98] chatify:disabled:cursor-not-allowed chatify:disabled:opacity-50"
                style="justify-content: center; max-width: none;"
                :disabled="contactsStore.isMessagingBlocked(user.id)"
                @pointermove="onGlassGlare"
                @pointerleave="resetGlassGlare"
                @click="startChat"
              >
                <span class="liquid-glass-refraction-fringe" aria-hidden="true" />
                <span class="liquid-glass-specular" aria-hidden="true" />
                <span class="liquid-glass-nav-glare" aria-hidden="true" />
                <span class="liquid-glass-nav-rim" aria-hidden="true" />
                <span class="chatify-contact-info-action-label chatify:text-chatify-text">
                  {{ $t('ui.modals.contact_info.message') }}
                </span>
              </button>

              <!-- Favorite / Block -->
              <div class="chatify:flex chatify:w-full chatify:gap-2">
                <button
                  v-if="!contactsStore.isMessagingBlocked(user.id)"
                  type="button"
                  class="liquid-glass-floating-nav chatify:flex-1 chatify:transition-transform chatify:hover:scale-[1.01] chatify:active:scale-[0.98]"
                  style="justify-content: center; max-width: none;"
                  @pointermove="onGlassGlare"
                  @pointerleave="resetGlassGlare"
                  @click="toggleFavorite"
                >
                  <span class="liquid-glass-refraction-fringe" aria-hidden="true" />
                  <span class="liquid-glass-specular" aria-hidden="true" />
                  <span class="liquid-glass-nav-glare" aria-hidden="true" />
                  <span class="liquid-glass-nav-rim" aria-hidden="true" />
                  <span class="chatify-contact-info-action-label chatify:text-chatify-text">
                    {{ contactsStore.isFavorite(user.id) ? $t('ui.actions.conversation.unfavorite') : $t('ui.actions.conversation.favorite') }}
                  </span>
                </button>
                <button
                  type="button"
                  class="liquid-glass-floating-nav chatify:flex-1 chatify:transition-transform chatify:hover:scale-[1.01] chatify:active:scale-[0.98]"
                  style="justify-content: center; max-width: none;"
                  @pointermove="onGlassGlare"
                  @pointerleave="resetGlassGlare"
                  @click="toggleBlock"
                >
                  <span class="liquid-glass-refraction-fringe" aria-hidden="true" />
                  <span class="liquid-glass-specular" aria-hidden="true" />
                  <span class="liquid-glass-nav-glare" aria-hidden="true" />
                  <span class="liquid-glass-nav-rim" aria-hidden="true" />
                  <span
                    class="chatify-contact-info-action-label"
                    :class="contactsStore.isBlocked(user.id) ? 'chatify:text-chatify-primary' : 'chatify:text-red-400'"
                  >
                    {{ contactsStore.isBlocked(user.id) ? $t('ui.actions.conversation.unblock') : $t('ui.actions.conversation.block') }}
                  </span>
                </button>
              </div>
            </div>

            <!-- identity hidden fallback -->
            <div v-else class="chatify:px-6 chatify:pb-8 chatify:pt-4">
              <button
                type="button"
                class="liquid-glass-floating-nav chatify:transition-transform chatify:hover:scale-[1.01] chatify:active:scale-[0.98]"
                style="justify-content: center; max-width: none;"
                @pointermove="onGlassGlare"
                @pointerleave="resetGlassGlare"
                @click="uiStore.closeModal()"
              >
                <span class="liquid-glass-refraction-fringe" aria-hidden="true" />
                <span class="liquid-glass-specular" aria-hidden="true" />
                <span class="liquid-glass-nav-glare" aria-hidden="true" />
                <span class="liquid-glass-nav-rim" aria-hidden="true" />
                <span
                  class="chatify:text-sm chatify:font-medium chatify:text-chatify-muted"
                  style="position: relative; z-index: 2;"
                >
                  Close
                </span>
              </button>
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
