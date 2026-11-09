<!-- language: Vue 3 / TypeScript, file: frontend/src/components/landing/LandingPage.vue, target: browser -->
<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { animate, inView, stagger } from 'motion'
import type { ChatifyUser } from '../../types'
import logoImg from '../../assets/images/logo_transparant.png'
import land1Avatar from '../../assets/images/land1.jpg'
import land2Avatar from '../../assets/images/land2.jpg'

const logoUrl = logoImg || '/vendor/chatify/logo_transparant.png'
const donAvatarUrl = land1Avatar
const ravenAvatarUrl = land2Avatar

defineProps<{
  user?: ChatifyUser | null
  loginUrl?: string
  registerUrl?: string
  dashboardUrl?: string
  appName?: string
}>()

const rootContainerRef = ref<HTMLElement | null>(null)

// Interactive Glare & 3D Perspective Tilt for Hero Terminal
const heroGlareX = ref('50%')
const heroGlareY = ref('25%')
const heroGlareOpacity = ref('0.45')
const heroRotateX = ref('0deg')
const heroRotateY = ref('0deg')

function handleHeroPointer(e: PointerEvent) {
  const target = e.currentTarget as HTMLElement | null
  if (!target) return
  const rect = target.getBoundingClientRect()
  const rawX = (e.clientX - rect.left) / rect.width
  const rawY = (e.clientY - rect.top) / rect.height
  const x = Math.max(0, Math.min(100, rawX * 100))
  const y = Math.max(0, Math.min(100, rawY * 100))

  heroGlareX.value = `${Math.round(x)}%`
  heroGlareY.value = `${Math.round(y)}%`
  heroGlareOpacity.value = '0.85'

  // Subtle high-end 3D tilt (-3.5deg to +3.5deg)
  const tiltX = (0.5 - rawY) * 7
  const tiltY = (rawX - 0.5) * 7
  heroRotateX.value = `${tiltX.toFixed(2)}deg`
  heroRotateY.value = `${tiltY.toFixed(2)}deg`
}

function handleHeroPointerLeave() {
  heroGlareX.value = '50%'
  heroGlareY.value = '25%'
  heroGlareOpacity.value = '0.45'
  heroRotateX.value = '0deg'
  heroRotateY.value = '0deg'
}

// Card glare & 3D tilt states
const cardGlares = ref([
  { x: '50%', y: '50%', opacity: '0', rx: '0deg', ry: '0deg' },
  { x: '50%', y: '50%', opacity: '0', rx: '0deg', ry: '0deg' },
  { x: '50%', y: '50%', opacity: '0', rx: '0deg', ry: '0deg' },
  { x: '50%', y: '50%', opacity: '0', rx: '0deg', ry: '0deg' },
])

function handleCardPointer(index: number, e: PointerEvent) {
  const target = e.currentTarget as HTMLElement | null
  if (!target || !cardGlares.value[index]) return
  const rect = target.getBoundingClientRect()
  const rawX = (e.clientX - rect.left) / rect.width
  const rawY = (e.clientY - rect.top) / rect.height
  const x = Math.max(0, Math.min(100, rawX * 100))
  const y = Math.max(0, Math.min(100, rawY * 100))

  cardGlares.value[index].x = `${Math.round(x)}%`
  cardGlares.value[index].y = `${Math.round(y)}%`
  cardGlares.value[index].opacity = '0.9'
  cardGlares.value[index].rx = `${((0.5 - rawY) * 6).toFixed(2)}deg`
  cardGlares.value[index].ry = `${((rawX - 0.5) * 6).toFixed(2)}deg`
}

function handleCardPointerLeave(index: number) {
  if (!cardGlares.value[index]) return
  cardGlares.value[index].opacity = '0'
  cardGlares.value[index].rx = '0deg'
  cardGlares.value[index].ry = '0deg'
}

// Interactive Terminal Mockup State (Allows competition judges to interact!)
const customDispatchInput = ref('')
interface MockDispatch {
  author: string
  text: string
  time: string
  hash: string
  isVoice?: boolean
  duration?: string
}
const liveDispatches = ref<MockDispatch[]>([])

const presetDispatches = [
  'Handshake verified. Rotating ephemeral session keys across 12 sovereign nodes.',
  'Zero-Log directive enforced. Memory page table scrubbed in 0.4ms.',
  'Telemetry clean. Direct Reverb WebSocket tunnel operating at full capacity.',
]
let presetIndex = 0

function triggerMockDispatch() {
  const textToSend =
    customDispatchInput.value.trim() ||
    presetDispatches[presetIndex % presetDispatches.length] ||
    'Encrypted packet transmitted via isolated Reverb tunnel.'
  presetIndex++
  customDispatchInput.value = ''

  const now = new Date()
  const hh = String(now.getHours()).padStart(2, '0')
  const mm = String(now.getMinutes()).padStart(2, '0')
  const randomHash = Math.random().toString(16).substring(2, 6).toUpperCase()

  liveDispatches.value.push({
    author: 'Operator (You)',
    text: textToSend,
    time: `${hh}:${mm} • Delivered`,
    hash: `0x${randomHash}`,
  })

  if (liveDispatches.value.length > 3) {
    liveDispatches.value.shift()
  }
}

function triggerVoiceDispatch() {
  const now = new Date()
  const hh = String(now.getHours()).padStart(2, '0')
  const mm = String(now.getMinutes()).padStart(2, '0')
  const randomHash = Math.random().toString(16).substring(2, 6).toUpperCase()

  liveDispatches.value.push({
    author: 'Operator (You)',
    text: '',
    time: `${hh}:${mm} • Delivered`,
    hash: `0x${randomHash}`,
    isVoice: true,
    duration: '0:18',
  })

  if (liveDispatches.value.length > 3) {
    liveDispatches.value.shift()
  }
}

function triggerMockAttachment() {
  const now = new Date()
  const hh = String(now.getHours()).padStart(2, '0')
  const mm = String(now.getMinutes()).padStart(2, '0')
  const randomHash = Math.random().toString(16).substring(2, 6).toUpperCase()

  liveDispatches.value.push({
    author: 'Operator (You)',
    text: 'syndicate_payload_v3r_manifest.enc (2.4 MB) • SHA-256 Verified',
    time: `${hh}:${mm} • Delivered`,
    hash: `0x${randomHash}`,
  })

  if (liveDispatches.value.length > 3) {
    liveDispatches.value.shift()
  }
}

