import { defineConfig } from "vite";
export default defineConfig({
  build: {
    rollupOptions: {
      output: {
        entryFileNames: "assets/js/framework.js",
        assetFileNames: "assets/css/framework.css",
      },
    },
  },
});