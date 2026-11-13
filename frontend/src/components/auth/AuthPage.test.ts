// language: TypeScript, file: frontend/src/components/auth/AuthPage.test.ts, target: vitest
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import axios from 'axios'
import AuthPage from './AuthPage.vue'

describe('AuthPage', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('renders login form by default with liquid glass card and fields', () => {
    const wrapper = mount(AuthPage, {
      props: {
        initialMode: 'login',
        csrfToken: 'test-csrf-token',
        loginUrl: '/login',
        registerUrl: '/register',
        dashboardUrl: '/v3rchat',
        appName: 'V3RCHAT',
      },
    })

    expect(wrapper.find('.liquid-glass-auth-card').exists()).toBe(true)
    expect(wrapper.find('#v3r-login-email').exists()).toBe(true)
    expect(wrapper.find('#v3r-login-password').exists()).toBe(true)
    expect(wrapper.find('#v3r-secret-code').exists()).toBe(false)
    expect(wrapper.find('.liquid-glass-submit').text()).toContain('Sign in')
  })

  it('switches to register mode and executes multi-step secret code authorization flow', async () => {
    vi.spyOn(axios, 'post').mockImplementation(async (url: string, payload: any) => {
      if (url === '/register/verify-code') {
        if (payload?.secret_code === 'V3ROURPARTNERSINCE2020') {
          return { data: { valid: true } }
        }
        const error: any = new Error('Invalid code')
        error.response = { status: 422, data: { errors: { secret_code: ['The provided syndicate access code is invalid.'] } } }
        throw error
      }
      return { data: { status: 'success' } }
    })

    const wrapper = mount(AuthPage, {
      props: {
        initialMode: 'login',
        csrfToken: 'test-csrf-token',
        loginUrl: '/login',
        registerUrl: '/register',
        dashboardUrl: '/v3rchat',
        appName: 'V3RCHAT',
      },
    })

    const buttons = wrapper.findAll('.v3rchat-switcher-btn')
    expect(buttons).toHaveLength(2)

    // Switch to Create Account -> Step 1: Secret Code Gate
    await buttons[1].trigger('click')
    expect(wrapper.text()).toContain('What the code then ?')
    expect(wrapper.find('#v3r-secret-code').exists()).toBe(true)
    expect(wrapper.find('#v3r-name').exists()).toBe(false)

    // Attempting Next with empty code triggers validation error
    const nextBtnStep1 = wrapper.find('.v3rchat-submit-btn')
    await nextBtnStep1.trigger('click')
    expect(wrapper.text()).toContain('What the code then ? Secret access code required.')

    // Enter wrong code
    await wrapper.find('#v3r-secret-code').setValue('WRONG_CODE')
    await nextBtnStep1.trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('The provided syndicate access code is invalid.')
    expect(wrapper.find('#v3r-name').exists()).toBe(false)

    // Enter correct syndicate clearance code
    await wrapper.find('#v3r-secret-code').setValue('V3ROURPARTNERSINCE2020')
    await nextBtnStep1.trigger('click')
    await flushPromises()

    // Step 2 unlocked: Profile info
    expect(wrapper.find('#v3r-name').exists()).toBe(true)
    expect(wrapper.find('#v3r-username').exists()).toBe(true)
    expect(wrapper.find('#v3r-email').exists()).toBe(true)
    expect(wrapper.find('#v3r-password').exists()).toBe(false)

    // Fill Step 2 profile
    await wrapper.find('#v3r-name').setValue('Don Salvatore')
    await wrapper.find('#v3r-username').setValue('don_salvatore')
    await wrapper.find('#v3r-email').setValue('don@v3rchat.io')

    // Click Next on Step 2
    const step2Buttons = wrapper.findAll('.v3rchat-step-btn-group button')
    await step2Buttons[1].trigger('click') // Next
    await flushPromises()

    // Step 3 unlocked: Cipher / Password
    expect(wrapper.find('#v3r-password').exists()).toBe(true)
    expect(wrapper.find('#v3r-password-confirmation').exists()).toBe(true)
    expect(wrapper.find('button[type="submit"]').text()).toContain('Create Account')

    // Switch back to Sign In
    await buttons[0].trigger('click')
    expect(wrapper.find('#v3r-secret-code').exists()).toBe(false)
    expect(wrapper.find('#v3r-login-email').exists()).toBe(true)
  })
})
