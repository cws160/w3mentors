import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import type { Plugin } from 'vite';
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

const rootDir = path.dirname(fileURLToPath(import.meta.url));
const managerViewsDir = path.join(rootDir, '..', 'manager', 'views');

function serveManagerViewsPlugin(): Plugin {
  return {
    name: 'serve-manager-views',
    configureServer(server) {
      server.middlewares.use('/manager/views', (req, res, next) => {
        const pathname = (req.url ?? '/').split('?')[0];
        const relative = pathname.replace(/^\//, '');
        const filePath = path.join(managerViewsDir, relative);
        if (!filePath.startsWith(managerViewsDir) || !fs.existsSync(filePath) || !fs.statSync(filePath).isFile()) {
          next();
          return;
        }
        const ext = path.extname(filePath).toLowerCase();
        const types: Record<string, string> = {
          '.css': 'text/css',
          '.js': 'application/javascript',
          '.svg': 'image/svg+xml',
          '.png': 'image/png',
          '.jpg': 'image/jpeg',
          '.jpeg': 'image/jpeg',
          '.gif': 'image/gif',
          '.woff': 'font/woff',
          '.woff2': 'font/woff2',
        };
        res.setHeader('Content-Type', types[ext] ?? 'application/octet-stream');
        fs.createReadStream(filePath).pipe(res);
      });
    },
  };
}

/** Serve static files from frontend/public (and repo public/) before proxying to Laravel. */
function bypassToPublic(req: { url?: string }): string | undefined {
  const pathname = req.url?.split('?')[0];
  if (!pathname) {
    return undefined;
  }
  const relative = pathname.replace(/^\//, '');
  const roots = [
    path.join(rootDir, 'public'),
    path.join(rootDir, '..', 'public'),
  ];
  for (const root of roots) {
    const file = path.join(root, relative);
    if (fs.existsSync(file) && fs.statSync(file).isFile()) {
      if (root === path.join(rootDir, 'public')) {
        return pathname;
      }
      // Serve repo-level public assets through /@fs/ (Vite dev)
      return `/@fs/${file.replace(/\\/g, '/')}`;
    }
  }
  return undefined;
}

/** React SPA routes under /guest-user — must not proxy to legacy PHP in dev. */
const GUEST_USER_SPA_PATHS = new Set([
  'forgot-password',
  'affiliate-signup-form',
]);

function guestUserProxyBypass(req: { url?: string }): string | false | undefined {
  const pathname = req.url?.split('?')[0] ?? '';
  const match = pathname.match(/^\/guest-user\/([^/]+)/);
  if (!match) {
    return undefined;
  }
  const segment = match[1];
  if (GUEST_USER_SPA_PATHS.has(segment) || segment === 'reset-password') {
    return '/index.html';
  }
  return undefined;
}

const proxyWithPublicFallback = {
  target: 'http://localhost:8000',
  changeOrigin: true,
  bypass: bypassToPublic,
};

/** React SPA dashboard routes under /dashboard — must not proxy to legacy PHP in dev. */
const REACT_DASHBOARD_PREFIXES = ['/dashboard/teacher', '/dashboard/learner'];

function isReactDashboardPath(pathname: string): boolean {
  return REACT_DASHBOARD_PREFIXES.some(
    (prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`),
  );
}

function dashboardProxyBypass(req: { url?: string }): string | false | undefined {
  const pathname = req.url?.split('?')[0] ?? '';
  if (pathname === '/dashboard' || isReactDashboardPath(pathname)) {
    return '/index.html';
  }
  return undefined;
}

const legacyOrigin = process.env.VITE_LEGACY_ORIGIN || 'http://localhost:8090';

export default defineConfig({
  plugins: [react(), serveManagerViewsPlugin()],
  optimizeDeps: {
    include: ['jquery', 'slick-carousel', 'aos'],
  },
  server: {
    port: 5173,
    fs: {
      allow: ['..'],
    },
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      /* Legacy PHP GuestUser OAuth (facebookLogin, googleLogin, …) */
      '/guest-user': {
        target: process.env.VITE_LEGACY_ORIGIN || 'http://localhost:8090',
        changeOrigin: true,
        bypass: guestUserProxyBypass,
      },
      '/image': proxyWithPublicFallback,
      '/user-uploads': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      '/sitemap.xml': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      '/sitemap/list_': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      '/images': proxyWithPublicFallback,
      /* Dashboard sprites in frontend/public/dashboard/images */
      '/dashboard/images': proxyWithPublicFallback,
      '/admin-dashboard-bridge.php': {
        target: legacyOrigin,
        changeOrigin: true,
      },
      '/dashboard': {
        target: legacyOrigin,
        changeOrigin: true,
        bypass: dashboardProxyBypass,
      },
    },
  },
});
