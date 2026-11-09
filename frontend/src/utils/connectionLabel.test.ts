import { describe, expect, it } from 'vitest'
import { connectionStatusLabel, sidebarSubtitleLabel } from './connectionLabel'

describe('connectionLabel', () => {
  it('returns status labels for degraded states', () => {
    expect(connectionStatusLabel('connecting')).toBe('Connecting…')
    expect(connectionStatusLabel('updating')).toBe('Updating…')
    expect(connectionStatusLabel('online')).toBeNull()
  })

  it('returns V3RCHAT when online', () => {
    expect(sidebarSubtitleLabel('online')).toBe('V3RCHAT')
    expect(sidebarSubtitleLabel('connecting')).toBe('Connecting…')
    expect(sidebarSubtitleLabel('updating')).toBe('Updating…')
  })
})
