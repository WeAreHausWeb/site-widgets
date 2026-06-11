import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],

  build: {
    outDir: 'dist',
    emptyOutDir: true,
    sourcemap: true,
    manifest: false,
    cssMinify: true,

    rollupOptions: {
      input: {
        script: 'assets/js/script.js',
        style: 'assets/scss/style.scss',
        admin: 'assets/scss/admin.scss',
      },

      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: '[name][extname]',
      },
    },
  },
})
