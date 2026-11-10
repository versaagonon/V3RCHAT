import { beforeEach, describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import SettingsSidebarView from './SettingsSidebarView.vue'
import { useConfigStore } from '../../stores/config'

describe('SettingsSidebarView', () => {
  beforeEach(() => {
    document.body.innerHTML = '<div id="chatify-app"></div>'
  })

  it('renders settings root and navigates through sub-views inside sidebar', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)

    const configStore = useConfigStore(pinia)
    configStore.init({
      authMode: 'authenticated',
      colors: ['#ffffff', '#25d366', '#56a7f5'],
      user: {
        id: 1,
        type: 'user',
        attributes: {
          name: 'Versaa',
          email: 'versaa@v3rchat.io',
          avatar: null,
          active_status: true,
        },
      },
      features: {
        themes: true,
        colors: true,
        fonts: true,
        wallpaper: true,
        giphy: false,
        groups: true,
        sounds: true,
        saved_messages: true,
      },
    } as any)

    const wrapper = mount(SettingsSidebarView, {
      global: {
        plugins: [pinia],
      },
    })

    expect(wrapper.exists()).toBe(true)
    expect(wrapper.classes()).toContain('chatify-sidebar-settings')
    expect(wrapper.text()).toContain('Versaa')

    // Navigate to Themes view
    const themesRow = wrapper.findAll('.chatify-settings-row').find((row) =>
      row.text().includes('Themes') || row.text().includes('Monochrome') || row.text().includes('Black White')
    )
    if (themesRow) {
      await themesRow.trigger('click')
      await wrapper.vm.$nextTick()
      expect(wrapper.text()).toContain('Themes')
    }
  })
})
