import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import AccentSwatchRow from './AccentSwatchRow.vue'
import { useConfigStore } from '../../stores/config'

describe('AccentSwatchRow', () => {
  let pinia: ReturnType<typeof createPinia>
  let configStore: ReturnType<typeof useConfigStore>

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    configStore = useConfigStore()
    configStore.init({
      authMode: 'authenticated',
      colors: ['#2180f3', '#ff0055', '#10b981'],
      user: { id: 1, attributes: { name: 'Versaa' } },
    } as any)
  })

  it('renders color swatches inside grey container and selects color', async () => {
    const wrapper = mount(AccentSwatchRow, {
      props: {
        themeId: 'black_white',
      },
      global: {
        plugins: [pinia],
      },
    })

    const box = wrapper.find('.chatify-accent-swatches-box')
    expect(box.exists()).toBe(true)

    const buttons = wrapper.findAll('button.chatify-accent-swatch')
    expect(buttons.length).toBeGreaterThan(1)

    // Click on a swatch button
    await buttons[0].trigger('click')
    expect(configStore.preferences.themeId).toBe('black_white')
  })

  it('opens and closes custom color picker when rainbow trigger is clicked', async () => {
    const wrapper = mount(AccentSwatchRow, {
      props: {
        themeId: 'black_white',
      },
      global: {
        plugins: [pinia],
      },
    })

    expect(wrapper.findComponent({ name: 'CustomColorPicker' }).exists()).toBe(false)

    // Last button is the rainbow custom picker trigger
    const buttons = wrapper.findAll('button.chatify-accent-swatch')
    const trigger = buttons[buttons.length - 1]
    await trigger.trigger('click')
    await flushPromises()

    const picker = wrapper.findComponent({ name: 'CustomColorPicker' })
    expect(picker.exists()).toBe(true)

    // Close it via close event
    await picker.vm.$emit('close')
    await flushPromises()

    expect(wrapper.findComponent({ name: 'CustomColorPicker' }).exists()).toBe(false)
  })
})
