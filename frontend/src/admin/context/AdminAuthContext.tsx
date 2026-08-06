import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { adminApi, type AdminBootstrap, type AdminNavItem, type AdminUser } from '../api/adminClient';

type AdminAuthContextValue = {
  admin: AdminUser | null;
  navigation: AdminNavItem[];
  privileges: Record<string, boolean>;
  features: AdminBootstrap['features'] | null;
  reportGeneratedAt: string | null;
  loading: boolean;
  login: (username: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
  setReportGeneratedAt: (value: string | null) => void;
};

const AdminAuthContext = createContext<AdminAuthContextValue | null>(null);

export function AdminAuthProvider({ children }: { children: ReactNode }) {
  const [admin, setAdmin] = useState<AdminUser | null>(() => {
    const raw = localStorage.getItem('admin_user');
    return raw ? (JSON.parse(raw) as AdminUser) : null;
  });
  const [navigation, setNavigation] = useState<AdminNavItem[]>([]);
  const [privileges, setPrivileges] = useState<Record<string, boolean>>({});
  const [features, setFeatures] = useState<AdminBootstrap['features'] | null>(null);
  const [reportGeneratedAt, setReportGeneratedAt] = useState<string | null>(null);
  const [loading, setLoading] = useState(() => Boolean(localStorage.getItem('admin_token')));

  const applyBootstrap = useCallback((data: AdminBootstrap) => {
    setAdmin(data.admin);
    setNavigation(data.navigation);
    setPrivileges(data.privileges);
    setFeatures(data.features);
    setReportGeneratedAt(data.report_generated_at ?? null);
    localStorage.setItem('admin_user', JSON.stringify(data.admin));
  }, []);

  const refresh = useCallback(async () => {
    const token = localStorage.getItem('admin_token');
    if (!token) {
      setAdmin(null);
      setNavigation([]);
      setPrivileges({});
      setFeatures(null);
      setLoading(false);
      return;
    }
    if (!localStorage.getItem('admin_user')) {
      setLoading(true);
    }
    try {
      const res = await adminApi.bootstrap();
      applyBootstrap(res.data);
    } catch {
      localStorage.removeItem('admin_token');
      localStorage.removeItem('admin_user');
      setAdmin(null);
      setNavigation([]);
      setPrivileges({});
      setFeatures(null);
    } finally {
      setLoading(false);
    }
  }, [applyBootstrap]);

  useEffect(() => {
    void refresh();
    const onCleared = () => {
      setAdmin(null);
      setNavigation([]);
      setPrivileges({});
      setFeatures(null);
    };
    window.addEventListener('admin:cleared', onCleared);
    return () => window.removeEventListener('admin:cleared', onCleared);
  }, [refresh]);

  const login = useCallback(
    async (username: string, password: string) => {
      const res = await adminApi.login(username, password);
      localStorage.setItem('admin_token', res.data.token);
      localStorage.setItem('admin_user', JSON.stringify(res.data.admin));
      setAdmin(res.data.admin);
      await refresh();
    },
    [refresh]
  );

  const logout = useCallback(async () => {
    try {
      await adminApi.logout();
    } catch {
      // ignore
    }
    localStorage.removeItem('admin_token');
    localStorage.removeItem('admin_user');
    setAdmin(null);
    setNavigation([]);
    setPrivileges({});
    setFeatures(null);
  }, []);

  const value = useMemo(
    () => ({
      admin,
      navigation,
      privileges,
      features,
      reportGeneratedAt,
      loading,
      login,
      logout,
      refresh,
      setReportGeneratedAt,
    }),
    [admin, navigation, privileges, features, reportGeneratedAt, loading, login, logout, refresh]
  );

  return <AdminAuthContext.Provider value={value}>{children}</AdminAuthContext.Provider>;
}

export function useAdminAuth() {
  const ctx = useContext(AdminAuthContext);
  if (!ctx) {
    throw new Error('useAdminAuth must be used within AdminAuthProvider');
  }
  return ctx;
}
