import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import NotificationDetailView from './NotificationDetailView.vue'
import { useConfigStore } from '../../stores/config'
import type { ChatifyNotification } from '../../types'

const notification: ChatifyNotification = {
  id: 'notification-1',
  type: 'information',
  title: 'Server Maintenance',
  body: 'Tonight at 22:00.',
  image_url: '/storage/announcements/poster.png',
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
}

function mountDetail() {
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

  return mount(NotificationDetailView, {
    props: { notification },
    global: { plugins: [pinia] },
  })
}

describe('NotificationDetailView', () => {
  it('shows the author name, avatar and animated border', () => {
    const wrapper = mountDetail()

    expect(wrapper.text()).toContain('Versaa Admin')
    expect(wrapper.find('img[src="/storage/users-avatar/admin.png"]').exists()).toBe(true)
    const border = wrapper.find('.chatify-avatar-border')
    expect(border.exists()).toBe(true)
    expect(border.attributes('src')).toBe('/vendor/chatify/borders/darkrose.png')
  })

  it('renders the attachment as a frameless, portrait-safe image', () => {
    const wrapper = mountDetail()

    const image = wrapper.find('img[alt="Attachment"]')
    expect(image.exists()).toBe(true)

    const classes = image.classes().join(' ')
    expect(classes).toContain('object-contain')
    expect(classes).toContain('w-auto')
    expect(classes).not.toContain('mx-auto')
    expect(classes).toContain('max-w-full')

    // No background/border wrapper around the picture.
    expect(wrapper.html()).not.toContain('bg-black/40')
    expect(wrapper.html()).not.toContain('rounded-2xl chatify:border')
  })
})
