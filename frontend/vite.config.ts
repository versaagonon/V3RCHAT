import { copyFileSync, existsSync, mkdirSync, readdirSync, unlinkSync, writeFileSync } from 'node:fs'
import os from 'node:os'
import { resolve } from 'node:path'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

function getLanIp(): string {
  const ifaces = os.networkInterfaces()
  for (const [name, addrs] of Object.entries(ifaces)) {
    if (/wi-?fi|wireless|wlan/i.test(name) && addrs) {
      const ipv4 = addrs.find((a) => a.family === 'IPv4' && !a.internal)
      if (ipv4) return ipv4.address
    }
  }
  for (const [name, addrs] of Object.entries(ifaces)) {
    if (/vmware|virtual|veth|loopback|wsl/i.test(name) || !addrs) continue
    const ipv4 = addrs.find((a) => a.family === 'IPv4' && !a.internal)
    if (ipv4) return ipv4.address
  }
  return '127.0.0.1'
}

function laravelHotFilePlugin(lanIp: string) {
  if (process.env.VITEST) {
    return { name: 'laravel-hot-file' }
  }
  const hotFilePath = resolve(__dirname, '../chat-app/public/hot')
  return {
    name: 'laravel-hot-file',
    configureServer() {
      const devUrl = `http://${lanIp}:5173`
      try {
        writeFileSync(hotFilePath, devUrl, 'utf-8')
        console.log(`[V3RCHAT] Hot file written: ${hotFilePath} -> ${devUrl}`)
      } catch (err) {
        console.warn('[V3RCHAT] Failed to write hot file:', err)
      }

      const cleanUp = () => {
        try {
          if (existsSync(hotFilePath)) {
            unlinkSync(hotFilePath)
            console.log('[V3RCHAT] Hot file removed.')
          }
        } catch {
          // ignore
        }
      }

      process.on('exit', cleanUp)
      process.on('SIGINT', () => {
        cleanUp()
        process.exit()
      })
      process.on('SIGTERM', () => {
        cleanUp()
        process.exit()
      })
    },
  }
}

function syncBuildToLaravel() {
  return {
    name: 'sync-build-to-laravel',
    closeBundle() {
      const distDir = resolve(__dirname, '../dist')
      const targetDir = resolve(__dirname, '../chat-app/public/vendor/chatify')
      if (existsSync(distDir) && existsSync(targetDir)) {
        for (const file of ['chatify.js', 'chatify.css']) {
          const src = resolve(distDir, file)
          const dest = resolve(targetDir, file)
          if (existsSync(src)) {
            copyFileSync(src, dest)
            console.log(`[V3RCHAT] Synced ${file} -> chat-app/public/vendor/chatify/`)
          }
        }

        // Sync fonts directory
        const srcFonts = resolve(__dirname, 'src/assets/fonts')
        const destFonts = resolve(targetDir, 'fonts')
        if (existsSync(srcFonts)) {
          if (!existsSync(destFonts)) {
            mkdirSync(destFonts, { recursive: true })
          }
          for (const fontFile of readdirSync(srcFonts)) {
            if (fontFile.endsWith('.woff2')) {
              copyFileSync(resolve(srcFonts, fontFile), resolve(destFonts, fontFile))
            }
          }
          console.log(`[V3RCHAT] Synced font files -> chat-app/public/vendor/chatify/fonts/`)
        }

        // Sync branding images
        const srcImages = resolve(__dirname, 'src/assets/images')
        if (existsSync(srcImages)) {
          for (const imgFile of readdirSync(srcImages)) {
            if (imgFile.endsWith('.png') || imgFile.endsWith('.jpg') || imgFile.endsWith('.svg')) {
              copyFileSync(resolve(srcImages, imgFile), resolve(targetDir, imgFile))
            }
          }
          console.log(`[V3RCHAT] Synced branding image files -> chat-app/public/vendor/chatify/`)
        }

        // Sync PWA shell files to chat-app/public/
        const publicDir = resolve(__dirname, 'public')
        const laravelPublicDir = resolve(__dirname, '../chat-app/public')
        if (existsSync(publicDir) && existsSync(laravelPublicDir)) {
          for (const pwaFile of ['manifest.json', 'sw.js', 'offline.html']) {
            const pwaSrc = resolve(publicDir, pwaFile)
            const pwaDest = resolve(laravelPublicDir, pwaFile)
            if (existsSync(pwaSrc)) {
              copyFileSync(pwaSrc, pwaDest)
            }
          }

          const srcIcons = resolve(publicDir, 'icons')
          const destIcons = resolve(laravelPublicDir, 'icons')
          if (existsSync(srcIcons)) {
            if (!existsSync(destIcons)) {
              mkdirSync(destIcons, { recursive: true })
            }
            for (const iconFile of readdirSync(srcIcons)) {
              if (iconFile.endsWith('.png')) {
                copyFileSync(resolve(srcIcons, iconFile), resolve(destIcons, iconFile))
              }
            }
          }
          console.log(`[V3RCHAT] Synced PWA assets (manifest, sw, offline, icons) -> chat-app/public/`)
        }
      }
    },
  }
}

export default defineConfig(({ mode }) => {
  const lanIp = getLanIp()
  const devOrigin = `http://${lanIp}:5173`

  return {
    base: mode === 'development' ? `${devOrigin}/` : './',
    plugins: [vue(), tailwindcss(), laravelHotFilePlugin(lanIp), syncBuildToLaravel()],
    server: {
      host: '0.0.0.0',
      port: 5173,
      strictPort: true,
      cors: true,
      hmr: {
        protocol: 'ws',
        clientPort: 5173,
      },
      headers: {
        'Access-Control-Allow-Origin': '*',
      },
    },
    define: {
      'process.env.NODE_ENV': JSON.stringify(mode === 'production' ? 'production' : 'development'),
    },
  css: {
    postcss: './postcss.config.js',
  },
  build: {
    assetsInlineLimit: 1000000,
    lib: {
      entry: resolve(__dirname, 'src/main.ts'),
      name: 'Chatify',
      formats: ['iife'],
      fileName: () => 'chatify.js',
    },
    outDir: resolve(__dirname, '../dist'),
    emptyOutDir: false,
    cssCodeSplit: false,
    rollupOptions: {
      output: {
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'chatify.css'
          }
          return 'fonts/[name][extname]'
        },
        inlineDynamicImports: true,
      },
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/i18n/testSetup.ts'],
  },
}
})