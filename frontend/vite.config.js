import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        configure: (proxy) => {
          proxy.on('proxyReq', (proxyReq, req) => {
            const auth = req.headers.authorization || req.headers.Authorization
            const xAuth = req.headers['x-auth-token'] || req.headers['X-Auth-Token']
            if (auth) proxyReq.setHeader('Authorization', auth)
            if (xAuth) proxyReq.setHeader('X-Auth-Token', xAuth)
          })
        },
      },
    },
  },
})