function scrollToSection(id: string) {
  const target = document.getElementById(id)
  if (!target) return
  target.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

// Typewriter headline effect: "Sovereign Messaging. Zero-Trace Control."
const fullLine1 = 'Sovereign Messaging.'
const fullLine2 = 'Zero-Trace Control.'

const displayedLine1 = ref('')
const displayedLine2 = ref('')
const activeLine = ref<1 | 2>(1)
const isCursorBlinking = ref(true)

const isHeadlineEmpty = computed(
  () => displayedLine1.value.length === 0 && displayedLine2.value.length === 0
)

let typewriterTimer: number | null = null

function startTypewriter() {
  let phase: 'idle' | 'typing1' | 'typing2' | 'hold' | 'deleting2' | 'deleting1' = 'idle'
  let charIdx = 0

  displayedLine1.value = ''
  displayedLine2.value = ''
  activeLine.value = 1
  isCursorBlinking.value = true

  // Initial state: Start with only the logo emblem visible. Delay 5 seconds before typing begins.
  typewriterTimer = window.setTimeout(() => {
    phase = 'typing1'
    charIdx = 0
    tick()
  }, 5000)

  function tick() {
    if (phase === 'typing1') {
      activeLine.value = 1
      isCursorBlinking.value = false
      if (charIdx < fullLine1.length) {
        charIdx++
        displayedLine1.value = fullLine1.slice(0, charIdx)
        typewriterTimer = window.setTimeout(tick, 55)
      } else {
        phase = 'typing2'
        activeLine.value = 2
        charIdx = 0
        typewriterTimer = window.setTimeout(tick, 85)
      }
    } else if (phase === 'typing2') {
      activeLine.value = 2
      isCursorBlinking.value = false
      if (charIdx < fullLine2.length) {
        charIdx++
        displayedLine2.value = fullLine2.slice(0, charIdx)
        typewriterTimer = window.setTimeout(tick, 55)
      } else {
        phase = 'hold'
        isCursorBlinking.value = true
        // Hold the completed text for 5 seconds
        typewriterTimer = window.setTimeout(tick, 5000)
      }
    } else if (phase === 'hold') {
      phase = 'deleting2'
      activeLine.value = 2
      isCursorBlinking.value = false
      tick()
    } else if (phase === 'deleting2') {
      activeLine.value = 2
      if (displayedLine2.value.length > 0) {
        displayedLine2.value = displayedLine2.value.slice(0, -1)
        typewriterTimer = window.setTimeout(tick, 28)
      } else {
        // Line 2 completely erased -> immediately jump cursor up to line 1
        phase = 'deleting1'
        activeLine.value = 1
        typewriterTimer = window.setTimeout(tick, 28)
      }
    } else if (phase === 'deleting1') {
      activeLine.value = 1
      if (displayedLine1.value.length > 0) {
        displayedLine1.value = displayedLine1.value.slice(0, -1)
        typewriterTimer = window.setTimeout(tick, 28)
      } else {
        // Both lines completely erased -> hold logo alone for 5 seconds before next cycle
        phase = 'idle'
        isCursorBlinking.value = true
        typewriterTimer = window.setTimeout(() => {
          phase = 'typing1'
          charIdx = 0
          tick()
        }, 5000)
      }
    }
  }
}

// Mount animations using lightweight motion
onMounted(() => {
  if (typeof window === 'undefined') return

  // Update browser icon bar / favicon dynamically
  try {
    let faviconLink = document.querySelector<HTMLLinkElement>("link[rel~='icon']")
    if (!faviconLink) {
      faviconLink = document.createElement('link')
      faviconLink.rel = 'icon'
      document.head.appendChild(faviconLink)
    }
    faviconLink.type = 'image/png'
    faviconLink.href = logoUrl
  } catch {
    // Graceful fallback
  }

  startTypewriter()

  try {
    const heroElements = document.querySelectorAll('.v3r-hero-reveal')
    if (heroElements.length > 0) {
      animate(
        heroElements,
        {
          opacity: [0, 1],
          transform: ['translateY(28px) scale(0.985)', 'translateY(0px) scale(1)'],
          filter: ['blur(8px)', 'blur(0px)'],
        },
        { delay: stagger(0.09), duration: 0.85, ease: [0.16, 1, 0.3, 1] },
      )
    }

    if (typeof IntersectionObserver !== 'undefined') {
      inView('.v3r-section-header', (element) => {
        animate(
          element,
          {
            opacity: [0, 1],
            transform: ['translateY(20px)', 'translateY(0px)'],
          },
          { duration: 0.7, ease: [0.16, 1, 0.3, 1] },
        )
      })

      inView('.v3r-features-grid', (element) => {
        const cards = element.querySelectorAll('.v3r-feature-card')
        if (cards.length > 0) {
          animate(
            cards,
            {
              opacity: [0, 1],
              transform: ['translateY(32px) scale(0.97)', 'translateY(0px) scale(1)'],
            },
            { delay: stagger(0.09), duration: 0.7, ease: [0.16, 1, 0.3, 1] },
          )
        }
      })

      inView('.v3r-comparison-section', (element) => {
        const rows = element.querySelectorAll('.v3r-compare-row')
        if (rows.length > 0) {
          animate(
            rows,
            {
              opacity: [0, 1],
              transform: ['translateX(-18px)', 'translateX(0px)'],
            },
            { delay: stagger(0.07), duration: 0.6, ease: [0.16, 1, 0.3, 1] },
          )
        }
      })

      inView('.v3r-manifesto-box', (element) => {
        animate(
          element,
          {
            opacity: [0, 1],
            transform: ['translateY(24px) scale(0.975)', 'translateY(0px) scale(1)'],
          },
          { duration: 0.8, ease: [0.16, 1, 0.3, 1] },
        )
      })

      inView('.v3r-cta-card', (element) => {
        animate(
          element,
          {
            opacity: [0, 1],
            transform: ['translateY(24px) scale(0.98)', 'translateY(0px) scale(1)'],
          },
          { duration: 0.75, ease: [0.16, 1, 0.3, 1] },
        )
      })
    }
  } catch {
    // Fallback gracefully without breaking layout
  }
})

onUnmounted(() => {
  if (typewriterTimer) {
    window.clearTimeout(typewriterTimer)
  }
})
</script>

<template>
  <div ref="rootContainerRef" class="v3rchat-landing-root">
    <!-- Dark Glamorous Wallpaper Pattern Overlay -->
    <div class="v3rchat-landing-pattern" aria-hidden="true" />

    <!-- Precision Architectural Cyber Grid Overlay -->
    <div class="v3rchat-architectural-grid" aria-hidden="true" />

    <!-- Ambient Shadow Syndicate Glows -->
    <div class="v3rchat-ambient-glow glow-top" aria-hidden="true" />
    <div class="v3rchat-ambient-glow glow-center" aria-hidden="true" />
    <div class="v3rchat-ambient-glow glow-bottom" aria-hidden="true" />

    <!-- Top Navigation Header -->
    <header class="v3r-nav-bar">
      <div class="v3r-nav-inner">
        <a :href="dashboardUrl" class="v3r-brand-logo">
          <div class="v3r-logo-badge">
            <img :src="logoUrl" alt="V3RCHAT Logo" class="v3r-brand-icon" />
          </div>
          <span class="v3r-brand-name">{{ appName || 'V3RCHAT' }}</span>
        </a>

        <nav class="v3r-nav-menu">
          <a href="#features" class="v3r-nav-link" @click.prevent="scrollToSection('features')">
            <span class="nav-index">01.</span> Protocols
          </a>
          <a href="#comparison" class="v3r-nav-link" @click.prevent="scrollToSection('comparison')">
            <span class="nav-index">02.</span> Comparison
          </a>
          <a href="#manifesto" class="v3r-nav-link" @click.prevent="scrollToSection('manifesto')">
            <span class="nav-index">03.</span> Manifesto
          </a>
        </nav>

        <div class="v3r-nav-actions">
          <template v-if="user">
            <a :href="dashboardUrl" class="v3r-btn-primary">
              <span>Console</span>
              <svg width="12" height="12" class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </a>
          </template>
          <template v-else>
            <a :href="loginUrl" class="v3r-btn-ghost">Sign In</a>
            <a :href="registerUrl" class="v3r-btn-primary">
              <span>Sign Up</span>
              <svg width="12" height="12" class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </a>
          </template>
        </div>
      </div>
    </header>

    <!-- Main Content Container -->
    <main class="v3r-main-content">
      <!-- HERO SECTION -->
      <section class="v3r-hero-section">
        <!-- Tactical Hero Headline with Sovereign Emblem & Typewriter Engine -->
        <div class="v3r-hero-headline-wrap v3r-hero-reveal" :class="{ 'is-empty-state': isHeadlineEmpty }">
          <div class="v3r-hero-logo-box">
            <img :src="logoUrl" alt="V3RCHAT Sovereign Emblem" class="v3r-hero-logo" />
            <div class="v3r-hero-logo-glow" aria-hidden="true" />
          </div>
          <div class="v3r-hero-title-group">
            <h1 class="v3r-hero-title">
              <span class="sr-only">Sovereign Messaging. Zero-Trace Control.</span>
              <span class="v3r-typewriter-line">{{ displayedLine1 }}</span>
              <span v-if="activeLine === 1 && !isHeadlineEmpty" class="v3r-cursor" :class="{ 'is-blinking': isCursorBlinking }">|</span>
              <template v-if="activeLine === 2 || displayedLine2.length > 0">
                <br />
                <span class="v3r-title-gradient v3r-typewriter-line">{{ displayedLine2 }}</span>
                <span v-if="activeLine === 2" class="v3r-cursor" :class="{ 'is-blinking': isCursorBlinking }">|</span>
              </template>
            </h1>
          </div>
        </div>

        <p class="v3r-hero-subtitle v3r-hero-reveal">
          A syndicate-grade sovereign communications platform. Shielded by asynchronous Reverb WebSockets,
          free from phone number tracking, and enclosed within a high-precision tactical HUD interface.
        </p>

        <div class="v3r-hero-actions v3r-hero-reveal">
          <a :href="user ? dashboardUrl : loginUrl" class="v3r-cta-primary">
            <span>{{ user ? 'Open Chat Console' : 'Access Terminal' }}</span>
            <svg width="16" height="16" class="h-4 w-4 cta-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
          </a>

          <a :href="registerUrl" class="v3r-cta-secondary">
            <span>Create Account</span>
          </a>
        </div>

        <!-- Hero Visual Mockup: Tactical Specular Terminal -->
        <div
          class="v3r-hero-mockup v3r-hero-reveal"
          :style="{
            '--glare-x': heroGlareX,
            '--glare-y': heroGlareY,
            '--glare-opacity': heroGlareOpacity,
            '--rx': heroRotateX,
            '--ry': heroRotateY,
          }"
          @pointermove="handleHeroPointer"
          @pointerleave="handleHeroPointerLeave"
        >
          <!-- Ambient Back-Aura behind Terminal -->
          <div class="v3r-mockup-aura" aria-hidden="true" />

          <div class="v3r-mockup-frame">
            <!-- Top Specular Edge Highlight -->
            <span class="v3r-specular-edge" aria-hidden="true" />
            <span class="liquid-glass-card-glare" aria-hidden="true" />

            <!-- Corner HUD Reticles -->
            <span class="hud-corner tl" aria-hidden="true" />
            <span class="hud-corner tr" aria-hidden="true" />
            <span class="hud-corner bl" aria-hidden="true" />
            <span class="hud-corner br" aria-hidden="true" />

            <!-- Mockup Header: Tactical Channel Header -->
            <div class="v3r-mockup-header">
              <div class="v3r-window-controls">
                <span class="dot close" />
                <span class="dot minimize" />
                <span class="dot expand" />
              </div>

              <div class="v3r-mockup-channel-header">
                <div class="v3r-channel-avatar">
                  <img :src="donAvatarUrl" alt="" class="v3r-avatar-img" />
                  <span class="v3r-online-dot" />
                </div>
                <div class="v3r-channel-info">
                  <div class="v3r-channel-name">
                    <span>Don Salvatore</span>
                    <span class="author-sub">• Capo Console</span>
                  </div>
                  <div class="v3r-channel-sub">
                    <span class="v3r-status-ping" />
                    <span>online</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Mockup Chat Stream (Identical to Chatify Thread) -->
            <div class="v3r-mockup-body">
              <div class="v3r-mock-chat-bg" aria-hidden="true" />

              <!-- Message 1: Incoming (Don Salvatore) -->
              <div class="v3r-mock-msg-row incoming">
                <div class="v3r-mock-avatar">
                  <img :src="donAvatarUrl" alt="" class="v3r-avatar-img" />
                  <i class="avatar-status" />
                </div>
                <div class="v3r-mock-bubble incoming">
                  <div class="v3r-mock-author">
                    <span class="author-name">Don Salvatore <span class="author-sub">• Capo Console</span></span>
                  </div>
                  <p class="v3r-msg-content">
                  Tonight's operation must remain sterile. Route all strategic communications exclusively through this private V3RCHAT node.
                  </p>
                  <div class="v3r-mock-meta">
                    <span class="v3r-meta-time">23:42</span>
                  </div>
                </div>
              </div>

              <!-- Message 2: Outgoing (Operator / You) -->
              <div class="v3r-mock-msg-row outgoing">
                <div class="v3r-mock-bubble outgoing">
                  <div class="v3r-mock-author outgoing">
                    <span class="author-name">Operator (You)</span>
                  </div>
                  <p class="v3r-msg-content">
                    Confirmed. Isolated WebSocket session running on dedicated hardware. In-memory residue is purged instantly.
                  </p>
                  <div class="v3r-mock-meta">
                    <span class="v3r-meta-time">23:43 • Delivered</span>
                    <span class="v3r-double-ticks">✓✓</span>
                  </div>
                </div>
              </div>

              <!-- Message 3: Incoming (Raven Node) -->
              <div class="v3r-mock-msg-row incoming">
                <div class="v3r-mock-avatar">
                  <img :src="ravenAvatarUrl" alt="" class="v3r-avatar-img" />
                  <i class="avatar-status" />
                </div>
                <div class="v3r-mock-bubble incoming">
                  <div class="v3r-mock-author">
                    <span class="author-name">Raven Node <span class="author-sub">• Intel Division</span></span>
                  </div>
                  <p class="v3r-msg-content">
                    Group enclave locked down. No phone numbers, IP traces, or central metadata exposed to external networks.
                  </p>
                  <div class="v3r-mock-meta">
                    <span class="v3r-meta-time">23:45</span>
                  </div>
                </div>
              </div>

              <!-- Dynamic Dispatches (Interactive Outgoing Bubbles) -->
              <div
                v-for="(item, idx) in liveDispatches"
                :key="idx"
                class="v3r-mock-msg-row outgoing v3r-live-msg-enter"
              >
                <div class="v3r-mock-bubble outgoing">
                  <div class="v3r-mock-author outgoing">
                    <span class="author-name">{{ item.author }}</span>
                  </div>

                  <!-- Simulated Voice Note Bubble -->
                  <div v-if="item.isVoice" class="v3r-mock-voice-wrap">
                    <button type="button" class="v3r-mock-voice-play" aria-label="Play voice note">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5v14l11-7z" />
                      </svg>
                    </button>
                    <div class="v3r-mock-voice-bars" aria-hidden="true">
                      <span style="height: 35%" />
                      <span style="height: 65%" />
                      <span style="height: 90%" />
                      <span style="height: 50%" />
                      <span style="height: 80%" />
                      <span style="height: 100%" />
                      <span style="height: 60%" />
                      <span style="height: 85%" />
                      <span style="height: 45%" />
                      <span style="height: 75%" />
                      <span style="height: 90%" />
                      <span style="height: 55%" />
                      <span style="height: 70%" />
                      <span style="height: 40%" />
                      <span style="height: 80%" />
                      <span style="height: 95%" />
                    </div>
                    <span class="v3r-mock-voice-dur">{{ item.duration || '0:18' }}</span>
                  </div>

                  <p v-else class="v3r-msg-content">{{ item.text }}</p>

                  <div class="v3r-mock-meta">
                    <span class="v3r-meta-time">{{ item.time }}</span>
                    <span class="v3r-double-ticks">✓✓</span>
                  </div>
                </div>
              </div>

              <!-- Mockup Bottom Bar: Specular Floating Pill Composer (Transparent Flow) -->
              <div class="v3r-mockup-composer-area">
                <form class="v3r-floating-composer-pill liquid-glass-composer" @submit.prevent="triggerMockDispatch">
                  <span class="liquid-glass-refraction-fringe" aria-hidden="true" />
                  <span class="liquid-glass-specular" aria-hidden="true" />
                  <span class="liquid-glass-glare" aria-hidden="true" />
                  <span class="liquid-glass-glare-opposite" aria-hidden="true" />
                  <span class="liquid-glass-rim" aria-hidden="true" />

                  <!-- Attach Icon Button -->
                  <button
                    type="button"
                    class="v3r-composer-icon-btn"
                    title="Attach file"
                    aria-label="Attach file"
                    @click="triggerMockAttachment"
                  >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48" />
                    </svg>
                  </button>

                  <!-- Floating Input Field -->
                  <input
                    v-model="customDispatchInput"
                    type="text"
                    class="v3r-composer-input-field"
                    placeholder="Transmit secure dispatch... (Press Enter or Send)"
                    aria-label="Simulate dispatch"
                  />

                  <!-- Dynamic Action Button: Send vs VN / Mic Button -->
                  <button
                    v-if="customDispatchInput.trim()"
                    type="submit"
                    class="v3r-composer-action-btn send-mode"
                    title="Transmit Dispatch"
                    aria-label="Send message"
                  >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                    </svg>
                  </button>

                  <button
                    v-else
                    type="button"
                    class="v3r-composer-action-btn mic-mode"
                    title="Transmit Voice Note (VN)"
                    aria-label="Record voice note"
                    @click="triggerVoiceDispatch"
                  >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.91-3c-.49 0-.9.36-.98.85C16.52 14.2 14.47 16 12 16s-4.52-1.8-4.93-4.15c-.08-.49-.49-.85-.98-.85-.61 0-1.09.54-1 1.14.49 3 2.89 5.35 5.91 5.78V20c0 .55.45 1 1 1s1-.45 1-1v-2.08c3.02-.43 5.42-2.78 5.91-5.78.1-.6-.39-1.14-1-1.14z" />
                    </svg>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- COMPARISON SECTION (V3RCHAT vs Ordinary Apps) -->
      <section id="comparison" class="v3r-section v3r-comparison-section">
        <div class="v3r-section-header">
          <div class="v3r-section-badge">
            <span class="badge-line" />
            <span>COMPARATIVE SPECIFICATIONS</span>
            <span class="badge-line" />
          </div>
          <h2 class="v3r-section-title">Why Commercial Platforms Compromise You</h2>
          <p class="v3r-section-subtitle">
            Mainstream messengers are architected to extract and commercialize your metadata. V3RCHAT is engineered for sovereign privacy and operational security.
          </p>
        </div>

        <div class="v3r-comparison-card">
          <span class="v3r-specular-edge" aria-hidden="true" />
          <div class="v3r-table-wrapper">
            <table class="v3r-compare-table">
              <thead>
                <tr>
                  <th class="col-feature">Security Parameter</th>
                  <th class="col-standard">
                    <div class="th-inner">
                      <span class="th-dot compromised" />
                      <span>Commercial Platforms (WA / Telegram)</span>
                    </div>
                  </th>
                  <th class="col-v3r">
                    <div class="th-inner">
                      <span class="th-dot sovereign" />
                      <span>V3RCHAT Syndicate Network</span>
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr class="v3r-compare-row">
                  <td class="feature-name">
                    <span class="row-index">01</span>
                    <span>Identity Coupling</span>
                  </td>
                  <td class="val-standard">
                    Mandatory personal phone number (Vulnerable to SIM swaps, carrier tracking, and leaks)
                  </td>
                  <td class="val-v3r">
                    <span class="badge-v3r">No Phone Required</span>
                    <span class="v3r-val-text">Self-sovereign identity with locally encrypted credentials</span>
                  </td>
                </tr>
                <tr class="v3r-compare-row">
                  <td class="feature-name">
                    <span class="row-index">02</span>
                    <span>Metadata Retention</span>
                  </td>
                  <td class="val-standard">
                    Permanently stored (Who you contact, timestamps, IP geolocation logs)
                  </td>
                  <td class="val-v3r">
                    <span class="badge-v3r">Zero-Trace</span>
                    <span class="v3r-val-text">Pure in-memory buffer with zero commercial metadata logging</span>
                  </td>
                </tr>
                <tr class="v3r-compare-row">
                  <td class="feature-name">
                    <span class="row-index">03</span>
                    <span>Server Sovereignty</span>
                  </td>
                  <td class="val-standard">
                    Centralized corporate cloud subject to foreign jurisdiction, scanning, and unilateral censorship
                  </td>
                  <td class="val-v3r">
                    <span class="badge-v3r">Self-Hosted</span>
                    <span class="v3r-val-text">Deployed on private infrastructure under your unilateral administrative control</span>
                  </td>
                </tr>
                <tr class="v3r-compare-row">
                  <td class="feature-name">
                    <span class="row-index">04</span>
                    <span>Realtime Pipeline</span>
                  </td>
                  <td class="val-standard">
                    Sluggish polling or opaque third-party gateways prone to interception
                  </td>
                  <td class="val-v3r">
                    <span class="badge-v3r">Reverb WebSocket</span>
                    <span class="v3r-val-text">Direct low-latency WebSocket tunnel without intermediary middleware</span>
                  </td>
                </tr>
                <tr class="v3r-compare-row">
                  <td class="feature-name">
                    <span class="row-index">05</span>
                    <span>Interface & Ergonomics</span>
                  </td>
                  <td class="val-standard">
                    Generic, high-glare light layouts optimized for ad consumption and high eye fatigue
                  </td>
                  <td class="val-v3r">
                    <span class="badge-v3r">Tactical Specular HUD</span>
                    <span class="v3r-val-text">Deep-void monochrome interface with precision optical refractions</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- FEATURES / PILLARS OF SECLUSION -->
      <section id="features" class="v3r-section">
        <div class="v3r-section-header">
          <div class="v3r-section-badge">
            <span class="badge-line" />
            <span>ARCHITECTURE & PROTOCOLS</span>
            <span class="badge-line" />
          </div>
          <h2 class="v3r-section-title">Four Pillars of Seclusion</h2>
          <p class="v3r-section-subtitle">
            Tactical engineering designed to safeguard the integrity of mission-critical dispatches.
          </p>
        </div>

        <div class="v3r-features-grid">
          <!-- Card 1 -->
          <div
            class="v3r-feature-card"
            :style="{
              '--glare-x': cardGlares[0]?.x,
              '--glare-y': cardGlares[0]?.y,
              '--glare-opacity': cardGlares[0]?.opacity,
              '--rx': cardGlares[0]?.rx,
              '--ry': cardGlares[0]?.ry,
            }"
            @pointermove="(e) => handleCardPointer(0, e)"
            @pointerleave="() => handleCardPointerLeave(0)"
          >
            <span class="v3r-specular-edge" aria-hidden="true" />
            <span class="liquid-glass-card-glare" aria-hidden="true" />
            <div class="v3r-card-top">
              <div class="v3r-feature-icon-box">
                <svg width="24" height="24" class="v3r-feature-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="9" stroke-width="1.5" stroke-dasharray="4 3" />
                  <circle cx="12" cy="12" r="5" stroke-width="1.5" />
                  <path stroke-linecap="round" stroke-width="1.5" d="M12 3v3m0 12v3M3 12h3m12 0h3" />
                  <circle cx="12" cy="12" r="1.5" fill="currentColor" />
                </svg>
              </div>
              <div class="v3r-feature-num">01 // CIPHER</div>
            </div>
            <h3 class="v3r-feature-title">Asynchronous Zero-Log Channel</h3>
            <p class="v3r-feature-desc">
              Transmissions flow through an encrypted Reverb WebSocket tunnel. Once threads are terminated or deleted,
              all memory buffers are wiped permanently.
            </p>
            <div class="v3r-card-telemetry">
              <span class="telemetry-label">MEMORY RESIDUE</span>
              <span class="telemetry-val">0.00 KB // PURGED</span>
            </div>
          </div>

          <!-- Card 2 -->
          <div
            class="v3r-feature-card"
            :style="{
              '--glare-x': cardGlares[1]?.x,
              '--glare-y': cardGlares[1]?.y,
              '--glare-opacity': cardGlares[1]?.opacity,
              '--rx': cardGlares[1]?.rx,
              '--ry': cardGlares[1]?.ry,
            }"
            @pointermove="(e) => handleCardPointer(1, e)"
            @pointerleave="() => handleCardPointerLeave(1)"
          >
            <span class="v3r-specular-edge" aria-hidden="true" />
            <span class="liquid-glass-card-glare" aria-hidden="true" />
            <div class="v3r-card-top">
              <div class="v3r-feature-icon-box">
                <svg width="24" height="24" class="v3r-feature-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2.5l8 4.6v9.2l-8 4.6-8-4.6V7.1z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 7.5v4.5m0 0l-3.9 2.25m3.9-2.25l3.9 2.25" />
                  <circle cx="12" cy="12" r="1.5" fill="currentColor" />
                  <circle cx="12" cy="7.5" r="1" fill="currentColor" />
                  <circle cx="8.1" cy="14.25" r="1" fill="currentColor" />
                  <circle cx="15.9" cy="14.25" r="1" fill="currentColor" />
                </svg>
              </div>
              <div class="v3r-feature-num">02 // ENCLAVE</div>
            </div>
            <h3 class="v3r-feature-title">Syndicate Group Enclaves</h3>
            <p class="v3r-feature-desc">
              Orchestrate command groups with granular administrative control. Only verified circle members
              can decrypt and participate in strategic briefings.
            </p>
            <div class="v3r-card-telemetry">
              <span class="telemetry-label">ACCESS CLEARANCE</span>
              <span class="telemetry-val">RING-0 // VERIFIED</span>
            </div>
          </div>

          <!-- Card 3 -->
          <div
            class="v3r-feature-card"
            :style="{
              '--glare-x': cardGlares[2]?.x,
              '--glare-y': cardGlares[2]?.y,
              '--glare-opacity': cardGlares[2]?.opacity,
              '--rx': cardGlares[2]?.rx,
              '--ry': cardGlares[2]?.ry,
            }"
            @pointermove="(e) => handleCardPointer(2, e)"
            @pointerleave="() => handleCardPointerLeave(2)"
          >
            <span class="v3r-specular-edge" aria-hidden="true" />
            <span class="liquid-glass-card-glare" aria-hidden="true" />
            <div class="v3r-card-top">
              <div class="v3r-feature-icon-box">
                <svg width="24" height="24" class="v3r-feature-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3L3 10.5 12 21l9-10.5L12 3z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10.5h18" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" stroke-dasharray="2 2" d="M12 3v18" />
                </svg>
              </div>
              <div class="v3r-feature-num">03 // OPTICS</div>
            </div>
            <h3 class="v3r-feature-title">Specular Tactical HUD</h3>
            <p class="v3r-feature-desc">
              Precision optical refractions with real-time specular highlights responding to cursor dynamics.
              Tested monochrome aesthetics designed for sustained focus.
            </p>
            <div class="v3r-card-telemetry">
              <span class="telemetry-label">REFRACTION ENGINE</span>
              <span class="telemetry-val">SPECULAR // 120HZ</span>
            </div>
          </div>

          <!-- Card 4 -->
          <div
            class="v3r-feature-card"
            :style="{
              '--glare-x': cardGlares[3]?.x,
              '--glare-y': cardGlares[3]?.y,
              '--glare-opacity': cardGlares[3]?.opacity,
              '--rx': cardGlares[3]?.rx,
              '--ry': cardGlares[3]?.ry,
            }"
            @pointermove="(e) => handleCardPointer(3, e)"
            @pointerleave="() => handleCardPointerLeave(3)"
          >
            <span class="v3r-specular-edge" aria-hidden="true" />
            <span class="liquid-glass-card-glare" aria-hidden="true" />
            <div class="v3r-card-top">
              <div class="v3r-feature-icon-box">
                <svg width="24" height="24" class="v3r-feature-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2 12h4l2.5-6 4 12 3.5-8 2 4h4" />
                  <circle cx="8.5" cy="6" r="1" fill="currentColor" />
                  <circle cx="12.5" cy="18" r="1" fill="currentColor" />
                </svg>
              </div>
              <div class="v3r-feature-num">04 // REALTIME</div>
            </div>
            <h3 class="v3r-feature-title">Sub-10ms Execution Engine</h3>
            <p class="v3r-feature-desc">
              Optimized without runtime overhead. Framer Motion runs directly via GPU compositor hardware
              acceleration—fluid, ultra-responsive, and power efficient.
            </p>
            <div class="v3r-card-telemetry">
              <span class="telemetry-label">PIPELINE LATENCY</span>
              <span class="telemetry-val">&lt; 10MS // GPU DIRECT</span>
            </div>
          </div>
        </div>
      </section>

      <!-- CREED / MANIFESTO SECTION -->
      <section id="manifesto" class="v3r-section">
        <div class="v3r-manifesto-box">
          <div class="v3r-manifesto-inner">

            <div class="v3r-quote-icon">“</div>
            <blockquote class="v3r-manifesto-quote">
              The ultimate law of power is absolute control over your intelligence. Never entrust your critical secrets
              to platforms whose core business model is indexing your data. Own your channels, operate in the shadows,
              and let the outcome speak for itself.
            </blockquote>
            <div class="v3r-manifesto-author">
              <span class="author-name">— SYNDICATE HIGH COUNCIL</span>
              <span class="author-role">V3RCHAT SOVEREIGN MANIFESTO</span>
            </div>
          </div>
        </div>
      </section>

      <!-- BOTTOM CTA BANNER -->
      <section class="v3r-cta-banner">
        <div class="v3r-cta-card">
          <h2 class="v3r-cta-title">Ready to Reclaim Complete Communications Sovereignty?</h2>
          <p class="v3r-cta-subtitle">
            Enter the V3RCHAT syndicate network today and experience the new benchmark for private tactical messaging.
          </p>
          <div class="v3r-cta-buttons">
            <a :href="user ? dashboardUrl : loginUrl" class="v3r-cta-primary">
              <span>{{ user ? 'Return to Console' : 'Launch V3RCHAT Terminal' }}</span>
              <svg width="16" height="16" class="h-4 w-4 cta-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </a>
            <a :href="registerUrl" class="v3r-cta-secondary">
              <span>Create Account</span>
            </a>
          </div>
        </div>
      </section>
    </main>

    <!-- FOOTER -->
    <footer class="v3r-footer">
      <div class="v3r-footer-inner">
        <div class="v3r-footer-brand">
          <img :src="logoUrl" alt="V3RCHAT Logo" class="v3r-footer-logo" />
          <div class="v3r-footer-brand-info">
            <span class="brand-text">{{ appName || 'V3RCHAT' }}</span>
            <span class="brand-sub">SOVEREIGN CIPHER LABS // ZERO-LOG DIRECTIVE</span>
          </div>
        </div>

        <div class="v3r-footer-links">
          <a :href="loginUrl" class="footer-link">Terminal Login</a>
          <a :href="registerUrl" class="footer-link">Registration</a>
          <a :href="dashboardUrl" class="footer-link">Live Console</a>
        </div>

        <div class="v3r-footer-status">
          <span class="status-node" />
          <span>ALL NODES OPERATIONAL • ZERO METADATA EXPOSURE</span>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* ==========================================================================
   V3RCHAT SOVEREIGN SYNDICATE LANDING PAGE STYLES (COMPETITION EDITION)
   Deep Obsidian #000000, Specular HUD, 3D Perspective Optics
   ========================================================================== */

