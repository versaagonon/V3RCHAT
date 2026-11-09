<script setup lang="ts">
import { ref } from 'vue'
import axios from 'axios'

const props = withDefaults(
  defineProps<{
    initialMode?: 'login' | 'register'
    csrfToken?: string
    loginUrl?: string
    registerUrl?: string
    dashboardUrl?: string
    appName?: string
  }>(),
  {
    initialMode: 'login',
    csrfToken: '',
    loginUrl: '/login',
    registerUrl: '/register',
    dashboardUrl: '/v3rchat',
    appName: 'V3RCHAT',
  },
)

const mode = ref<'login' | 'register'>(props.initialMode)

// Form fields
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const remember = ref(false)

// UI state
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const loading = ref(false)
const generalError = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

// Glare coordinates for Liquid Glass Card
const cardGlareX = ref('50%')
const cardGlareY = ref('20%')
const cardGlareOpacity = ref('0.55')

function onCardPointerMove(event: PointerEvent) {
  const target = event.currentTarget as HTMLElement | null
  if (!target) return
  const rect = target.getBoundingClientRect()
  const x = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100))
  const y = Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100))
  cardGlareX.value = `${Math.round(x)}%`
  cardGlareY.value = `${Math.round(y)}%`
  cardGlareOpacity.value = '0.85'
}

function onCardPointerLeave() {
  cardGlareX.value = '50%'
  cardGlareY.value = '20%'
  cardGlareOpacity.value = '0.55'
}

function switchMode(newMode: 'login' | 'register') {
  mode.value = newMode
  generalError.value = ''
  fieldErrors.value = {}
  if (typeof window !== 'undefined') {
    window.history.replaceState({}, '', newMode === 'login' ? props.loginUrl : props.registerUrl)
  }
}

async function handleSubmit(event: Event) {
  event.preventDefault()
  if (loading.value) return

  loading.value = true
  generalError.value = ''
  fieldErrors.value = {}

  const url = mode.value === 'login' ? props.loginUrl : props.registerUrl
  const payload: Record<string, unknown> = {
    _token: props.csrfToken,
    email: email.value,
    password: password.value,
  }

  if (mode.value === 'login') {
    payload.remember = remember.value
  } else {
    payload.name = name.value
    payload.password_confirmation = passwordConfirmation.value
  }

  try {
    await axios.post(url, payload, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': props.csrfToken,
      },
    })

    window.location.href = props.dashboardUrl
  } catch (err: any) {
    loading.value = false
    if (err?.response?.status === 422 && err.response.data?.errors) {
      fieldErrors.value = err.response.data.errors
      if (err.response.data.message) {
        generalError.value = err.response.data.message
      }
    } else if (err?.response?.data?.message) {
      generalError.value = err.response.data.message
    } else {
      const form = (event.target as HTMLFormElement)
      if (form) {
        form.submit()
        return
      }
      generalError.value = 'Authentication failed. Please check your credentials.'
    }
  }
}
</script>

