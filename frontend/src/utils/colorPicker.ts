export interface HsvColor {
  h: number // 0 - 360
  s: number // 0 - 100
  v: number // 0 - 100
}

export interface RgbColor {
  r: number // 0 - 255
  g: number // 0 - 255
  b: number // 0 - 255
}

export function hexToRgb(hex: string): RgbColor {
  let clean = hex.replace('#', '').trim()
  if (clean.length === 3) {
    clean = clean.split('').map((c) => c + c).join('')
  }
  if (clean.length !== 6) {
    return { r: 255, g: 255, b: 255 }
  }
  const num = parseInt(clean, 16)
  if (isNaN(num)) return { r: 255, g: 255, b: 255 }
  return {
    r: (num >> 16) & 255,
    g: (num >> 8) & 255,
    b: num & 255,
  }
}

export function rgbToHex(r: number, g: number, b: number): string {
  const clamp = (n: number) => Math.max(0, Math.min(255, Math.round(n)))
  const toHex = (n: number) => clamp(n).toString(16).padStart(2, '0')
  return `#${toHex(r)}${toHex(g)}${toHex(b)}`.toUpperCase()
}

export function rgbToHsv(r: number, g: number, b: number): HsvColor {
  const rNorm = r / 255
  const gNorm = g / 255
  const bNorm = b / 255
  const max = Math.max(rNorm, gNorm, bNorm)
  const min = Math.min(rNorm, gNorm, bNorm)
  const diff = max - min
  let h = 0

  if (diff !== 0) {
    if (max === rNorm) {
      h = ((gNorm - bNorm) / diff) % 6
    } else if (max === gNorm) {
      h = (bNorm - rNorm) / diff + 2
    } else {
      h = (rNorm - gNorm) / diff + 4
    }
    h = Math.round(h * 60)
    if (h < 0) h += 360
  }

  const s = max === 0 ? 0 : Math.round((diff / max) * 100)
  const v = Math.round(max * 100)

  return { h, s, v }
}

export function hsvToRgb(h: number, s: number, v: number): RgbColor {
  const sNorm = s / 100
  const vNorm = v / 100
  const c = vNorm * sNorm
  const x = c * (1 - Math.abs(((h / 60) % 2) - 1))
  const m = vNorm - c

  let rPrime = 0
  let gPrime = 0
  let bPrime = 0

  if (h >= 0 && h < 60) {
    rPrime = c
    gPrime = x
    bPrime = 0
  } else if (h >= 60 && h < 120) {
    rPrime = x
    gPrime = c
    bPrime = 0
  } else if (h >= 120 && h < 180) {
    rPrime = 0
    gPrime = c
    bPrime = x
  } else if (h >= 180 && h < 240) {
    rPrime = 0
    gPrime = x
    bPrime = c
  } else if (h >= 240 && h < 300) {
    rPrime = x
    gPrime = 0
    bPrime = c
  } else {
    rPrime = c
    gPrime = 0
    bPrime = x
  }

  return {
    r: Math.round((rPrime + m) * 255),
    g: Math.round((gPrime + m) * 255),
    b: Math.round((bPrime + m) * 255),
  }
}

export function hexToHsv(hex: string): HsvColor {
  const rgb = hexToRgb(hex)
  return rgbToHsv(rgb.r, rgb.g, rgb.b)
}

export function hsvToHex(h: number, s: number, v: number): string {
  const rgb = hsvToRgb(h, s, v)
  return rgbToHex(rgb.r, rgb.g, rgb.b)
}