.v3rchat-landing-root {
  position: fixed;
  inset: 0;
  width: 100%;
  height: 100%;
  min-height: 100vh;
  min-height: 100dvh;
  overflow-y: auto;
  overflow-x: hidden;
  -webkit-overflow-scrolling: touch;
  scroll-behavior: smooth;
  user-select: text;
  -webkit-user-select: text;
  background-color: #000000;
  color: #f4f4f5;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  line-height: 1.5;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  z-index: 10;
}

/* Custom Sleek Scrollbar */
.v3rchat-landing-root::-webkit-scrollbar {
  width: 6px;
}

.v3rchat-landing-root::-webkit-scrollbar-track {
  background: #000000;
}

.v3rchat-landing-root::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.16);
  border-radius: 9999px;
}

.v3rchat-landing-root::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.35);
}

/* Background Glamorous Pattern */
.v3rchat-landing-pattern {
  position: fixed;
  inset: 0;
  background-image: url('/vendor/chatify/patterns/glamorous.svg');
  background-repeat: repeat;
  background-size: 320px;
  opacity: 0.04;
  pointer-events: none;
  z-index: 0;
}

/* Architectural Precision Cyber Grid */
.v3rchat-architectural-grid {
  position: fixed;
  inset: 0;
  background-size: 64px 64px;
  background-image:
    linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
  mask-image: radial-gradient(ellipse 80% 60% at 50% 25%, #000 35%, transparent 100%);
  -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 25%, #000 35%, transparent 100%);
  pointer-events: none;
  z-index: 0;
}

/* Ambient Backlights */
.v3rchat-ambient-glow {
  position: fixed;
  border-radius: 50%;
  pointer-events: none;
  filter: blur(150px);
  z-index: 0;
}

.glow-top {
  width: 760px;
  height: 460px;
  top: -230px;
  left: 50%;
  transform: translateX(-50%);
  opacity: 0.32;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.18) 0%, rgba(63, 63, 70, 0.25) 45%, transparent 70%);
}