<template>
  <div class="v3rchat-auth-container">
    <!-- Glamorous Wallpaper Pattern Background Overlay -->
    <div class="v3rchat-auth-bg-pattern" aria-hidden="true" />
    <div class="v3rchat-auth-ambient-glow" aria-hidden="true" />

    <!-- Centered Auth Card -->
    <div
      class="v3rchat-auth-card liquid-glass-auth-card"
      :style="{
        '--glare-x': cardGlareX,
        '--glare-y': cardGlareY,
        '--glare-opacity': cardGlareOpacity,
      }"
      @pointermove="onCardPointerMove"
      @pointerleave="onCardPointerLeave"
    >
      <!-- Refraction Fringe & Silky Rim Highlights -->
      <span class="liquid-glass-refraction-fringe" aria-hidden="true" />
      <span class="liquid-glass-specular" aria-hidden="true" />
      <span class="liquid-glass-card-glare" aria-hidden="true" />
      <span class="liquid-glass-rim" aria-hidden="true" />

      <!-- Brand Header -->
      <div class="v3rchat-auth-header">
        <div class="v3rchat-auth-badge liquid-glass-badge">
          <!-- V3RCHAT Chat Bubble Icon -->
          <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
        </div>
        <h1 class="v3rchat-auth-title">{{ appName }}</h1>
        <p class="v3rchat-auth-subtitle">Private Realtime Communications</p>
      </div>

      <!-- Segmented Pill Switcher (Sign In vs Create Account) -->
      <div class="v3rchat-auth-switcher" role="tablist">
        <button
          type="button"
          class="v3rchat-switcher-btn"
          :class="{ 'v3rchat-switcher-active': mode === 'login' }"
          @click="switchMode('login')"
        >
          Sign In
        </button>
        <button
          type="button"
          class="v3rchat-switcher-btn"
          :class="{ 'v3rchat-switcher-active': mode === 'register' }"
          @click="switchMode('register')"
        >
          Create Account
        </button>
      </div>

      <!-- General Error Alert -->
      <div v-if="generalError" class="v3rchat-auth-alert">
        <svg class="h-4 w-4 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ generalError }}</span>
      </div>

      <!-- Form -->
      <form
        class="v3rchat-auth-form"
        :action="mode === 'login' ? loginUrl : registerUrl"
        method="POST"
        @submit="handleSubmit"
      >
        <input type="hidden" name="_token" :value="csrfToken" />

        <!-- Name (Register only) -->
        <div v-if="mode === 'register'" class="v3rchat-form-group">
          <label for="v3r-name" class="v3rchat-form-label">Full Name</label>
          <div class="v3rchat-input-wrapper">
            <svg class="v3rchat-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <input
              id="v3r-name"
              v-model="name"
              type="text"
              name="name"
              required
              autocomplete="name"
              placeholder="e.g. Versaa"
              class="v3rchat-form-input"
            />
          </div>
          <p v-if="fieldErrors.name?.[0]" class="v3rchat-form-error">{{ fieldErrors.name[0] }}</p>
        </div>

        <!-- Email -->
        <div class="v3rchat-form-group">
          <label for="v3r-email" class="v3rchat-form-label">Email Address</label>
          <div class="v3rchat-input-wrapper">
            <svg class="v3rchat-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
            </svg>
            <input
              id="v3r-email"
              v-model="email"
              type="email"
              name="email"
              required
              autocomplete="username"
              placeholder="name@example.com"
              class="v3rchat-form-input"
            />
          </div>
          <p v-if="fieldErrors.email?.[0]" class="v3rchat-form-error">{{ fieldErrors.email[0] }}</p>
        </div>

        <!-- Password -->
        <div class="v3rchat-form-group">
          <label for="v3r-password" class="v3rchat-form-label">Password</label>
          <div class="v3rchat-input-wrapper">
            <svg class="v3rchat-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <input
              id="v3r-password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              name="password"
              required
              :autocomplete="mode === 'login' ? 'current-password' : 'new-password'"
              placeholder="••••••••"
              class="v3rchat-form-input"
            />
            <button
              type="button"
              class="v3rchat-input-trailing"
              tabindex="-1"
              @click="showPassword = !showPassword"
            >
              <svg v-if="showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
              </svg>
              <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
          </div>
          <p v-if="fieldErrors.password?.[0]" class="v3rchat-form-error">{{ fieldErrors.password[0] }}</p>
        </div>

        <!-- Confirm Password (Register only) -->
        <div v-if="mode === 'register'" class="v3rchat-form-group">
          <label for="v3r-password-confirmation" class="v3rchat-form-label">Confirm Password</label>
          <div class="v3rchat-input-wrapper">
            <svg class="v3rchat-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <input
              id="v3r-password-confirmation"
              v-model="passwordConfirmation"
              :type="showPasswordConfirm ? 'text' : 'password'"
              name="password_confirmation"
              required
              autocomplete="new-password"
              placeholder="••••••••"
              class="v3rchat-form-input"
            />
            <button
              type="button"
              class="v3rchat-input-trailing"
              tabindex="-1"
              @click="showPasswordConfirm = !showPasswordConfirm"
            >
              <svg v-if="showPasswordConfirm" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
              </svg>
              <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
          </div>
          <p v-if="fieldErrors.password_confirmation?.[0]" class="v3rchat-form-error">{{ fieldErrors.password_confirmation[0] }}</p>
        </div>

        <!-- Remember Me (Login only) -->
        <div v-if="mode === 'login'" class="v3rchat-form-options">
          <label class="v3rchat-checkbox-label">
            <input
              v-model="remember"
              type="checkbox"
              name="remember"
              class="v3rchat-checkbox"
            />
            <span>Remember me</span>
          </label>
        </div>

        <!-- Submit Button (Liquid Glass Action Button) -->
        <button
          type="submit"
          class="v3rchat-submit-btn liquid-glass-submit"
          :disabled="loading"
        >
          <span v-if="loading" class="v3rchat-spinner" aria-hidden="true" />
          <span>{{ loading ? 'Authenticating...' : (mode === 'login' ? 'Sign In to V3RCHAT' : 'Create Account') }}</span>
        </button>
      </form>

      <!-- Footer Switcher Link -->
      <div class="v3rchat-auth-footer">
        <template v-if="mode === 'login'">
          <span>Don't have an account?</span>
          <button type="button" class="v3rchat-auth-link" @click="switchMode('register')">Create an account</button>
        </template>
        <template v-else>
          <span>Already have an account?</span>
          <button type="button" class="v3rchat-auth-link" @click="switchMode('login')">Sign in</button>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.v3rchat-auth-container {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100vw;
  height: 100vh;
  min-height: 100%;
  background-color: #000000;
  overflow: hidden;
  user-select: none;
}

