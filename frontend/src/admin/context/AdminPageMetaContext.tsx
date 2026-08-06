import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react';

export type AdminPageMeta = {
  title?: string;
  summary?: string;
  warning?: string;
  recommendations?: string;
  helpingText?: string;
  plangId?: number;
};

type AdminPageMetaContextValue = {
  meta: AdminPageMeta;
  setMeta: (meta: AdminPageMeta) => void;
  clearMeta: () => void;
};

const AdminPageMetaContext = createContext<AdminPageMetaContextValue | null>(null);

export function AdminPageMetaProvider({ children }: { children: ReactNode }) {
  const [meta, setMetaState] = useState<AdminPageMeta>({});

  const setMeta = useCallback((next: AdminPageMeta) => {
    setMetaState(next);
  }, []);

  const clearMeta = useCallback(() => {
    setMetaState({});
  }, []);

  const value = useMemo(
    () => ({
      meta,
      setMeta,
      clearMeta,
    }),
    [meta, setMeta, clearMeta]
  );

  return <AdminPageMetaContext.Provider value={value}>{children}</AdminPageMetaContext.Provider>;
}

export function useAdminPageMeta() {
  const ctx = useContext(AdminPageMetaContext);
  if (!ctx) {
    throw new Error('useAdminPageMeta must be used within AdminPageMetaProvider');
  }
  return ctx;
}
