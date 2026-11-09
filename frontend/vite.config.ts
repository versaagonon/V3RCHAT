import { copyFileSync, existsSync } from 'node:fs'
import { resolve } from 'node:path'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

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
      }
    },
  }
}

export default defineConfig(({ mode }) => ({
  plugins: [vue(), tailwindcss(), syncBuildToLaravel()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    cors: true,
    origin: 'http://127.0.0.1:5173',
    hmr: {
      host: '127.0.0.1',
      port: 5173,
    },
  },
  define: {
    ...(mode === 'production' ? { 'process.env.NODE_ENV': JSON.stringify('production') } : {}),
    'process.env': '{}',
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
        assetFileNames: 'chatify.css',
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
