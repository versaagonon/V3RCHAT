// language: TypeScript, file: frontend/src/components/landing/LandingPage.test.ts, target: vitest
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import LandingPage from './LandingPage.vue'

describe('LandingPage', () => {
  it('renders syndicate landing page with hero, navigation, and features', () => {
    const wrapper = mount(LandingPage, {
      props: {
        user: null,
        loginUrl: '/login',
        registerUrl: '/register',
        dashboardUrl: '/v3rchat',
        appName: 'V3RCHAT',
      },
    })

    expect(wrapper.find('.v3r-brand-name').text()).toBe('V3RCHAT')
    expect(wrapper.find('.v3r-hero-title').text().toUpperCase()).toContain('SOVEREIGN MESSAGING')
    expect(wrapper.find('.v3r-hero-title').text().toUpperCase()).toContain('ZERO-TRACE CONTROL')
    expect(wrapper.findAll('.v3r-feature-card')).toHaveLength(4)
    expect(wrapper.find('.v3r-comparison-card').exists()).toBe(true)
    expect(wrapper.find('.v3r-manifesto-box').exists()).toBe(true)
  })

  it('renders guest CTA actions when user is not authenticated', () => {
    const wrapper = mount(LandingPage, {
      props: {
        user: null,
        loginUrl: '/login',
        registerUrl: '/register',
        dashboardUrl: '/v3rchat',
        appName: 'V3RCHAT',
      },
    })

    const primaryCta = wrapper.find('.v3r-cta-primary')
    expect(primaryCta.attributes('href')).toBe('/login')
    expect(primaryCta.text()).toContain('Access Terminal')

    const registerCta = wrapper.find('.v3r-cta-secondary')
    expect(registerCta.attributes('href')).toBe('/register')
  })

  it('renders authenticated terminal link when user is logged in', () => {
    const wrapper = mount(LandingPage, {
      props: {
        user: {
          id: 1,
          type: 'user',
          attributes: {
            name: 'Don Salvatore',
            avatar: 'http://localhost:8000/avatar.png',
          },
        },
        loginUrl: '/login',
        registerUrl: '/register',
        dashboardUrl: '/v3rchat',
        appName: 'V3RCHAT',
      },
    })

    const primaryCta = wrapper.find('.v3r-cta-primary')
    expect(primaryCta.attributes('href')).toBe('/v3rchat')
    expect(primaryCta.text()).toContain('Open Chat Console')
  })
})
