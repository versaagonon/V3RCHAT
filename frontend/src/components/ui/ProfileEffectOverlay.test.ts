import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import type { ProfileEffect } from '../../types'
import { clearProfileEffectBlobCache } from '../../utils/profileEffectBlobs'
import ProfileEffectOverlay from './ProfileEffectOverlay.vue'

function effect(overrides: Partial<ProfileEffect> = {}): ProfileEffect {
  return {
    id: 1,
    name: 'Dark Rose',
    slug: 'darkrose',
    thumbnail_url: '/effects/darkrose-pair-1.png',
    pair1_url: '/effects/darkrose-pair-1.png',
    pair2_url: '/effects/darkrose-pair-2.png',
    pair1_duration_ms: 3000,
    pair2_duration_ms: 3000,
    is_system: true,
    ...overrides,
  }
}

let objectUrlSeq = 0
let fetchMock: ReturnType<typeof vi.fn>

beforeEach(() => {
  vi.useFakeTimers()
  objectUrlSeq = 0
  clearProfileEffectBlobCache()

  fetchMock = vi.fn(async () => ({
    ok: true,
    blob: async () => new Blob(['apng'], { type: 'image/png' }),
  }))
  vi.stubGlobal('fetch', fetchMock)

  Object.defineProperty(URL, 'createObjectURL', {
    configurable: true,
    value: vi.fn(() => `blob:effect-${++objectUrlSeq}`),
  })
  Object.defineProperty(URL, 'revokeObjectURL', {
    configurable: true,
    value: vi.fn(),
  })

  vi.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue({
    drawImage: vi.fn(),
  } as unknown as CanvasRenderingContext2D)
  vi.spyOn(HTMLCanvasElement.prototype, 'toDataURL').mockReturnValue('data:image/png;base64,still')
})

afterEach(() => {
  vi.restoreAllMocks()
  vi.useRealTimers()
  vi.unstubAllGlobals()
})

function markLoaded(img: Element) {
  const el = img as HTMLImageElement
  Object.defineProperty(el, 'naturalWidth', { value: 450, configurable: true })
  Object.defineProperty(el, 'naturalHeight', { value: 880, configurable: true })
}

describe('ProfileEffectOverlay', () => {
  it('plays a freshly minted pair-1 resource, then swaps to pair-2', async () => {
    const wrapper = mount(ProfileEffectOverlay, {
      props: { effect: effect({ pair1_url: '/effects/first-play-pair-1.png' }) },
    })
    await vi.advanceTimersByTimeAsync(0)

    expect(wrapper.find('img').attributes('src')).toBe('blob:effect-1')

    // The countdown starts when the object URL has painted its first frame.
    await wrapper.find('img').trigger('load')

    await vi.advanceTimersByTimeAsync(2959)
    expect(wrapper.find('img').attributes('src')).toBe('blob:effect-1')

    await vi.advanceTimersByTimeAsync(1)
    expect(wrapper.find('img').attributes('src')).toMatch(/^blob:effect-/)
    expect(wrapper.find('img').attributes('src')).not.toBe('blob:effect-1')
  })

  it('mints a new object URL on every open and reuses the cached Blob', async () => {
    const props = { effect: effect({ pair1_url: '/effects/reopen-pair-1.png' }) }

    const first = mount(ProfileEffectOverlay, { props })
    await vi.advanceTimersByTimeAsync(0)
    const firstSrc = first.find('img').attributes('src')
    first.unmount()

    const second = mount(ProfileEffectOverlay, { props })
    await vi.advanceTimersByTimeAsync(0)
    const secondSrc = second.find('img').attributes('src')

    expect(firstSrc).toBe('blob:effect-1')
    expect(secondSrc).toBe('blob:effect-2')
    expect(secondSrc).not.toBe(firstSrc)
    // pair-1 plus the prefetched pair-2 Blob are fetched once; the second open
    // reuses both cached Blobs.
    expect(fetchMock).toHaveBeenCalledTimes(2)
  })

  it('rests pair-2 for three seconds on its last frame, then replays it', async () => {
    const wrapper = mount(ProfileEffectOverlay, { props: { effect: effect() } })
    await vi.advanceTimersByTimeAsync(0)

    await wrapper.find('img').trigger('load')
    await vi.advanceTimersByTimeAsync(2960)

    const firstPair2 = wrapper.find('img').attributes('src')
    expect(firstPair2).toMatch(/^blob:effect-/)

    markLoaded(wrapper.find('img').element)
    await wrapper.find('img').trigger('load')
    await vi.advanceTimersByTimeAsync(2959)
    expect(wrapper.find('img').attributes('src')).toBe(firstPair2)

    await vi.advanceTimersByTimeAsync(1)
    // Pair-2 finished its cycle and freezes on its final frame.
    expect(wrapper.find('img').attributes('src')).toBe('data:image/png;base64,still')

    await vi.advanceTimersByTimeAsync(2999)
    expect(wrapper.find('img').attributes('src')).toBe('data:image/png;base64,still')

    // After the idle gap a fresh object URL restarts pair-2 from frame 0.
    await vi.advanceTimersByTimeAsync(1)
    const secondPair2 = wrapper.find('img').attributes('src')
    expect(secondPair2).toMatch(/^blob:effect-/)
    expect(secondPair2).not.toBe(firstPair2)
  })

  it('restarts from pair-1 when the equipped effect changes', async () => {
    const wrapper = mount(ProfileEffectOverlay, {
      props: { effect: effect({ pair1_url: '/effects/change-a-pair-1.png' }) },
    })
    await vi.advanceTimersByTimeAsync(0)
    const firstSrc = wrapper.find('img').attributes('src')

    await wrapper.find('img').trigger('load')
    await vi.advanceTimersByTimeAsync(2960)
    expect(wrapper.find('img').attributes('src')).toMatch(/^blob:effect-/)

    await wrapper.setProps({
      effect: effect({
        id: 2,
        name: 'Venom',
        pair1_url: '/effects/change-b-pair-1.png',
        pair2_url: '/effects/change-b-pair-2.png',
      }),
    })
    await vi.advanceTimersByTimeAsync(0)

    const nextSrc = wrapper.find('img').attributes('src')
    expect(nextSrc).toMatch(/^blob:effect-/)
    expect(nextSrc).not.toBe(firstSrc)
  })

  it('renders nothing and fetches nothing when no effect is equipped', async () => {
    const wrapper = mount(ProfileEffectOverlay, { props: { effect: null } })
    await vi.advanceTimersByTimeAsync(0)

    expect(wrapper.find('[data-chatify-profile-effect]').exists()).toBe(false)
    expect(fetchMock).not.toHaveBeenCalled()
  })
})