.glow-center {
  width: 680px;
  height: 680px;
  top: 38%;
  left: -240px;
  opacity: 0.14;
  background: radial-gradient(circle, rgba(16, 185, 129, 0.18) 0%, rgba(24, 24, 27, 0.2) 55%, transparent 75%);
}

.glow-bottom {
  width: 640px;
  height: 640px;
  bottom: -240px;
  right: -120px;
  opacity: 0.2;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, rgba(24, 24, 27, 0.35) 50%, transparent 70%);
}

/* Floating Architectural Navigation Header */
.v3r-nav-bar {
  position: sticky;
  top: 0.85rem;
  width: calc(100% - 2rem);
  max-width: 1140px;
  margin: 0.85rem auto 0;
  z-index: 50;
  border-radius: 9999px;
  backdrop-filter: blur(28px) saturate(1.9);
  -webkit-backdrop-filter: blur(28px) saturate(1.9);
  background: rgba(9, 9, 11, 0.72);
  border: 1px solid rgba(255, 255, 255, 0.11);
  box-shadow:
    0 16px 36px -8px rgba(0, 0, 0, 0.75),
    inset 0 1px 0 rgba(255, 255, 255, 0.22);
  transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

.v3r-nav-bar:hover {
  border-color: rgba(255, 255, 255, 0.18);
}

.v3r-nav-inner {
  padding: 0.55rem 0.85rem 0.55rem 1.15rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.v3r-brand-logo {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  text-decoration: none;
  color: #ffffff;
}

.v3r-logo-badge {
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  box-shadow: none;
  flex-shrink: 0;
}

.v3r-brand-icon {
  width: 1.65rem;
  height: 1.65rem;
  object-fit: contain;
  display: block;
  filter: drop-shadow(0 2px 8px rgba(255, 255, 255, 0.22));
}

.v3r-brand-name {
  font-size: 0.925rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: #ffffff;
}

.v3r-brand-tag {
  font-size: 0.58rem;
  font-weight: 700;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  letter-spacing: 0.14em;
  color: #a1a1aa;
  padding: 0.15rem 0.45rem;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.v3r-nav-menu {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  padding: 0.22rem;
  border-radius: 9999px;
}

.v3r-nav-link {
  color: #a1a1aa;
  text-decoration: none;
  font-size: 0.78rem;
  font-weight: 500;
  letter-spacing: 0.02em;
  padding: 0.32rem 0.85rem;
  border-radius: 9999px;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.nav-index {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.65rem;
  color: #52525b;
  transition: color 0.2s ease;
}

.v3r-nav-link:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.07);
}

.v3r-nav-link:hover .nav-index {
  color: #10b981;
}

.v3r-nav-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.v3r-nav-telemetry {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.65rem;
  color: #71717a;
  padding: 0.28rem 0.6rem;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.07);
  margin-right: 0.25rem;
}

.telemetry-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 6px #10b981;
}

