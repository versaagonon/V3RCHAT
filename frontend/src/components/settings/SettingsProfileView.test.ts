import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import SettingsProfileView from './SettingsProfileView.vue'
import { useConfigStore } from '../../stores/config'

describe('SettingsProfileView', () => {
  beforeEach(() => {
    document.body.innerHTML = '<div id="chatify-app"></div>'
  })

  it('renders user profile details and toggles current password field only on email modification', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)

    const configStore = useConfigStore(pinia)
    configStore.init({
      authMode: 'authenticated',
      colors: ['#2180f3'],
      user: {
        id: 1,
        type: 'user',
        attributes: {
          name: 'Versaa',
          username: 'versaa_dev',
          email: 'versaa@v3rchat.io',
          avatar: null,
        },
      },
      features: {
        themes: true,
        colors: true,
        fonts: true,
        wallpaper: true,
      },
    } as any)

    if (configStore.api) {
      configStore.api.getUserBorders = vi.fn().mockResolvedValue({
        data: {
          data: {
            is_admin: true,
            active_border_id: null,
            available_borders: [
              { id: 1, name: 'Angel Flower', slug: 'angleflower', image_url: '/borders/angleflower.png' },
            ],
          },
        },
      })
    }

    const wrapper = mount(SettingsProfileView, {
      global: {
        plugins: [pinia],
      },
    })

    expect(wrapper.exists()).toBe(true)

    // Name input has original value
    const nameInput = wrapper.find('input[type="text"]')
    expect((nameInput.element as HTMLInputElement).value).toBe('Versaa')

    // Email input has original value
    const emailInput = wrapper.find('input[type="email"]')
    expect((emailInput.element as HTMLInputElement).value).toBe('versaa@v3rchat.io')

    // Initially, current password field does not exist
    expect(wrapper.find('input[placeholder*="current password"]').exists()).toBe(false)

    // Changing name does NOT reveal password field
    await nameInput.setValue('Versaa Sovereign')
    expect(wrapper.find('input[placeholder*="current password"]').exists()).toBe(false)

    // Changing email REVEALS current password field with security notice
    await emailInput.setValue('new_email@v3rchat.io')
    expect(wrapper.find('input[placeholder*="current password"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Security Confirmation Required')

    // Restoring original email hides current password field again
    await emailInput.setValue('versaa@v3rchat.io')
    expect(wrapper.find('input[placeholder*="current password"]').exists()).toBe(false)
  })
})
