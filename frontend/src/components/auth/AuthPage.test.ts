// language: TypeScript, file: frontend/src/components/auth/AuthPage.test.ts, target: vitest
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AuthPage from './AuthPage.vue'

describe('AuthPage', () => {
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
    expect(wrapper.find('input[type="email"]').exists()).toBe(true)
    expect(wrapper.find('input[type="password"]').exists()).toBe(true)
    expect(wrapper.find('#v3r-name').exists()).toBe(false)
    expect(wrapper.find('.liquid-glass-submit').text()).toContain('Sign In to V3RCHAT')
  })

  it('switches between login and register modes', async () => {
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

    // Switch to Create Account
    await buttons[1].trigger('click')
    expect(wrapper.find('#v3r-name').exists()).toBe(true)
    expect(wrapper.find('#v3r-password-confirmation').exists()).toBe(true)
    expect(wrapper.find('.liquid-glass-submit').text()).toContain('Create Account')

    // Switch back to Sign In
    await buttons[0].trigger('click')
    expect(wrapper.find('#v3r-name').exists()).toBe(false)
    expect(wrapper.find('#v3r-password-confirmation').exists()).toBe(false)
  })
})
