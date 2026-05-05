import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'node:path';

// 코트맵 프론트 빌드 — src/frontend/main.jsx → public/build/app.js (+ app.css)
// PHP 서버가 렌더한 HTML 에서 단순히 <script src="/build/app.js"> 로 사용.
export default defineConfig({
  plugins: [react()],
  // outDir 가 publicDir 안에 있으므로 Vite 의 public 파일 복사 비활성화
  publicDir: false,
  // lib 모드는 process.env.NODE_ENV 를 자동 대체하지 않음 → 명시적으로 "production" 주입
  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
    'process.env': '{}',
  },
  // dev 서버가 PHP 백엔드 API 를 호출할 수 있도록 프록시
  server: {
    port: 5173,
    proxy: {
      '^/(api|login|register|logout|auth|reservations|me|venues)': {
        target: 'https://bad.mvc.kr',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    outDir: path.resolve(__dirname, 'public/build'),
    emptyOutDir: true,
    sourcemap: true,
    lib: {
      entry: path.resolve(__dirname, 'src/frontend/main.jsx'),
      formats: ['iife'],
      name: 'CourtMap',
      fileName: () => 'app.js',
    },
    rollupOptions: {
      output: {
        assetFileNames: (info) => info.name === 'style.css' ? 'app.css' : '[name][extname]',
        inlineDynamicImports: true,
      },
    },
  },
});
