import { defineConfig } from 'vite';
import { resolve } from 'path';
import dts from 'vite-plugin-dts';

export default defineConfig(({ mode }) => {
  if (mode === 'lib') {
    return {
      plugins: [
        dts({
          insertTypesEntry: true,
        }),
      ],
      build: {
        lib: {
          entry: resolve(__dirname, 'src/index.ts'),
          name: 'Brighten',
          formats: ['es', 'cjs', 'umd'],
          fileName: (format) => {
            const formatMap: Record<string, string> = { es: 'esm', cjs: 'cjs', umd: 'umd' };
            return `brighten.${formatMap[format] || format}.js`;
          },
        },
        rollupOptions: {
          external: [],
          output: {
            globals: {},
          },
        },
        sourcemap: true,
      },
      resolve: {
        alias: {
          '@': resolve(__dirname, 'src'),
        },
      },
    };
  }

  const isGitHubPagesBuild = process.env.NODE_ENV === 'production';
  
  return {
    base: isGitHubPagesBuild ? '/brighten/demo/' : '/',
    build: {
      outDir: 'dist-pages',
      sourcemap: true,
      rollupOptions: {
        input: {
          main: resolve(__dirname, 'index.html'),
        },
      },
    },
    resolve: {
      alias: {
        '@': resolve(__dirname, 'src'),
      },
    },
    server: {
      proxy: {
        '/openapi.json': {
          target: 'http://localhost:3001',
          changeOrigin: true,
        },
      },
    },
  };
});
