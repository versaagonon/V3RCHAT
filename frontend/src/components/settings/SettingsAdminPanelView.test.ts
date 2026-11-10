import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import SettingsAdminPanelView from './SettingsAdminPanelView.vue'
import { useConfigStore } from '../../stores/config'

describe('SettingsAdminPanelView', () => {
  beforeEach(() => {
    document.body.innerHTML = '<div id="chatify-app"></div>'
  })

  it('restricts access when user has node role', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)

    const configStore = useConfigStore(pinia)
    configStore.init({
      authMode: 'authenticated',
      colors: ['#2180f3'],
      user: {
        id: 2,
        type: 'user',
        attributes: {
          name: 'Regular Node',
          username: 'node_user',
          email: 'node@test.com',
          role: 'node',
          avatar: null,
        },
      },
    } as any)

    const wrapper = mount(SettingsAdminPanelView, {
      global: {
        plugins: [pinia],
      },
    })

    expect(wrapper.text()).toContain('Access Restricted')
    expect(wrapper.text()).toContain('Node role')
  })

  it('renders admin management tools when user has admin role', async () => {
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
          name: 'Versaa Admin',
          username: 'versaa_prime',
          email: 'versaa@test.com',
          role: 'admin',
          avatar: null,
        },
      },
    } as any)

    if (configStore.api) {
      configStore.api.getAdminBorders = vi.fn().mockResolvedValue({
        data: {
          data: [
            { id: 1, name: 'Angel Flower', slug: 'angleflower', image_url: '/borders/angleflower.png', is_system: true, users_count: 3 },
            { id: 2, name: 'Dark Rose', slug: 'darkrose', image_url: '/borders/darkrose.png', is_system: true, users_count: 0 },
          ],
        },
      })
      configStore.api.searchAdminUsers = vi.fn().mockResolvedValue({
        data: {
          data: [
            { id: 2, name: 'Toke Suntik', username: 'tokesuntik', email: 'tokesuntik@gmail.com', role: 'node', borders_count: 0 },
          ],
        },
      })
    }

    const wrapper = mount(SettingsAdminPanelView, {
      global: {
        plugins: [pinia],
      },
    })

    expect(wrapper.text()).toContain('Administrator Control Panel')
    expect(wrapper.text()).toContain('Gift Border')
    expect(wrapper.text()).toContain('Add Border')
    expect(wrapper.text()).toContain('Catalog')
  })
})