.v3rchat-auth-bg-pattern {
  position: absolute;
  inset: 0;
  background-color: #ffffff;
  opacity: 0.12;
  mask-image: url('/vendor/chatify/patterns/glamorous.svg');
  -webkit-mask-image: url('/vendor/chatify/patterns/glamorous.svg');
  mask-repeat: repeat;
  -webkit-mask-repeat: repeat;
  mask-size: 140px;
  -webkit-mask-size: 140px;
  pointer-events: none;
}

.v3rchat-auth-ambient-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 35%, rgba(255, 255, 255, 0.06) 0%, transparent 65%);
  pointer-events: none;
}

/* Liquid Glass Auth Card */
.liquid-glass-auth-card {
  position: relative;
  width: 100%;
  max-width: 25.5rem;
  margin: 1.25rem;
  padding: 2rem 1.75rem;
  border-radius: 1.5rem;
  background: rgba(18, 18, 20, 0.65);
  backdrop-filter: blur(24px) saturate(1.6);
  -webkit-backdrop-filter: blur(24px) saturate(1.6);
  border: 1px solid rgba(255, 255, 255, 0.14);
  box-shadow:
    0 24px 64px -12px rgba(0, 0, 0, 0.85),
    inset 0 1.5px 1.5px 0 rgba(255, 255, 255, 0.28),
    inset 0 -1.5px 1.5px 0 rgba(0, 0, 0, 0.5);
  overflow: hidden;
  z-index: 10;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.liquid-glass-auth-card .liquid-glass-card-glare {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: radial-gradient(
    ellipse 320px 200px at var(--glare-x, 50%) var(--glare-y, 20%),
    rgba(255, 255, 255, 0.16) 0%,
    rgba(255, 255, 255, 0.04) 45%,
    transparent 75%
  );
  opacity: var(--glare-opacity, 0.55);
  transition: opacity 0.25s ease;
  z-index: 1;
}

.liquid-glass-auth-card .liquid-glass-refraction-fringe {
  position: absolute;
  inset: 0;
  border-radius: inherit;
  pointer-events: none;
  box-shadow:
    inset 1.5px 1.5px 3px 0 rgba(255, 90, 110, 0.14),
    inset 0 0 4px 1px rgba(80, 255, 140, 0.08),
    inset -1.5px -1.5px 3px 0 rgba(60, 170, 255, 0.18);
  z-index: 1;
}

.liquid-glass-auth-card .liquid-glass-specular {
  position: absolute;
  inset: 0;
  border-radius: inherit;
  pointer-events: none;
  background: linear-gradient(
    170deg,
    rgba(255, 255, 255, 0.12) 0%,
    rgba(255, 255, 255, 0.02) 35%,
    transparent 60%
  );
  z-index: 1;
}

.liquid-glass-auth-card .liquid-glass-rim {
  position: absolute;
  inset: 0;
  border-radius: inherit;
  pointer-events: none;
  box-shadow:
    inset 0 1px 1px 0 rgba(255, 255, 255, 0.35),
    inset 0 -1px 1px 0 rgba(0, 0, 0, 0.45);
  z-index: 1;
}

.liquid-glass-auth-card > *:not(.liquid-glass-refraction-fringe):not(.liquid-glass-specular):not(.liquid-glass-card-glare):not(.liquid-glass-rim) {
  position: relative;
  z-index: 2;
}

/* Header */
.v3rchat-auth-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  margin-bottom: 1.5rem;
}

.v3rchat-auth-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 3.25rem;
  height: 3.25rem;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.22);
  box-shadow:
    inset 0 1px 1.5px 0 rgba(255, 255, 255, 0.4),
    0 4px 16px rgba(0, 0, 0, 0.4);
  margin-bottom: 0.75rem;
}

.v3rchat-auth-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: -0.025em;
  line-height: 1.2;
}

.v3rchat-auth-subtitle {
  font-size: 0.75rem;
  color: #a1a1aa;
  margin-top: 0.25rem;
}

