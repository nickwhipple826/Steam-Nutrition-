import {defineConfig} from 'vite';
import {resolve} from 'path';
import autoprefixer from 'autoprefixer';

export default defineConfig({
  base: './',

  build: {
    outDir: 'assets/dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'assets/src/js/main.ts'),
      },
      output: {
        entryFileNames: '[name].min.js',
        chunkFileNames: '[name].min.js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return '[name].min.css';
          }
          if (/\.(png|jpe?g|gif|svg|webp)$/i.test(assetInfo.name)) {
            return 'images/[name][extname]'
          }
          return 'assets/[name][extname]';
        }
      }
    },
    minify: "terser",
    terserOptions: {
      mangle: false,
    },
  },

  css: {
    preprocessorOptions: {
      scss: {
        loadPaths: [
          resolve(__dirname, 'node_modules'),
        ]
      }
    },
    postcss: {
      plugins: [autoprefixer()],
    }
  },

  resolve: {
    alias: {
      '@': resolve(__dirname, 'assets/src'),
      '@css': resolve(__dirname, 'assets/src/scss'),
      '@js': resolve(__dirname, 'assets/src/js'),
      'node_modules': resolve(__dirname, 'node_modules'),
    }
  },
});