/* Micro-Pill Navbar Buttons */
.v3r-btn-ghost {
  color: #d4d4d8;
  text-decoration: none;
  font-size: 0.75rem;
  font-weight: 500;
  padding: 0.42rem 0.85rem;
  border-radius: 9999px;
  transition: all 0.18s ease;
  line-height: 1;
}

.v3r-btn-ghost:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.08);
}

.v3r-btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: #000000;
  background: #ffffff;
  text-decoration: none;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.42rem 0.95rem;
  border-radius: 9999px;
  border: 1px solid #ffffff;
  box-shadow:
    0 2px 6px rgba(0, 0, 0, 0.35),
    0 0 16px rgba(255, 255, 255, 0.22);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  line-height: 1;
}

.v3r-btn-primary:hover {
  background: #f4f4f5;
  transform: translateY(-1px) scale(1.02);
  box-shadow:
    0 4px 12px rgba(0, 0, 0, 0.4),
    0 0 24px rgba(255, 255, 255, 0.38);
}

.v3r-btn-primary svg {
  width: 0.75rem;
  height: 0.75rem;
  flex-shrink: 0;
}

/* Main Container */
.v3r-main-content {
  position: relative;
  z-index: 2;
  max-width: 1140px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

/* Hero Section */
.v3r-hero-section {
  padding: 4.75rem 0 4rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.v3r-hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  font-size: 0.675rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  color: #e4e4e7;
  padding: 0.35rem 0.95rem;
  border-radius: 9999px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.03) 100%);
  border: 1px solid rgba(255, 255, 255, 0.16);
  box-shadow:
    0 8px 20px -4px rgba(0, 0, 0, 0.6),
    inset 0 1px 0 rgba(255, 255, 255, 0.25);
  margin-bottom: 1.5rem;
}

.v3r-badge-divider {
  width: 1px;
  height: 10px;
  background: rgba(255, 255, 255, 0.2);
}

.v3r-badge-cipher {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  color: #10b981;
  letter-spacing: 0.1em;
}

.v3r-pulse-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 8px #10b981;
  animation: pulse-ring 2s infinite ease-in-out;
}

@keyframes pulse-ring {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.4; transform: scale(0.82); }
}

.v3r-hero-headline-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1.75rem;
  margin: 0 auto 1.5rem;
  max-width: 980px;
  position: relative;
  transition: gap 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}

.v3r-hero-logo-box {
  position: relative;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}

.v3r-hero-logo {
  width: 8.5rem;
  height: 8.5rem;
  object-fit: contain;
  position: relative;
  z-index: 2;
  filter: drop-shadow(0 0 24px rgba(255, 255, 255, 0.22)) drop-shadow(0 12px 28px rgba(0, 0, 0, 0.85));
  transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}

.v3r-hero-logo:hover {
  transform: scale(1.05) rotate(-2deg);
}

.v3r-hero-logo-glow {
  position: absolute;
  inset: -12px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.16) 0%, rgba(16, 185, 129, 0.12) 40%, transparent 70%);
  border-radius: 50%;
  filter: blur(14px);
  z-index: 1;
  pointer-events: none;
}

.v3r-hero-title-group {
  text-align: left;
  transition: opacity 0.3s ease, max-width 0.45s cubic-bezier(0.16, 1, 0.3, 1), transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}

/* When headline is completely erased, logo is centered in the middle */
.v3r-hero-headline-wrap.is-empty-state {
  gap: 0;
}

.v3r-hero-headline-wrap.is-empty-state .v3r-hero-title-group {
  max-width: 0;
  opacity: 0;
  overflow: hidden;
  pointer-events: none;
  transform: translateX(10px);
}

.v3r-hero-headline-wrap.is-empty-state .v3r-hero-logo {
  transform: scale(1.15);
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

.v3r-hero-title {
  font-size: clamp(2rem, 4.4vw, 3.85rem);
  font-weight: 800;
  letter-spacing: -0.04em;
  line-height: 1.12;
  margin: 0;
  color: #ffffff;
  min-height: 2.24em; /* Preserves 2 lines height preventing any CLS layout jump */
  text-shadow: 0 12px 40px rgba(0, 0, 0, 0.8);
}

.v3r-typewriter-line {
  display: inline;
}

.v3r-cursor {
  display: inline-block;
  margin-left: 3px;
  color: #ffffff;
  font-weight: 200;
  font-family: ui-monospace, SFMono-Regular, monospace;
  opacity: 1;
  vertical-align: baseline;
}

.v3r-cursor.is-blinking {
  animation: v3r-blink 0.9s steps(2, start) infinite;
}

@keyframes v3r-blink {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0;
  }
}

.v3r-title-gradient {
  background: linear-gradient(180deg, #ffffff 15%, #a1a1aa 65%, #52525b 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.v3r-hero-subtitle {
  max-width: 640px;
  font-size: 1.025rem;
  line-height: 1.68;
  color: #a1a1aa;
  margin: 0 0 2.15rem;
}

.v3r-hero-subtitle em {
  color: #f4f4f5;
  font-style: normal;
  font-weight: 500;
  border-bottom: 1px dashed rgba(255, 255, 255, 0.3);
}

.v3r-hero-actions {
  display: flex;
  align-items: center;
  gap: 0.95rem;
  margin-bottom: 2.25rem;
  flex-wrap: wrap;
  justify-content: center;
}

/* Refined, Sleek Action Buttons */
.v3r-cta-primary {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  color: #000000;
  background: linear-gradient(180deg, #ffffff 0%, #e4e4e7 100%);
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  padding: 0.68rem 1.55rem;
  border-radius: 9999px;
  border: 1px solid #ffffff;
  box-shadow:
    0 2px 4px rgba(0, 0, 0, 0.4),
    0 8px 24px rgba(255, 255, 255, 0.2),
    inset 0 1px 0 #ffffff;
  transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
  cursor: pointer;
  white-space: nowrap;
}

.cta-arrow {
  width: 1rem;
  height: 1rem;
  flex-shrink: 0;
  transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.v3r-cta-primary:hover {
  background: #ffffff;
  transform: translateY(-2px);
  box-shadow:
    0 4px 8px rgba(0, 0, 0, 0.45),
    0 12px 32px rgba(255, 255, 255, 0.32),
    inset 0 1px 0 #ffffff;
}

.v3r-cta-primary:hover .cta-arrow {
  transform: translateX(3px);
}

.v3r-cta-primary:active {
  transform: translateY(0);
}

.v3r-cta-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  color: #e4e4e7;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.16);
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 600;
  letter-spacing: -0.01em;
  padding: 0.68rem 1.55rem;
  border-radius: 9999px;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  box-shadow:
    0 4px 14px rgba(0, 0, 0, 0.4),
    inset 0 1px 0 rgba(255, 255, 255, 0.14);
  transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
  cursor: pointer;
  white-space: nowrap;
}

.v3r-cta-secondary:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.32);
  color: #ffffff;
  transform: translateY(-2px);
}

.v3r-cta-secondary:active {
  transform: translateY(0);
}

/* System Status Pill */
.v3r-system-status {
  display: inline-flex;
  align-items: center;
  gap: 1.1rem;
  background: rgba(14, 14, 17, 0.88);
  border: 1px solid rgba(255, 255, 255, 0.11);
  box-shadow:
    0 10px 24px -6px rgba(0, 0, 0, 0.65),
    inset 0 1px 0 rgba(255, 255, 255, 0.12);
  padding: 0.5rem 1.25rem;
  border-radius: 9999px;
  font-size: 0.7rem;
  font-weight: 500;
  letter-spacing: 0.04em;
  color: #a1a1aa;
  margin-bottom: 3.25rem;
  flex-wrap: wrap;
  justify-content: center;
}

.v3r-status-item {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.v3r-status-item strong {
  color: #ffffff;
  font-weight: 600;
}

.v3r-status-divider {
  width: 1px;
  height: 11px;
  background: rgba(255, 255, 255, 0.15);
}

.v3r-status-indicator.online {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 6px #10b981;
}

/* Hero Mockup (Tactical Terminal Frame with 3D Perspective) */
.v3r-hero-mockup {
  position: relative;
  width: 100%;
  max-width: 890px;
  perspective: 1200px;
}

.v3r-mockup-aura {
  position: absolute;
  inset: -16px;
  background: radial-gradient(
    circle at var(--glare-x, 50%) var(--glare-y, 25%),
    rgba(255, 255, 255, 0.13),
    rgba(16, 185, 129, 0.05) 45%,
    transparent 70%
  );
  filter: blur(28px);
  z-index: 0;
  pointer-events: none;
  transition: opacity 0.3s ease;
}

.v3r-mockup-frame {
  position: relative;
  z-index: 1;
  background: linear-gradient(165deg, rgba(20, 20, 24, 0.88) 0%, rgba(9, 9, 11, 0.94) 100%);
  border-radius: 1.15rem;
  border: 1px solid rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(32px) saturate(1.9);
  -webkit-backdrop-filter: blur(32px) saturate(1.9);
  box-shadow:
    0 32px 64px -16px rgba(0, 0, 0, 0.85),
    0 16px 32px -12px rgba(0, 0, 0, 0.9),
    inset 0 1px 1px rgba(255, 255, 255, 0.32),
    inset 0 -1px 1px rgba(0, 0, 0, 0.7);
  overflow: hidden;
  text-align: left;
  transform: rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg));
  transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.25s ease;
  will-change: transform;
}

.v3r-hero-mockup:hover .v3r-mockup-frame {
  border-color: rgba(255, 255, 255, 0.26);
}

/* Specular Top Light Beam */
.v3r-specular-edge {
  position: absolute;
  top: 0;
  left: 10%;
  right: 10%;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.65), transparent);
  pointer-events: none;
  z-index: 3;
}

/* Unified Glare Engine (Supports both Hero & Feature Cards) */
.liquid-glass-card-glare {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: radial-gradient(
    circle 360px at var(--glare-x, 50%) var(--glare-y, 30%),
    rgba(255, 255, 255, 0.15) 0%,
    rgba(255, 255, 255, 0.03) 45%,
    transparent 70%
  );
  opacity: var(--glare-opacity, 0.35);
  transition: opacity 0.25s ease;
  z-index: 1;
}

