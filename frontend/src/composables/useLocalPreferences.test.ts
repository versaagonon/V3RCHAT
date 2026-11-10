import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { setAllowedFonts } from '../themes/fonts'
import { setAllowedThemes } from '../themes/presets'
import { setWallpaperPatterns } from '../themes/patterns'
import {
  applyPreferences,
  defaultPreferences,
  loadPreferences,
  preferencesForServer,
  resolveTokenSet,
} from './useLocalPreferences'

const STORAGE_KEY = 'chatify.preferences'

describe('useLocalPreferences', () => {
  beforeEach(() => {
    setWallpaperPatterns([
      { id: 'bubbles', name: 'Bubbles', url: '/vendor/chatify/patterns/bubbles.svg' },
    ])
    setAllowedThemes(['classic', 'day', 'tinted', 'night'])
    setAllowedFonts(['system', 'georgia'])
    localStorage.clear()
    document.documentElement.removeAttribute('style')
    document.body.classList.remove('chatify-dark')
    document.body.innerHTML = '<div id="chatify-app"></div>'
  })

  afterEach(() => {
    localStorage.clear()
    document.documentElement.removeAttribute('style')
    document.body.innerHTML = ''
  })

  it('migrates legacy messengerColor to accentColor', () => {
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({ darkMode: false, messengerColor: '#2180f3' }),
    )

    const preferences = loadPreferences()
    expect(preferences.accentColor).toBe('#2180f3')
    expect(preferences.themeId).toBe('classic')
  })

  it('migrates darkMode true to night theme', () => {
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({ darkMode: true, messengerColor: '#2180f3' }),
    )

    expect(loadPreferences().themeId).toBe('night')
  })

  it('applies theme tokens on document root', () => {
    applyPreferences({
      ...defaultPreferences(),
      themeId: 'night',
    })

    expect(document.documentElement.style.getPropertyValue('--chatify-color-chatify-primary')).toBeTruthy()
    expect(document.body.classList.contains('chatify-dark')).toBe(true)
  })

  it('applies accent color to primary token', () => {
    applyPreferences({
      ...defaultPreferences(),
      themeId: 'day',
      accentColor: '#2180f3',
    })

    expect(document.documentElement.style.getPropertyValue('--chatify-color-chatify-primary')).toBe('#2180f3')
  })

  it('applies chat background pattern vars at accent tint', () => {
    applyPreferences({
      ...defaultPreferences('#2180f3'),
      wallpaper: {
        kind: 'pattern',
        patternId: 'bubbles',
        imageUrl: null,
        blurEnabled: false,
        blurAmount: 50,
      },
    })

    expect(document.documentElement.style.getPropertyValue('--chatify-chat-bg-enabled')).toBe('1')
    expect(document.documentElement.style.getPropertyValue('--chatify-chat-bg-pattern-tint')).toBe('#2180f3')
  })

  it('applies font family css var', () => {
    applyPreferences({
      ...defaultPreferences(),
      fontFamily: 'georgia',
    })

    expect(document.documentElement.style.getPropertyValue('--chatify-font-family')).toContain('Georgia')
  })

  it('serializes server-safe preferences without image url', () => {
    const payload = preferencesForServer({
      ...defaultPreferences(),
      wallpaper: {
        kind: 'image',
        patternId: 'bubbles',
        imageUrl: 'https://example.test/bg.jpg',
        blurEnabled: true,
        blurAmount: 65,
      },
    })

    expect(payload.wallpaper).toEqual({
      kind: 'image',
      patternId: 'bubbles',
      blurEnabled: true,
      blurAmount: 65,
      darknessAmount: 0,
    })
    expect((payload.wallpaper as { imageUrl?: string }).imageUrl).toBeUndefined()
  })

  it('applies wallpaper blur and darkness css vars when enabled', () => {
    applyPreferences({
      ...defaultPreferences(),
      wallpaper: {
        kind: 'image',
        patternId: 'bubbles',
        imageUrl: 'https://example.test/bg.jpg',
        blurEnabled: true,
        blurAmount: 50,
        darknessAmount: 40,
      },
    })

    expect(document.documentElement.style.getPropertyValue('--chatify-chat-bg-blur')).toBe('15px')
    expect(document.documentElement.style.getPropertyValue('--chatify-chat-bg-blur-enabled')).toBe('1')
    expect(document.documentElement.style.getPropertyValue('--chatify-chat-bg-darkness')).toBe('0.40')
  })

  it('does not apply wallpaper blur for patterns', () => {
    applyPreferences({
      ...defaultPreferences(),
      wallpaper: {
        kind: 'pattern',
        patternId: 'bubbles',
        imageUrl: null,
        blurEnabled: true,
        blurAmount: 80,
      },
    })

    expect(document.documentElement.style.getPropertyValue('--chatify-chat-bg-blur')).toBe('0px')
    expect(document.documentElement.style.getPropertyValue('--chatify-chat-bg-blur-enabled')).toBe('0')
  })

  it('resolves accent into token set', () => {
    const tokens = resolveTokenSet({
      ...defaultPreferences(),
      themeId: 'classic',
      accentColor: '#ff0000',
    })

    expect(tokens.primary).toBe('#ff0000')
    expect(tokens.bubbleOut).toBeTruthy()
  })

  it('applies font size, ui scale, and density classes', () => {
    applyPreferences({
      ...defaultPreferences(),
      fontSize: 16,
      uiScale: 110,
      density: 'compact',
    })

    expect(document.documentElement.style.getPropertyValue('--chatify-font-size-base')).toBe('16px')
    expect(document.documentElement.style.getPropertyValue('--chatify-text-sm')).toBe('1.0000rem')
    expect(document.documentElement.style.getPropertyValue('--chatify-text-xs')).toBe('0.8571rem')
    expect(document.documentElement.style.getPropertyValue('--chatify-ui-scale')).toBe('1.1')
    expect(document.documentElement.style.fontSize).toBe('17.6px')
    expect(document.body.classList.contains('chatify-density-compact')).toBe(true)
    expect(document.body.classList.contains('chatify-density-comfortable')).toBe(false)
  })

  it('serializes layout and font fields for server', () => {
    const payload = preferencesForServer({
      ...defaultPreferences(),
      fontSize: 18,
      uiScale: 90,
      density: 'spacious',
    })

    expect(payload.fontSize).toBe(18)
    expect(payload.uiScale).toBe(90)
    expect(payload.density).toBe('spacious')
  })
})