/* Tab Switcher */
.v3rchat-auth-switcher {
  display: flex;
  background: rgba(0, 0, 0, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 9999px;
  padding: 0.25rem;
  margin-bottom: 1.5rem;
}

.v3rchat-switcher-btn {
  flex: 1;
  padding: 0.45rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #a1a1aa;
  background: transparent;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.v3rchat-switcher-btn:hover {
  color: #ffffff;
}

.v3rchat-switcher-active {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.14);
  border-color: rgba(255, 255, 255, 0.25);
  box-shadow:
    inset 0 1px 1.5px 0 rgba(255, 255, 255, 0.45),
    0 2px 8px rgba(0, 0, 0, 0.3);
  font-weight: 600;
}

/* Alert */
.v3rchat-auth-alert {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 0.875rem;
  border-radius: 0.75rem;
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.25);
  color: #fca5a5;
  font-size: 0.75rem;
  margin-bottom: 1.25rem;
}

/* Form */
.v3rchat-auth-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.v3rchat-form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.v3rchat-form-label {
  font-size: 0.6875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #d4d4d8;
}

.v3rchat-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.v3rchat-input-icon {
  position: absolute;
  left: 0.75rem;
  width: 1rem;
  height: 1rem;
  color: #71717a;
  pointer-events: none;
}

.v3rchat-form-input {
  width: 100%;
  padding: 0.625rem 2.25rem 0.625rem 2.25rem;
  border-radius: 0.75rem;
  background: rgba(0, 0, 0, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #ffffff;
  font-size: 0.875rem;
  transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
}

.v3rchat-form-input::placeholder {
  color: #52525b;
}

.v3rchat-form-input:focus {
  outline: none;
  background: rgba(0, 0, 0, 0.6);
  border-color: rgba(255, 255, 255, 0.4);
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.12);
}

.v3rchat-input-trailing {
  position: absolute;
  right: 0.75rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #71717a;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0.25rem;
  transition: color 0.15s ease;
}

.v3rchat-input-trailing:hover {
  color: #ffffff;
}

.v3rchat-form-error {
  font-size: 0.6875rem;
  color: #f87171;
  margin-top: 0.125rem;
}

.v3rchat-form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: -0.25rem;
}

.v3rchat-checkbox-label {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: #a1a1aa;
  cursor: pointer;
}

.v3rchat-checkbox {
  appearance: none;
  width: 1rem;
  height: 1rem;
  border-radius: 0.25rem;
  border: 1px solid rgba(255, 255, 255, 0.25);
  background: rgba(0, 0, 0, 0.5);
  cursor: pointer;
  position: relative;
  transition: all 0.15s ease;
}

.v3rchat-checkbox:checked {
  background-color: #ffffff;
  border-color: #ffffff;
}

.v3rchat-checkbox:checked::after {
  content: '';
  position: absolute;
  top: 1px;
  left: 4px;
  width: 4px;
  height: 8px;
  border: solid #000000;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

/* Liquid Glass Submit Button */
.liquid-glass-submit {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.75rem 1.25rem;
  border-radius: 0.875rem;
  background: #ffffff;
  color: #000000;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  border: 1px solid rgba(255, 255, 255, 0.6);
  box-shadow:
    0 4px 16px rgba(255, 255, 255, 0.12),
    0 8px 24px rgba(0, 0, 0, 0.35),
    inset 0 1px 1.5px rgba(255, 255, 255, 0.8);
  transition: transform 0.15s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.15s ease, background 0.15s ease;
  margin-top: 0.75rem;
}

.liquid-glass-submit:hover:not(:disabled) {
  transform: translateY(-1.5px);
  background: #f4f4f5;
  box-shadow:
    0 6px 20px rgba(255, 255, 255, 0.18),
    0 12px 30px rgba(0, 0, 0, 0.45),
    inset 0 1px 1.5px rgba(255, 255, 255, 0.9);
}

.liquid-glass-submit:active:not(:disabled) {
  transform: translateY(0.5px) scale(0.98);
}

.liquid-glass-submit:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.v3rchat-spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(0, 0, 0, 0.2);
  border-top-color: #000000;
  border-radius: 9999px;
  animation: v3rchat-spin 0.8s linear infinite;
}

@keyframes v3rchat-spin {
  to {
    transform: rotate(360deg);
  }
}

/* Footer */
.v3rchat-auth-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  font-size: 0.75rem;
  color: #a1a1aa;
  margin-top: 1.5rem;
  text-align: center;
}

.v3rchat-auth-link {
  color: #ffffff;
  font-weight: 500;
  background: transparent;
  border: none;
  cursor: pointer;
  text-decoration: underline;
  text-underline-offset: 3px;
  transition: color 0.15s ease;
}

.v3rchat-auth-link:hover {
  color: #d4d4d8;
}
</style>