/* Tactical HUD Corner Reticles */
.hud-corner {
  position: absolute;
  width: 8px;
  height: 8px;
  border-color: rgba(255, 255, 255, 0.28);
  pointer-events: none;
  z-index: 4;
}

.hud-corner.tl { top: 8px; left: 8px; border-top: 1px solid; border-left: 1px solid; }
.hud-corner.tr { top: 8px; right: 8px; border-top: 1px solid; border-right: 1px solid; }
.hud-corner.bl { bottom: 8px; left: 8px; border-bottom: 1px solid; border-left: 1px solid; }
.hud-corner.br { bottom: 8px; right: 8px; border-bottom: 1px solid; border-right: 1px solid; }

.v3r-mockup-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1.15rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(10, 10, 12, 0.75);
  position: relative;
  z-index: 3;
  gap: 0.75rem;
}

.v3r-window-controls {
  display: flex;
  gap: 0.45rem;
  flex-shrink: 0;
}

.v3r-window-controls .dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
}

.v3r-window-controls .dot.close { background: #ff5f56; box-shadow: 0 0 6px rgba(255, 95, 86, 0.4); }
.v3r-window-controls .dot.minimize { background: #ffbd2e; box-shadow: 0 0 6px rgba(255, 189, 46, 0.4); }
.v3r-window-controls .dot.expand { background: #27c93f; box-shadow: 0 0 6px rgba(39, 201, 63, 0.4); }

.v3r-mockup-channel-header {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex: 1;
  min-width: 0;
}

.v3r-channel-avatar {
  position: relative;
  width: 2rem;
  height: 2rem;
  border-radius: 9999px;
  background: linear-gradient(135deg, #27272a 0%, #18181b 100%);
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.65rem;
  font-weight: 700;
  color: #ffffff;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
}

.v3r-avatar-img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  display: block;
}

.v3r-online-dot {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #10b981;
  border: 1px solid #000000;
  box-shadow: 0 0 4px #10b981;
  z-index: 2;
}

.v3r-channel-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.v3r-channel-name {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #ffffff;
  letter-spacing: -0.01em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-family: var(--chatify-font-family, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif);
}

.v3r-channel-name .author-sub {
  color: #a1a1aa;
  font-size: 0.72rem;
  font-weight: 400;
}

.v3r-badge-enc {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.58rem;
  font-weight: 700;
  color: #10b981;
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.28);
  padding: 0.08rem 0.35rem;
  border-radius: 9999px;
}

.v3r-channel-sub {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.65rem;
  color: #a1a1aa;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.v3r-status-ping {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 6px #10b981;
  animation: pulse 1.8s infinite;
}

.v3r-mockup-header-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.v3r-cipher-badge {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.6rem;
  font-weight: 700;
  color: #a1a1aa;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 0.2rem 0.55rem;
  border-radius: 6px;
  letter-spacing: 0.05em;
}

/* Chat Body (Identical to Chatify App Thread) */
.v3r-mockup-body {
  position: relative;
  padding: 1.25rem 1.35rem 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.95rem;
  z-index: 2;
  min-height: 440px;
  background: #09090b;
}

.v3r-mock-chat-bg {
  position: absolute;
  inset: 0;
  background-color: #ffffff;
  opacity: 0.06;
  mask-image: url(/vendor/chatify/patterns/glamorous.svg);
  -webkit-mask-image: url(/vendor/chatify/patterns/glamorous.svg);
  mask-repeat: repeat;
  -webkit-mask-repeat: repeat;
  mask-size: 130px;
  -webkit-mask-size: 130px;
  pointer-events: none;
  z-index: 0;
}

.v3r-mock-msg-row {
  position: relative;
  z-index: 1;
  display: flex;
  gap: 0.65rem;
  max-width: 84%;
}

.v3r-mock-msg-row.incoming {
  align-self: flex-start;
  align-items: flex-end;
}

.v3r-mock-msg-row.outgoing {
  align-self: flex-end;
  justify-content: flex-end;
}

.v3r-live-msg-enter {
  animation: dispatch-pop 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes dispatch-pop {
  from {
    opacity: 0;
    transform: translateY(12px) scale(0.96);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.v3r-mock-avatar {
  position: relative;
  width: 1.85rem;
  height: 1.85rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #27272a, #18181b);
  border: 1px solid rgba(255, 255, 255, 0.18);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.625rem;
  font-weight: 700;
  color: #ffffff;
  flex-shrink: 0;
  margin-bottom: 0.15rem;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
}

.avatar-status {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #10b981;
  border: 1px solid #000000;
  z-index: 2;
}

/* Chat Bubbles: Exact WhatsApp / Chatify Shapes */
.v3r-mock-bubble {
  min-width: 0;
  overflow: hidden;
  padding: 0.55rem 0.9rem;
  font-size: 0.875rem;
  line-height: 1.45;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
  transition: transform 0.18s ease;
  font-family: var(--chatify-font-family, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif);
}

.v3r-mock-bubble:hover {
  transform: translateY(-1px);
}

.v3r-mock-bubble.incoming {
  border-radius: 1.15rem 1.15rem 1.15rem 0.35rem;
  background: #18181b;
  border: 1px solid rgba(255, 255, 255, 0.09);
  color: #ffffff;
}

.v3r-mock-bubble.outgoing {
  border-radius: 1.15rem 1.15rem 0.35rem 1.15rem;
  background: #27272a;
  border: 1px solid rgba(255, 255, 255, 0.14);
  color: #ffffff;
  box-shadow:
    0 4px 14px rgba(0, 0, 0, 0.4),
    inset 0 1px 0 rgba(255, 255, 255, 0.12);
}

/* Authentic Chatify Sender Name Typography */
.v3r-mock-author {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.65rem;
  margin-bottom: 0.25rem;
  line-height: 1.25;
}

.v3r-mock-bubble.incoming .author-name {
  font-family: var(--chatify-font-family, system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif);
  font-size: 0.75rem; /* 12px exact Chatify text-xs */
  font-weight: 600;
  color: #e4e4e7;
  letter-spacing: -0.01em;
}

.author-sub {
  font-family: var(--chatify-font-family, system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif);
  font-size: 0.72rem;
  font-weight: 400;
  color: #a1a1aa;
}

.v3r-mock-bubble.outgoing .author-name {
  font-family: var(--chatify-font-family, system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif);
  font-size: 0.75rem; /* 12px exact Chatify text-xs */
  font-weight: 600;
  color: #e4e4e7;
  letter-spacing: -0.01em;
}

.v3r-msg-hash {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.6rem;
  font-weight: 600;
  color: #71717a;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 0.06rem 0.35rem;
  border-radius: 4px;
  flex-shrink: 0;
  margin-left: auto;
}

.v3r-msg-content {
  margin: 0;
  font-size: 0.875rem; /* 14px exact Chatify text-sm / body */
  line-height: 1.45;
  color: #ffffff;
  white-space: pre-wrap;
  word-break: break-word;
  font-weight: 400;
}

.v3r-mock-meta {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.3rem;
  margin-top: 0.25rem;
  font-size: 0.625rem;
  color: #71717a;
}

.v3r-mock-bubble.outgoing .v3r-mock-meta {
  color: #a1a1aa;
}

.v3r-meta-time {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.v3r-meta-tag {
  font-size: 0.6rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  opacity: 0.85;
}

.v3r-double-ticks {
  color: #53bdeb;
  font-weight: 700;
  font-size: 0.72rem;
  letter-spacing: -0.1em;
  margin-inline-start: 0.15rem;
}

/* Simulated Voice Note UI */
.v3r-mock-voice-wrap {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.2rem 0;
}

.v3r-mock-voice-play {
  width: 1.95rem;
  height: 1.95rem;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  cursor: pointer;
  flex-shrink: 0;
  transition: transform 0.15s ease, background-color 0.15s ease;
}

.v3r-mock-voice-play:hover {
  transform: scale(1.08);
  background: rgba(255, 255, 255, 0.24);
}

.v3r-mock-voice-play svg {
  width: 12px !important;
  height: 12px !important;
  min-width: 12px !important;
  min-height: 12px !important;
  max-width: 12px !important;
  max-height: 12px !important;
  display: block;
  flex-shrink: 0;
}

.v3r-mock-voice-bars {
  display: flex;
  align-items: center;
  gap: 2px;
  height: 1.35rem;
  flex: 1;
  min-width: 90px;
}

.v3r-mock-voice-bars span {
  flex: 1;
  width: 2.5px;
  background: #ffffff;
  border-radius: 9999px;
  opacity: 0.75;
}

.v3r-mock-voice-dur {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.65rem;
  color: #d4d4d8;
  flex-shrink: 0;
}

/* Floating Composer: Specular Floating Pill (Transparent Backing) */
.v3r-mockup-composer-area {
  position: relative;
  z-index: 3;
  padding: 0.85rem 0 0.25rem;
  margin-top: auto;
  background: transparent !important;
}

.v3r-floating-composer-pill {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.45rem;
  min-height: 3.25rem;
  padding: 0.35rem 0.65rem;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.04);
  backdrop-filter: blur(24px) saturate(1.8) contrast(1.05);
  -webkit-backdrop-filter: blur(24px) saturate(1.8) contrast(1.05);
  border: 1px solid rgba(255, 255, 255, 0.16);
  box-shadow:
    0 16px 40px -10px rgba(0, 0, 0, 0.65),
    0 4px 16px rgba(0, 0, 0, 0.3),
    inset 0 1px 1px rgba(255, 255, 255, 0.25);
  overflow: hidden;
  user-select: none;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}

.v3r-floating-composer-pill:focus-within {
  border-color: rgba(255, 255, 255, 0.35);
  box-shadow:
    0 16px 44px -8px rgba(0, 0, 0, 0.85),
    0 0 0 3px rgba(255, 255, 255, 0.08),
    inset 0 1px 1px rgba(255, 255, 255, 0.35);
}

.v3r-composer-icon-btn {
  display: inline-flex;
  height: 2.25rem;
  width: 2.25rem;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  background: transparent;
  border: none;
  color: #a1a1aa;
  cursor: pointer;
  transition: background-color 0.15s ease, color 0.15s ease, transform 0.15s ease;
}

.v3r-composer-icon-btn:hover {
  background-color: rgba(255, 255, 255, 0.08);
  color: #ffffff;
  transform: scale(1.06);
}

.v3r-composer-icon-btn svg,
.v3r-composer-action-btn svg {
  width: 18px !important;
  height: 18px !important;
  min-width: 18px !important;
  min-height: 18px !important;
  max-width: 18px !important;
  max-height: 18px !important;
  display: block;
  flex-shrink: 0;
}

.v3r-composer-cmd-prefix {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.65rem;
  font-weight: 700;
  color: #10b981;
  letter-spacing: 0.08em;
  padding: 0.1rem 0.25rem;
  user-select: none;
  flex-shrink: 0;
}

.v3r-composer-input-field {
  flex: 1;
  min-width: 0;
  background: transparent;
  border: none;
  outline: none;
  color: #ffffff;
  font-size: 0.85rem;
  font-family: inherit;
  padding: 0.45rem 0.5rem;
}

.v3r-composer-input-field::placeholder {
  color: #71717a;
}

.v3r-composer-e2ee-tag {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.58rem;
  font-weight: 700;
  color: #a1a1aa;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
  letter-spacing: 0.06em;
  user-select: none;
  flex-shrink: 0;
  margin-right: 0.2rem;
}

.v3r-composer-action-btn {
  display: inline-flex;
  height: 2.35rem;
  width: 2.35rem;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  border: none;
  background: #ffffff;
  color: #000000;
  cursor: pointer;
  box-shadow:
    0 0 2px 1px rgba(255, 255, 255, 0.65) inset,
    0 2px 10px rgba(0, 0, 0, 0.4);
  transition: transform 0.16s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.16s ease, box-shadow 0.16s ease;
}

.v3r-composer-action-btn:hover {
  transform: translateY(-1px) scale(1.06);
  background: #f4f4f5;
  box-shadow:
    0 0 3px 1.5px rgba(255, 255, 255, 0.85) inset,
    0 4px 16px rgba(0, 0, 0, 0.5);
}

.v3r-composer-action-btn:active {
  transform: scale(0.96);
}

/* Sections Shared */
.v3r-section {
  padding: 5.25rem 0;
}

.v3r-section-header {
  text-align: center;
  max-width: 680px;
  margin: 0 auto 3.25rem;
}

.v3r-section-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.65rem;
  font-size: 0.6875rem;
  font-weight: 700;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  letter-spacing: 0.16em;
  color: #a1a1aa;
  margin-bottom: 0.85rem;
}

.badge-line {
  width: 24px;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.35), transparent);
}

.v3r-section-title {
  font-size: clamp(1.75rem, 3.5vw, 2.5rem);
  font-weight: 800;
  letter-spacing: -0.03em;
  color: #ffffff;
  margin: 0 0 0.95rem;
}

.v3r-section-subtitle {
  font-size: 0.95rem;
  line-height: 1.6;
  color: #a1a1aa;
  margin: 0;
}

/* Comparison Matrix Table */
.v3r-comparison-card {
  position: relative;
  background: linear-gradient(165deg, rgba(18, 18, 22, 0.82) 0%, rgba(10, 10, 12, 0.9) 100%);
  border-radius: 1.25rem;
  border: 1px solid rgba(255, 255, 255, 0.14);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  box-shadow:
    0 24px 48px -12px rgba(0, 0, 0, 0.75),
    inset 0 1px 1px rgba(255, 255, 255, 0.22);
  overflow: hidden;
}

.v3r-table-wrapper {
  overflow-x: auto;
}

.v3r-compare-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.v3r-compare-table th,
.v3r-compare-table td {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.07);
}

.v3r-compare-row {
  transition: background-color 0.2s ease;
}

.v3r-compare-row:hover {
  background: rgba(255, 255, 255, 0.025);
}

.v3r-compare-row:last-child td {
  border-bottom: none;
}

.v3r-compare-table th {
  font-size: 0.7rem;
  font-weight: 700;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  letter-spacing: 0.11em;
  color: #a1a1aa;
  text-transform: uppercase;
  background: rgba(0, 0, 0, 0.5);
}

.th-inner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.th-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.th-dot.compromised {
  background: #ef4444;
  box-shadow: 0 0 6px rgba(239, 68, 68, 0.6);
}

.th-dot.sovereign {
  background: #10b981;
  box-shadow: 0 0 8px rgba(16, 185, 129, 0.8);
}

.v3r-compare-table th.col-v3r {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.05);
  border-left: 1px solid rgba(255, 255, 255, 0.09);
}

