import { copyFileSync, existsSync, mkdirSync, readdirSync, unlinkSync, writeFileSync } from 'node:fs'
import { resolve } from 'node:path'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

function laravelHotFilePlugin() {
  const hotFilePath = resolve(__dirname, '../chat-app/public/hot')
  return {
    name: 'laravel-hot-file',
    configureServer() {
      const devUrl = 'http://127.0.0.1:5173'
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
      }
    },
  }
}

export default defineConfig(({ mode }) => ({
  base: mode === 'development' ? 'http://127.0.0.1:5173/' : './',
  plugins: [vue(), tailwindcss(), laravelHotFilePlugin(), syncBuildToLaravel()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    cors: true,
    origin: 'http://127.0.0.1:5173',
    hmr: {
      host: '127.0.0.1',
      port: 5173,
      protocol: 'ws',
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
}))
