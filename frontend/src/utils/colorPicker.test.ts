import { describe, expect, it } from 'vitest'
import {
  hexToHsv,
  hexToRgb,
  hsvToHex,
  hsvToRgb,
  rgbToHex,
  rgbToHsv,
} from './colorPicker'

describe('colorPicker utils', () => {
  it('converts hex to rgb and rgb to hex', () => {
    expect(hexToRgb('#FF0000')).toEqual({ r: 255, g: 0, b: 0 })
    expect(hexToRgb('#00FF00')).toEqual({ r: 0, g: 255, b: 0 })
    expect(hexToRgb('#0000FF')).toEqual({ r: 0, g: 0, b: 255 })
    expect(hexToRgb('18181b')).toEqual({ r: 24, g: 24, b: 27 })

    expect(rgbToHex(255, 0, 0)).toBe('#FF0000')
    expect(rgbToHex(0, 255, 0)).toBe('#00FF00')
    expect(rgbToHex(0, 0, 255)).toBe('#0000FF')
  })

  it('converts between rgb and hsv accurately', () => {
    const redHsv = rgbToHsv(255, 0, 0)
    expect(redHsv.h).toBe(0)
    expect(redHsv.s).toBe(100)
    expect(redHsv.v).toBe(100)

    const restoredRed = hsvToRgb(redHsv.h, redHsv.s, redHsv.v)
    expect(restoredRed).toEqual({ r: 255, g: 0, b: 0 })

    const purpleRgb = hexToRgb('#800080')
    const hsv = rgbToHsv(purpleRgb.r, purpleRgb.g, purpleRgb.b)
    const restored = hsvToRgb(hsv.h, hsv.s, hsv.v)
    expect(Math.abs(restored.r - purpleRgb.r)).toBeLessThanOrEqual(2)
    expect(Math.abs(restored.g - purpleRgb.g)).toBeLessThanOrEqual(2)
    expect(Math.abs(restored.b - purpleRgb.b)).toBeLessThanOrEqual(2)
  })
})