.feature-name {
  font-weight: 600;
  color: #ffffff;
  font-size: 0.875rem;
  white-space: nowrap;
}

.row-index {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.68rem;
  color: #52525b;
  margin-right: 0.6rem;
}

.val-standard {
  color: #71717a;
  font-size: 0.825rem;
  line-height: 1.5;
}

.val-v3r {
  color: #f4f4f5;
  font-size: 0.825rem;
  font-weight: 500;
  line-height: 1.5;
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.035) 0%, rgba(255, 255, 255, 0.01) 100%);
  border-left: 1px solid rgba(255, 255, 255, 0.09);
}

.badge-v3r {
  display: inline-block;
  font-size: 0.625rem;
  font-weight: 800;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #000000;
  background: #ffffff;
  padding: 0.16rem 0.5rem;
  border-radius: 5px;
  margin-right: 0.55rem;
  box-shadow: 0 2px 8px rgba(255, 255, 255, 0.2);
}

/* Features Grid (Bento Specular Cards) */
.v3r-features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(245px, 1fr));
  gap: 1.35rem;
  perspective: 1000px;
}

.v3r-feature-card {
  position: relative;
  display: flex;
  flex-direction: column;
  background: linear-gradient(165deg, rgba(22, 22, 26, 0.74) 0%, rgba(12, 12, 14, 0.86) 100%);
  border: 1px solid rgba(255, 255, 255, 0.13);
  border-radius: 1.15rem;
  padding: 1.85rem 1.65rem;
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  box-shadow:
    0 16px 32px -8px rgba(0, 0, 0, 0.6),
    inset 0 1px 1px rgba(255, 255, 255, 0.22);
  transform: rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg));
  transition:
    transform 0.2s cubic-bezier(0.16, 1, 0.3, 1),
    border-color 0.25s ease,
    box-shadow 0.25s ease;
  overflow: hidden;
  will-change: transform;
}

.v3r-feature-card:hover {
  border-color: rgba(255, 255, 255, 0.3);
  box-shadow:
    0 24px 48px -12px rgba(0, 0, 0, 0.8),
    inset 0 1px 1px rgba(255, 255, 255, 0.35);
}

.v3r-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.35rem;
  position: relative;
  z-index: 2;
}

.v3r-feature-icon-box {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  min-width: 24px;
  min-height: 24px;
  background: transparent;
  border: none;
  box-shadow: none;
}

.v3r-feature-icon-box svg,
.v3r-feature-svg {
  width: 24px !important;
  height: 24px !important;
  min-width: 24px !important;
  min-height: 24px !important;
  display: block;
  stroke: #ffffff;
  color: #ffffff;
  flex-shrink: 0;
}

.v3r-feature-num {
  font-size: 0.675rem;
  font-weight: 700;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  letter-spacing: 0.14em;
  color: #71717a;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.07);
}

.v3r-feature-title {
  position: relative;
  z-index: 2;
  font-size: 1.1rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  color: #ffffff;
  margin: 0 0 0.7rem;
}

.v3r-feature-desc {
  position: relative;
  z-index: 2;
  font-size: 0.835rem;
  line-height: 1.6;
  color: #a1a1aa;
  margin: 0 0 1.45rem;
  flex: 1;
}

.v3r-card-telemetry {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 0.95rem;
  border-top: 1px solid rgba(255, 255, 255, 0.07);
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.625rem;
  letter-spacing: 0.08em;
}

.telemetry-label {
  color: #52525b;
  font-weight: 600;
}

.telemetry-val {
  color: #d4d4d8;
  font-weight: 700;
}

/* Manifesto Box */
.v3r-manifesto-box {
  position: relative;
  background: transparent;
  border: none;
  border-radius: 0;
  padding: 3rem 1.5rem;
  backdrop-filter: none;
  -webkit-backdrop-filter: none;
  box-shadow: none;
  text-align: center;
  max-width: 860px;
  margin: 0 auto;
}

.v3r-manifesto-inner {
  position: relative;
  z-index: 2;
}

.v3r-council-seal {
  width: 24px;
  height: 24px;
  margin: 0 auto 1rem;
  background: transparent;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: none;
}

.v3r-council-seal svg,
.v3r-seal-icon {
  width: 22px !important;
  height: 22px !important;
  max-width: 22px !important;
  max-height: 22px !important;
  display: block;
  stroke: #71717a;
  color: #71717a;
  flex-shrink: 0;
}

.v3r-quote-icon {
  font-size: 2rem;
  line-height: 1;
  font-family: Georgia, serif;
  color: rgba(255, 255, 255, 0.2);
  margin-bottom: 0.65rem;
}

.v3r-manifesto-quote {
  font-size: clamp(1.05rem, 2.2vw, 1.35rem);
  font-weight: 500;
  line-height: 1.68;
  color: #ffffff;
  font-style: italic;
  margin: 0 auto 2rem;
  max-width: 720px;
}

.v3r-manifesto-author {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.author-name {
  font-size: 0.825rem;
  font-weight: 800;
  letter-spacing: 0.14em;
  color: #ffffff;
}

.author-role {
  font-size: 0.675rem;
  font-weight: 700;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  letter-spacing: 0.16em;
  color: #71717a;
}

/* CTA Banner */
.v3r-cta-banner {
  padding: 3.5rem 0 5.5rem;
}

.v3r-cta-card {
  position: relative;
  background: transparent;
  border: none;
  border-radius: 0;
  padding: 3.5rem 1.5rem;
  text-align: center;
  backdrop-filter: none;
  -webkit-backdrop-filter: none;
  box-shadow: none;
}

.v3r-cta-glow {
  display: none;
}

.v3r-cta-badge {
  display: inline-block;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.16em;
  color: #10b981;
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.24);
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  margin-bottom: 1.15rem;
}

.v3r-cta-title {
  position: relative;
  z-index: 2;
  font-size: clamp(1.65rem, 3.2vw, 2.35rem);
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 0.95rem;
  letter-spacing: -0.03em;
}

.v3r-cta-subtitle {
  position: relative;
  z-index: 2;
  font-size: 0.975rem;
  color: #a1a1aa;
  max-width: 560px;
  margin: 0 auto 2.25rem;
  line-height: 1.6;
}

.v3r-cta-buttons {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.95rem;
  flex-wrap: wrap;
}

/* Footer */
.v3r-footer {
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(0, 0, 0, 0.95);
  padding: 2.5rem 1.5rem;
  position: relative;
  z-index: 10;
}

