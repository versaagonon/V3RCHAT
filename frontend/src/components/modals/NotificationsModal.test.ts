import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import NotificationsModal from './NotificationsModal.vue'
import { useUiStore } from '../../stores/ui'
import { useNotificationsStore } from '../../stores/notifications'
import { useConfigStore } from '../../stores/config'

describe('NotificationsModal', () => {
  beforeEach(() => {
    document.body.innerHTML = '<div id="chatify-app"></div>'
  })

  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('renders notifications with All, Information, and Request tabs', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)

    const uiStore = useUiStore(pinia)
    const configStore = useConfigStore(pinia)
    const notificationsStore = useNotificationsStore(pinia)

    configStore.init({
      authMode: 'authenticated',
      colors: ['#2180f3'],
      user: { id: 1, attributes: { name: 'Versaa', email: 'versaa@test.com' } },
    } as any)

    if (configStore.api) {
      configStore.api.getNotifications = vi.fn().mockResolvedValue({
        data: {
          data: [
            {
              id: 'notif-1',
              type: 'information',
              title: 'System Announcement',
              body: 'V3RCHAT maintenance scheduled.',
              status: 'unread',
              created_at: new Date().toISOString(),
            },
            {
              id: 'notif-2',
              type: 'request',
              title: 'Chat request from Shadow',
              body: 'Greetings sovereign!',
              data: {
                conversation_id: 'conv-123',
                sender_id: 2,
                sender_name: 'Shadow',
                sender_username: 'shadow_net',
              },
              status: 'pending',
              created_at: new Date().toISOString(),
            },
          ],
          meta: {
            total_count: 2,
            unread_info_count: 1,
            pending_requests_count: 1,
            total_badge_count: 2,
          },
        },
      })
    }

    uiStore.openModal('notifications')

    mount(NotificationsModal, {
      global: {
        plugins: [pinia],
      },
      attachTo: document.body,
    })

    await flushPromises()

    const app = document.querySelector('#chatify-app')
    expect(app).toBeTruthy()
    expect(app!.textContent).toContain('Notifications')
    expect(app!.textContent).toContain('All')
    expect(app!.textContent).toContain('Information')
    expect(app!.textContent).toContain('Request')
    expect(app!.textContent).toContain('System Announcement')
    expect(app!.textContent).toContain('Shadow')
    expect(app!.textContent).toContain('Incoming Chat Request')
  })

  it('switches tabs and filters request cards exclusively', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)

    const uiStore = useUiStore(pinia)
    const configStore = useConfigStore(pinia)
    const notificationsStore = useNotificationsStore(pinia)

    configStore.init({
      authMode: 'authenticated',
      colors: ['#2180f3'],
      user: { id: 1, attributes: { name: 'Versaa' } },
    } as any)

    if (configStore.api) {
      configStore.api.getNotifications = vi.fn().mockResolvedValue({
        data: {
          data: [
            {
              id: 'notif-1',
              type: 'information',
              title: 'System Announcement',
              body: 'Information notice only',
              status: 'read',
              created_at: new Date().toISOString(),
            },
            {
              id: 'notif-2',
              type: 'request',
              title: 'Chat request from Alice',
              body: 'Hey there!',
              data: {
                conversation_id: 'conv-456',
                sender_name: 'Alice',
              },
              status: 'pending',
              created_at: new Date().toISOString(),
            },
          ],
          meta: {
            total_count: 2,
            unread_info_count: 0,
            pending_requests_count: 1,
            total_badge_count: 1,
          },
        },
      })
    }

    uiStore.openModal('notifications')

    mount(NotificationsModal, {
      global: {
        plugins: [pinia],
      },
      attachTo: document.body,
    })

    await flushPromises()

    const app = document.querySelector('#chatify-app')
    expect(app).toBeTruthy()

    const buttons = Array.from(app!.querySelectorAll('button'))
    const requestTabButton = buttons.find((b) => b.textContent?.includes('Request'))
    expect(requestTabButton).toBeDefined()

    requestTabButton!.dispatchEvent(new MouseEvent('click', { bubbles: true }))
    await new Promise((r) => setTimeout(r, 20))

    expect(app!.textContent).toContain('Alice')
    expect(app!.textContent).not.toContain('System Announcement')
  })

  it('navigates to notification detail view when item is clicked and goes back', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)

    const uiStore = useUiStore(pinia)
    const configStore = useConfigStore(pinia)

    configStore.init({
      authMode: 'authenticated',
      colors: ['#2180f3'],
      user: { id: 1, attributes: { name: 'Versaa' } },
    } as any)

    if (configStore.api) {
      configStore.api.getNotifications = vi.fn().mockResolvedValue({
        data: {
          data: [
            {
              id: 'notif-99',
              type: 'information',
              title: 'Detailed Announcement Title',
              body: 'Very long announcement description body content that should show in detail view.',
              status: 'unread',
              created_at: new Date().toISOString(),
            },
          ],
          meta: {
            total_count: 1,
            unread_info_count: 1,
            pending_requests_count: 0,
            total_badge_count: 1,
          },
        },
      })
      configStore.api.markNotificationRead = vi.fn().mockResolvedValue({ data: { success: true } })
    }

    uiStore.openModal('notifications')

    mount(NotificationsModal, {
      global: {
        plugins: [pinia],
      },
      attachTo: document.body,
    })

    await flushPromises()

    const app = document.querySelector('#chatify-app')
    expect(app).toBeTruthy()
    expect(app!.textContent).toContain('Detailed Announcement Title')

    // Click on the notification item
    const itemCard = app!.querySelector('.chatify\\:cursor-pointer')
    expect(itemCard).toBeTruthy()

    itemCard!.dispatchEvent(new MouseEvent('click', { bubbles: true }))
    await flushPromises()

    // Should now show detail view with back button
    expect(app!.textContent).toContain('Notification Details')
    expect(app!.textContent).toContain('Back')
    expect(app!.textContent).toContain('Very long announcement description body content')

    // Click back button
    const backBtn = Array.from(app!.querySelectorAll('button')).find((b) => b.textContent?.includes('Back'))
    expect(backBtn).toBeDefined()

    backBtn!.dispatchEvent(new MouseEvent('click', { bubbles: true }))
    await flushPromises()

    // Should be back to the list view
    expect(app!.textContent).toContain('Notifications')
    expect(app!.textContent).toContain('All')
  })
})
