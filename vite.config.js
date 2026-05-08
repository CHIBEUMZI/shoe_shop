import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

const appUrl = process.env.APP_URL || 'http://localhost:8080'
const appOrigin = (() => {
  try {
    return new URL(appUrl).origin
  } catch {
    return 'http://localhost:8080'
  }
})()
const isNgrok = appUrl.includes('ngrok-free.dev') || appUrl.includes('ngrok.app')
const appHost = (() => {
  try {
    return new URL(appOrigin).hostname
  } catch {
    return 'localhost'
  }
})()

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    vue(),
    tailwindcss(),
  ],
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    cors: true,
    hmr: isNgrok
      ? {
          host: appHost,
          protocol: 'wss',
        }
      : {
          host: 'localhost',
          protocol: 'ws',
        },
  },
  define: {
    'process.env.APP_URL': JSON.stringify(appUrl),
  },
})
