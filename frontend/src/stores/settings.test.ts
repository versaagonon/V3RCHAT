import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import axios from 'axios'
import { useSettingsStore } from './settings'

vi.mock('axios')

describe('useSettingsStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('initializes with default saving and loggingOut as false', () => {
    const store = useSettingsStore()
    expect(store.saving).toBe(false)
    expect(store.loggingOut).toBe(false)
  })

  it('triggers logout POST request', async () => {
    vi.mocked(axios.post).mockResolvedValueOnce({ data: { status: 'success' } })
    const store = useSettingsStore()

    // Mock window location
    const originalLocation = window.location
    // @ts-expect-error mock window location
    delete window.location
    window.location = { href: '' } as unknown as Location

    await store.logout()

    expect(axios.post).toHaveBeenCalledWith(
      '/logout',
      {},
      expect.objectContaining({
        headers: expect.objectContaining({
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }),
      }),
    )
    expect(window.location.href).toBe('/')

    window.location = originalLocation
  })
})
