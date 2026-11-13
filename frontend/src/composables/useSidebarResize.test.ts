import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import {
  SIDEBAR_DEFAULT_WIDTH,
  SIDEBAR_MAX_WIDTH,
  SIDEBAR_MIN_WIDTH,
  SIDEBAR_STORAGE_KEY,
  useSidebarResize,
} from './useSidebarResize'

describe('useSidebarResize', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  afterEach(() => {
    localStorage.clear()
    document.body.classList.remove('chatify-resizing-active')
  })

  it('initializes with default width when localStorage is empty', () => {
    const { sidebarWidth, isResizing } = useSidebarResize()
    expect(sidebarWidth.value).toBe(SIDEBAR_DEFAULT_WIDTH)
    expect(isResizing.value).toBe(false)
  })

  it('restores valid width from localStorage', () => {
    localStorage.setItem(SIDEBAR_STORAGE_KEY, '420')
    const { sidebarWidth } = useSidebarResize()
    expect(sidebarWidth.value).toBe(420)
  })

  it('clamps stored width within min and max boundaries', () => {
    localStorage.setItem(SIDEBAR_STORAGE_KEY, '100')
    const low = useSidebarResize()
    expect(low.sidebarWidth.value).toBe(SIDEBAR_MIN_WIDTH)

    localStorage.setItem(SIDEBAR_STORAGE_KEY, '9999')
    const high = useSidebarResize()
    expect(high.sidebarWidth.value).toBe(SIDEBAR_MAX_WIDTH)
  })

  it('resets width to default and persists it', () => {
    localStorage.setItem(SIDEBAR_STORAGE_KEY, '480')
    const { sidebarWidth, resetWidth } = useSidebarResize()
    expect(sidebarWidth.value).toBe(480)

    resetWidth()
    expect(sidebarWidth.value).toBe(SIDEBAR_DEFAULT_WIDTH)
    expect(localStorage.getItem(SIDEBAR_STORAGE_KEY)).toBe(String(SIDEBAR_DEFAULT_WIDTH))
  })

  it('handles mouse resize drag workflow', () => {
    const { sidebarWidth, isResizing, startResize } = useSidebarResize()

    // Start resize with primary mouse button
    startResize(new MouseEvent('mousedown', { button: 0, clientX: 380 }))
    expect(isResizing.value).toBe(true)
    expect(document.body.classList.contains('chatify-resizing-active')).toBe(true)

    // Simulate drag movement (+50px)
    window.dispatchEvent(new MouseEvent('mousemove', { clientX: 430 }))
    expect(sidebarWidth.value).toBe(430)

    // End resize
    window.dispatchEvent(new MouseEvent('mouseup'))
    expect(isResizing.value).toBe(false)
    expect(document.body.classList.contains('chatify-resizing-active')).toBe(false)
    expect(localStorage.getItem(SIDEBAR_STORAGE_KEY)).toBe('430')
  })
})
