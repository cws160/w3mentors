import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { applyBrandText, BRAND_NAME } from '../../utils/branding';
import { siteApi, type SiteBootstrap } from '../../api/client';
import { API_OFFLINE_MESSAGE, isApiOfflineError } from '../../utils/apiError';

const LANG_STORAGE_KEY = 'w3mentors_lang_id';
const CURRENCY_STORAGE_KEY = 'w3mentors_currency_id';

type SiteContextValue = {
  site: SiteBootstrap['site'] | null;
  navigation: SiteBootstrap['navigation'] | null;
  legalPages: SiteBootstrap['legal_pages'] | null;
  demoLogin: SiteBootstrap['demo_login'] | null;
  modules: SiteBootstrap['modules'];
  searchFilters: SiteBootstrap['search_filters'];
  labels: Record<string, string>;
  languages: SiteBootstrap['languages'];
  currencies: SiteBootstrap['currencies'];
  social: SiteBootstrap['social'];
  langId: number;
  currencyId: number | null;
  setLangId: (id: number) => void;
  setCurrencyId: (id: number) => void;
  loading: boolean;
  lbl: (key: string, fallback?: string) => string;
};

const SiteContext = createContext<SiteContextValue | null>(null);

const EMPTY_LABELS: Record<string, string> = {};

function readStoredLangId(): number {
  const stored = Number(localStorage.getItem(LANG_STORAGE_KEY));
  return stored > 0 ? stored : 1;
}

function readStoredCurrencyId(): number | null {
  const stored = localStorage.getItem(CURRENCY_STORAGE_KEY);
  if (!stored) {
    return null;
  }
  const id = Number(stored);
  return id > 0 ? id : null;
}

export function SiteProvider({ children }: { children: ReactNode }) {
  const [langId, setLangIdState] = useState(readStoredLangId);
  const [currencyId, setCurrencyIdState] = useState<number | null>(readStoredCurrencyId);
  const [data, setData] = useState<SiteBootstrap | null>(null);
  const [loading, setLoading] = useState(true);
  const [apiOffline, setApiOffline] = useState(false);

  const loadBootstrap = useCallback(() => {
    setLoading(true);
    siteApi
      .bootstrap(langId, currencyId ?? undefined)
      .then((res) => {
        setData(res.data);
        setApiOffline(false);
      })
      .catch((err) => {
        setData(null);
        setApiOffline(isApiOfflineError(err));
      })
      .finally(() => setLoading(false));
  }, [langId, currencyId]);

  useEffect(() => {
    loadBootstrap();
  }, [loadBootstrap]);

  useEffect(() => {
    if (data?.site?.theme) {
      document.documentElement.setAttribute('theme', data.site.theme);
    }
  }, [data?.site?.theme]);

  useEffect(() => {
    const siteName = data?.site?.name ? applyBrandText(data.site.name) : BRAND_NAME;
    document.title = siteName || BRAND_NAME;
  }, [data?.site?.name]);

  const setLangId = useCallback((id: number) => {
    localStorage.setItem(LANG_STORAGE_KEY, String(id));
    setLangIdState(id);
  }, []);

  const setCurrencyId = useCallback((id: number) => {
    localStorage.setItem(CURRENCY_STORAGE_KEY, String(id));
    setCurrencyIdState(id);
  }, []);

  const lbl = useMemo(
    () => (key: string, fallback = key) => applyBrandText(data?.labels?.[key] ?? fallback),
    [data],
  );

  const site = useMemo(() => {
    if (!data?.site) {
      return null;
    }
    return {
      ...data.site,
      name: applyBrandText(data.site.name) || BRAND_NAME,
    };
  }, [data?.site]);

  const value = useMemo(
    () => ({
      site,
      navigation: data?.navigation ?? null,
      legalPages: data?.legal_pages ?? null,
      demoLogin: data?.demo_login ?? null,
      modules: data?.modules ?? { courses: true, group_classes: true },
      searchFilters: data?.search_filters ?? { 0: 'LBL_ALL', 3: 'LBL_TEACHERS' },
      labels: data?.labels ?? EMPTY_LABELS,
      languages: data?.languages ?? [],
      currencies: data?.currencies ?? [],
      social: data?.social ?? {},
      langId,
      currencyId,
      setLangId,
      setCurrencyId,
      loading,
      lbl,
    }),
    [data, site, langId, currencyId, setLangId, setCurrencyId, loading, lbl]
  );

  return (
    <SiteContext.Provider value={value}>
      {apiOffline && (
        <div className="alert alert--danger" role="alert">
          {API_OFFLINE_MESSAGE}
        </div>
      )}
      {children}
    </SiteContext.Provider>
  );
}

export function useSite() {
  const ctx = useContext(SiteContext);
  if (!ctx) throw new Error('useSite must be used within SiteProvider');
  return ctx;
}
