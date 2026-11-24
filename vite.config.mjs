import { resolve } from 'node:path';

import { defineConfig } from 'vite';

const root = resolve(import.meta.dirname, 'src/web/assets/cp');

export default defineConfig(({ mode }) => {
    // Plugin Kit's components are ES modules, loaded on demand from their own bundle beside the classic script.
    if (mode === 'kit') {
        return {
            root,
            // Craft publishes the bundle under cpresources, so chunks load relative to the entry.
            base: '',
            build: {
                outDir: resolve(root, 'dist/kit'),
                emptyOutDir: true,
                assetsDir: '',
                cssMinify: 'esbuild',
                minify: 'oxc',
                sourcemap: false,
                target: 'es2020',
                rolldownOptions: {
                    input: resolve(root, 'src/plugin-kit.js'),
                    output: {
                        format: 'es',
                        entryFileNames: '[name].js',
                        chunkFileNames: '[name]-[hash].js',
                        // The entry's stylesheet keeps a fixed name, so the asset bundle can register it in <head>.
                        assetFileNames: '[name][extname]',
                    },
                },
            },
        };
    }

    const isCssBuild = mode === 'css';

    if (!isCssBuild && mode !== 'js') {
        throw new Error(`Unknown asset build mode: ${mode}`);
    }

    const output = isCssBuild ? {
        assetFileNames: '[name][extname]',
    } : {
        codeSplitting: false,
        entryFileNames: '[name].js',
        format: 'iife',
    };

    return {
        root,
        input: resolve(root, `src/gift-voucher.${isCssBuild ? 'css' : 'js'}`),
        build: {
            outDir: resolve(root, 'dist'),
            emptyOutDir: isCssBuild,
            assetsDir: '',
            cssMinify: 'esbuild',
            cssTarget: ['chrome61', 'safari10'],
            minify: 'oxc',
            sourcemap: !isCssBuild,
            target: 'es2015',
            rolldownOptions: {
                output,
            },
        },
    };
});
