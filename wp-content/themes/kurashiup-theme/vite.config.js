import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    tailwindcss(),
  ],
  build: {
    outDir: 'assets',
    emptyOutDir: false,
    rollupOptions: {
      input: {
        app: 'src/js/app.js',
      },
      output: {
        entryFileNames: 'js/[name].js',
        assetFileNames: 'css/[name][extname]',
      },
    },
  },
});
