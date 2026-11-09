import { resolve } from 'node:path'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  server: {
    origin: 'http://localhost:5173',
    cors: true,
  },
  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
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
})
