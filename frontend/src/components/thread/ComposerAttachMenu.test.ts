import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import ComposerAttachMenu from './ComposerAttachMenu.vue'

describe('ComposerAttachMenu', () => {
  it('toggles the attach menu open and emits open event when button is clicked', async () => {
    const wrapper = mount(ComposerAttachMenu, {
      global: {
        mocks: {
          $t: (key: string) => key,
        },
      },
    })

    expect(wrapper.find('.chatify-composer-attach-menu').exists()).toBe(false)

    const toggleBtn = wrapper.find('button.chatify-composer-icon-btn')
    expect(toggleBtn.exists()).toBe(true)

    await toggleBtn.trigger('click')

    expect(wrapper.emitted('open')).toBeTruthy()
    expect(wrapper.find('.chatify-composer-attach-menu').exists()).toBe(true)

    const items = wrapper.findAll('.chatify-composer-attach-item')
    expect(items.length).toBe(2)

    await items[0].trigger('click')
    expect(wrapper.emitted('pick-media')).toBeTruthy()
    expect(wrapper.find('.chatify-composer-attach-menu').exists()).toBe(false)
  })

  it('emits pick-document and closes on document item click', async () => {
    const wrapper = mount(ComposerAttachMenu, {
      global: {
        mocks: {
          $t: (key: string) => key,
        },
      },
    })

    const toggleBtn = wrapper.find('button.chatify-composer-icon-btn')
    await toggleBtn.trigger('click')
    expect(wrapper.find('.chatify-composer-attach-menu').exists()).toBe(true)

    const items = wrapper.findAll('.chatify-composer-attach-item')
    await items[1].trigger('click')
    expect(wrapper.emitted('pick-document')).toBeTruthy()
    expect(wrapper.find('.chatify-composer-attach-menu').exists()).toBe(false)
  })

  it('closes on escape key', async () => {
    const wrapper = mount(ComposerAttachMenu, {
      attachTo: document.body,
      global: {
        mocks: {
          $t: (key: string) => key,
        },
      },
    })

    const toggleBtn = wrapper.find('button.chatify-composer-icon-btn')
    await toggleBtn.trigger('click')
    expect(wrapper.find('.chatify-composer-attach-menu').exists()).toBe(true)

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    await wrapper.vm.$nextTick()
    expect(wrapper.find('.chatify-composer-attach-menu').exists()).toBe(false)

    wrapper.unmount()
  })
})