.v3r-footer-inner {
  max-width: 1140px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.v3r-footer-brand {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.v3r-footer-logo {
  width: 2.25rem;
  height: 2.25rem;
  object-fit: contain;
  flex-shrink: 0;
  filter: drop-shadow(0 2px 8px rgba(255, 255, 255, 0.18));
}

.v3r-footer-brand-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.brand-text {
  font-size: 1rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: #ffffff;
}

.brand-sub {
  font-size: 0.625rem;
  font-weight: 700;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  letter-spacing: 0.14em;
  color: #71717a;
}

.v3r-footer-links {
  display: flex;
  gap: 1.5rem;
}

.footer-link {
  color: #a1a1aa;
  text-decoration: none;
  font-size: 0.8125rem;
  font-weight: 500;
  transition: color 0.18s ease;
}

.footer-link:hover {
  color: #ffffff;
}

.v3r-footer-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.675rem;
  font-weight: 600;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  letter-spacing: 0.08em;
  color: #71717a;
}

.status-node {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 8px #10b981;
}

/* Responsive adjustments */
@media (max-width: 820px) {
  .v3r-hero-headline-wrap {
    flex-direction: column;
    gap: 1.15rem;
    text-align: center;
  }

  .v3r-hero-title-group {
    text-align: center;
  }

  .v3r-hero-logo {
    width: 4.25rem;
    height: 4.25rem;
  }

  .v3r-nav-menu,
  .v3r-nav-telemetry,
  .v3r-cipher-stream {
    display: none;
  }

  .v3r-nav-bar {
    top: 0.5rem;
    width: calc(100% - 1.25rem);
  }

  .v3r-mock-msg-row {
    max-width: 90%;
  }

  .v3r-features-grid {
    grid-template-columns: 1fr;
  }

  .v3r-system-status {
    flex-direction: column;
    gap: 0.5rem;
    border-radius: 1rem;
    padding: 0.85rem 1.25rem;
  }

  .v3r-status-divider,
  .v3r-badge-divider,
  .v3r-badge-cipher {
    display: none;
  }
}

@media (max-width: 640px) {
  /* Navigation */
  .v3r-nav-bar {
    top: 0.35rem;
    width: calc(100% - 0.75rem);
    padding: 0.35rem 0.65rem;
  }

  .v3r-logo-badge {
    width: 1.85rem;
    height: 1.85rem;
  }

  .v3r-brand-name {
    font-size: 0.875rem;
  }

  .v3r-nav-actions .v3r-btn-ghost,
  .v3r-nav-actions .v3r-btn-primary {
    padding: 0.35rem 0.65rem;
    font-size: 0.75rem;
  }

  /* Hero Section */
  .v3r-hero-section {
    padding: 2.25rem 0.5rem 1.5rem;
  }

  .v3r-hero-headline-wrap {
    gap: 0.75rem;
    margin-bottom: 0.75rem;
  }

  .v3r-hero-logo {
    width: 3.25rem;
    height: 3.25rem;
  }

  .v3r-hero-title {
    font-size: clamp(1.4rem, 5.8vw, 1.95rem);
    line-height: 1.15;
    min-height: auto;
    letter-spacing: -0.02em;
  }

  .v3r-hero-subtitle {
    font-size: 0.8125rem;
    line-height: 1.45;
    margin: 0 0 1.25rem;
    padding: 0 0.25rem;
  }

  .v3r-hero-actions {
    flex-direction: column;
    width: 100%;
    max-width: 290px;
    gap: 0.5rem;
    margin: 0 auto;
  }

  .v3r-cta-primary,
  .v3r-cta-secondary {
    width: 100%;
    justify-content: center;
    padding: 0.65rem 1.15rem;
    font-size: 0.8125rem;
  }

  /* Mockup Frame & Header */
  .v3r-hero-mockup {
    margin: 1.25rem auto 0;
    width: 100%;
  }

  .v3r-mockup-frame {
    border-radius: 0.85rem;
  }

  .v3r-mockup-header {
    padding: 0.5rem 0.65rem;
    gap: 0.5rem;
  }

  .v3r-window-controls {
    gap: 0.3rem;
  }

  .v3r-window-controls .dot {
    width: 7px;
    height: 7px;
  }

  .v3r-channel-avatar {
    width: 1.85rem;
    height: 1.85rem;
  }

  .v3r-channel-name {
    font-size: 0.75rem;
  }

  .v3r-channel-name .author-sub {
    font-size: 0.65rem;
  }

  .v3r-channel-sub {
    font-size: 0.58rem;
    gap: 0.25rem;
  }

  .v3r-status-ping {
    width: 4px;
    height: 4px;
  }

  /* Mockup Chat Body & Bubbles */
  .v3r-mockup-body {
    padding: 0.65rem 0.55rem 0.45rem;
    min-height: 270px;
    gap: 0.6rem;
  }

  .v3r-mock-msg-row {
    max-width: 95%;
    gap: 0.45rem;
  }

  .v3r-mock-avatar {
    width: 1.65rem;
    height: 1.65rem;
    font-size: 0.55rem;
  }

  .avatar-status {
    width: 4px;
    height: 4px;
  }

  .v3r-mock-bubble {
    padding: 0.4rem 0.65rem 0.35rem;
    border-radius: 0.75rem;
    font-size: 0.75rem;
    line-height: 1.38;
  }

  .v3r-mock-author {
    margin-bottom: 0.15rem;
  }

  .v3r-mock-bubble.incoming .author-name,
  .v3r-mock-bubble.outgoing .author-name {
    font-size: 0.6875rem;
  }

  .author-sub {
    font-size: 0.625rem;
  }

  .v3r-msg-content {
    font-size: 0.75rem;
    line-height: 1.38;
  }

  .v3r-mock-meta {
    margin-top: 0.15rem;
    font-size: 0.55rem;
  }

  .v3r-meta-time {
    font-size: 0.55rem;
  }

  .v3r-double-ticks {
    font-size: 0.65rem;
  }

  /* Composer */
  .v3r-mockup-composer-area {
    padding: 0.45rem 0 0.15rem;
  }

  .v3r-floating-composer-pill {
    min-height: 2.35rem;
    padding: 0.18rem 0.4rem;
    gap: 0.25rem;
  }

  .v3r-composer-icon-btn,
  .v3r-composer-action-btn {
    width: 1.85rem;
    height: 1.85rem;
  }

  .v3r-composer-icon-btn svg,
  .v3r-composer-action-btn svg {
    width: 14px !important;
    height: 14px !important;
    min-width: 14px !important;
    min-height: 14px !important;
    max-width: 14px !important;
    max-height: 14px !important;
  }

  .v3r-composer-input-field {
    font-size: 0.72rem;
    padding: 0.25rem 0.35rem;
  }

  /* Sections */
  .v3r-section {
    padding: 2.5rem 0;
  }

  .v3r-section-header {
    margin-bottom: 1.25rem;
  }

  .v3r-section-badge {
    font-size: 0.58rem;
    padding: 0.15rem 0.5rem;
  }

  .v3r-section-title {
    font-size: clamp(1.2rem, 4.5vw, 1.45rem);
    line-height: 1.2;
  }

  .v3r-section-subtitle {
    font-size: 0.78rem;
    line-height: 1.4;
    padding: 0 0.5rem;
  }

  /* Comparison Card & Table */
  .v3r-comparison-card {
    border-radius: 0.75rem;
  }

  .v3r-compare-table {
    min-width: 520px;
  }

  .v3r-compare-table th,
  .v3r-compare-table td {
    padding: 0.5rem 0.55rem;
    font-size: 0.7rem;
  }

  .feature-name {
    font-size: 0.7rem;
  }

  .val-standard,
  .v3r-val-text {
    font-size: 0.68rem;
    line-height: 1.35;
  }

  .badge-v3r {
    font-size: 0.55rem;
    padding: 0.08rem 0.3rem;
  }

  /* Feature Cards */
  .v3r-features-grid {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .v3r-feature-card {
    padding: 1rem 0.85rem;
    border-radius: 0.75rem;
  }

  .v3r-feature-icon-box {
    width: 2.15rem;
    height: 2.15rem;
  }

  .v3r-feature-svg {
    width: 18px;
    height: 18px;
  }

  .v3r-feature-num {
    font-size: 0.58rem;
  }

  .v3r-feature-title {
    font-size: 0.92rem;
    margin: 0.55rem 0 0.3rem;
  }

  .v3r-feature-desc {
    font-size: 0.75rem;
    line-height: 1.4;
  }

  .v3r-card-telemetry {
    margin-top: 0.65rem;
    padding-top: 0.45rem;
    font-size: 0.58rem;
  }

  /* Manifesto */
  .v3r-manifesto-box {
    padding: 1.25rem 0.85rem;
    border-radius: 0.75rem;
  }

  .v3r-quote-icon {
    font-size: 2.25rem;
    line-height: 1;
    margin-bottom: 0.35rem;
  }

  .v3r-manifesto-quote {
    font-size: 0.8rem;
    line-height: 1.45;
  }

  .v3r-manifesto-author {
    margin-top: 0.75rem;
    font-size: 0.68rem;
  }

  .v3r-manifesto-author .author-role {
    font-size: 0.58rem;
  }

  /* CTA Banner */
  .v3r-cta-banner {
    padding: 2.25rem 0;
  }

  .v3r-cta-card {
    padding: 1.5rem 0.85rem;
    border-radius: 0.75rem;
  }

  .v3r-cta-title {
    font-size: 1.2rem;
    line-height: 1.25;
  }

  .v3r-cta-subtitle {
    font-size: 0.78rem;
    line-height: 1.4;
    margin-bottom: 1.15rem;
  }

  .v3r-cta-buttons {
    flex-direction: column;
    width: 100%;
    max-width: 280px;
    margin: 0 auto;
    gap: 0.5rem;
  }

  /* Footer */
  .v3r-footer {
    padding: 1.75rem 0 1.25rem;
  }

  .v3r-footer-inner {
    flex-direction: column;
    text-align: center;
    gap: 0.85rem;
  }

  .v3r-footer-brand {
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
  }

  .brand-text {
    font-size: 0.85rem;
  }

  .brand-sub {
    font-size: 0.55rem;
  }

  .v3r-footer-links {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.75rem;
    font-size: 0.75rem;
  }

  .v3r-footer-status {
    font-size: 0.58rem;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .v3r-hero-title {
    font-size: clamp(1.25rem, 5.6vw, 1.65rem);
  }

  .v3r-hero-subtitle {
    font-size: 0.78rem;
    line-height: 1.42;
  }

  .v3r-mock-bubble {
    padding: 0.35rem 0.55rem 0.3rem;
  }

  .v3r-msg-content {
    font-size: 0.72rem;
  }

  .v3r-composer-input-field {
    font-size: 0.7rem;
  }

  .v3r-composer-input-field::placeholder {
    font-size: 0.68rem;
  }
}
</style>