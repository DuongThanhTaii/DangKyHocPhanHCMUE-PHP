import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],

  // Optimize dependency pre-bundling to reduce dev server requests
  optimizeDeps: {
    include: [
      'react',
      'react-dom',
      'react-router-dom',
      '@reduxjs/toolkit',
      'react-redux',
      'axios',
    ],
    // Force include these for faster cold starts
    force: false,
  },

  // Build optimization for production
  build: {
    // Chunk splitting strategy - ✅ OPTIMIZED
    rollupOptions: {
      output: {
        manualChunks: {
          // Core React ecosystem
          'vendor-react': ['react', 'react-dom', 'react-router-dom'],
          // State management
          'vendor-redux': ['@reduxjs/toolkit', 'react-redux', 'redux-persist'],
          // HTTP utilities
          'vendor-utils': ['axios', 'fuse.js'],
          // Charts - large library, separate chunk
          'vendor-charts': ['recharts'],
          // Drag and drop - only load when needed
          'vendor-dnd': ['@dnd-kit/core', '@dnd-kit/sortable', '@dnd-kit/utilities'],
        },
      },
    },
    // Increase chunk size warning limit (default 500kb)
    chunkSizeWarningLimit: 1000,
    // Enable source maps for debugging (disable in production for smaller builds)
    sourcemap: false,
    // ✅ Enable CSS code splitting - each page loads only needed CSS
    cssCodeSplit: true,
    // ✅ Minify options for better compression
    minify: 'esbuild',
    // ✅ Target modern browsers for smaller bundle
    target: 'es2020',
  },

  server: {
    host: true, // Listen on all addresses (0.0.0.0)
    port: 5173,
    watch: {
      usePolling: true, // Needed for Docker file watching
    },
    proxy: {
      '/api': {
        target: 'http://backend:8000',
        changeOrigin: true,
        secure: false,
      },
    },
    // Enable HMR with faster refresh
    hmr: {
      overlay: true,
    },
  },
})
