import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    tailwindcss(),
  ],
  build: {
    outDir: 'assets',
    emptyOutDir: false,
    cssCodeSplit: false,
    rollupOptions: {
      input: {
        app: 'src/js/app.js',
      },
      output: {
        format: 'iife',
        entryFileNames: 'js/[name].js',
        assetFileNames: (asset) => asset.names.some((name) => name.endsWith('.css'))
          ? 'css/app.css'
          : 'css/[name][extname]',
      },
    },
  },
});
