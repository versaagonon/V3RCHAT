import { beforeEach, describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import SettingsWallpaperView from './SettingsWallpaperView.vue'
import { useConfigStore } from '../../stores/config'

describe('SettingsWallpaperView', () => {
  beforeEach(() => {
    document.body.innerHTML = '<div id="chatify-app"></div>'
  })

  it('renders live chat preview and controls for uploaded wallpaper with blur and darkness', async () => {
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

    // Set custom wallpaper image with blur and darkness
    configStore.setWallpaper({
      kind: 'image',
      imageUrl: 'https://example.test/wallpaper.jpg',
      blurEnabled: true,
      blurAmount: 60,
      darknessAmount: 35,
    })

    const wrapper = mount(SettingsWallpaperView, {
      global: {
        plugins: [pinia],
      },
    })

    expect(wrapper.exists()).toBe(true)

    // Live preview card is rendered
    expect(wrapper.text()).toContain('Live Preview')
    expect(wrapper.text()).toContain('Blur: 60%')
    expect(wrapper.text()).toContain('Darkness: 35%')

    // Blur wallpaper switch exists
    expect(wrapper.find('button[role="switch"]').exists()).toBe(true)

    // Darkness slider exists
    const ranges = wrapper.findAll('input[type="range"]')
    expect(ranges.length).toBe(2) // blur slider & darkness slider

    // Preset buttons exist (0%, 20%, 40%, 60%, 80%)
    expect(wrapper.text()).toContain('20%')
    expect(wrapper.text()).toContain('40%')
    expect(wrapper.text()).toContain('60%')
  })
})
