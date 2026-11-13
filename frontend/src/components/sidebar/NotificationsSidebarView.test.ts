import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import NotificationsSidebarView from './NotificationsSidebarView.vue'
import { useConfigStore } from '../../stores/config'

function bootUser() {
  return {
    id: 2,
    type: 'user',
    attributes: {
      name: 'Regular Node',
      username: 'node_user',
      email: 'node@test.com',
      role: 'node',
      avatar: null,
    },
  }
}

describe('NotificationsSidebarView', () => {
  beforeEach(() => {
    document.body.innerHTML = '<div id="chatify-app"></div>'
  })

  it('shows the announcement author avatar with their animated border', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)

    const configStore = useConfigStore(pinia)
    configStore.init({
      authMode: 'authenticated',
      colors: ['#2180f3'],
      user: bootUser(),
    } as any)

    configStore.api!.getNotifications = vi.fn().mockResolvedValue({
      data: {
        data: [
          {
            id: 'notification-1',
            type: 'information',
            title: 'Server Maintenance',
            body: 'Tonight at 22:00.',
            image_url: null,
            data: { is_announcement: true },
            author: {
              id: 1,
              name: 'Versaa Admin',
              avatar: '/storage/users-avatar/admin.png',
              avatar_border: {
                id: 3,
                name: 'Dark Rose',
                slug: 'darkrose',
                image_url: '/vendor/chatify/borders/darkrose.png',
              },
            },
            status: 'unread',
            created_at: new Date().toISOString(),
          },
        ],
      },
    })

    const wrapper = mount(NotificationsSidebarView, {
      global: { plugins: [pinia] },
    })

    await flushPromises()

    expect(wrapper.find('img[src="/storage/users-avatar/admin.png"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Versaa Admin')
    const border = wrapper.find('.chatify-avatar-border')
    expect(border.exists()).toBe(true)
    expect(border.attributes('src')).toBe('/vendor/chatify/borders/darkrose.png')
  })

  it('falls back to the amber icon when no author is available', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)

    const configStore = useConfigStore(pinia)
    configStore.init({
      authMode: 'authenticated',
      colors: ['#2180f3'],
      user: bootUser(),
    } as any)

    configStore.api!.getNotifications = vi.fn().mockResolvedValue({
      data: {
        data: [
          {
            id: 'notification-2',
            type: 'information',
            title: 'System Message',
            body: 'No author metadata.',
            image_url: null,
            data: {},
            author: null,
            status: 'read',
            created_at: new Date().toISOString(),
          },
        ],
      },
    })

    const wrapper = mount(NotificationsSidebarView, {
      global: { plugins: [pinia] },
    })

    await flushPromises()

    expect(wrapper.find('.chatify-avatar-border').exists()).toBe(false)
    expect(wrapper.find('svg.text-amber-400, .chatify\\:text-amber-400').exists()).toBe(true)
  })
})
