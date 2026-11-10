import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { ref } from 'vue'
import SearchField from './SearchField.vue'

describe('SearchField', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders input with type search and shows clear button only when non-empty', async () => {
    const Component = {
      components: { SearchField },
      setup() {
        const query = ref('')
        return { query }
      },
      template: '<SearchField v-model="query" placeholder="Search..." clear-label="Clear" />',
    }

    const wrapper = mount(Component)
    const input = wrapper.find('input[type="search"]')
    expect(input.exists()).toBe(true)
    expect(input.classes()).toContain('chatify-search-field-input')

    // Initially empty: clear button should not exist
    expect(wrapper.find('button').exists()).toBe(false)

    // Type query
    await input.setValue('secret message')
    expect(wrapper.find('button').exists()).toBe(true)

    // Click clear button
    await wrapper.find('button').trigger('click')
    expect((input.element as HTMLInputElement).value).toBe('')
    expect(wrapper.find('button').exists()).toBe(false)
  })
})
