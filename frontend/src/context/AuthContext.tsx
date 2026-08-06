import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from 'react';
import { authApi, type User } from '../api/client';
import { normalizeUser, readStoredUser } from '../utils/authUser';

type AuthContextValue = {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (payload: {
    first_name: string;
    last_name?: string;
    email: string;
    password: string;
  }) => Promise<void>;
  logout: () => Promise<void>;
  reloadProfile: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

function persistUser(user: User | null, token?: string) {
  if (token) {
    localStorage.setItem('auth_token', token);
  }
  if (user) {
    localStorage.setItem('auth_user', JSON.stringify(user));
  } else {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
  }
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(() => readStoredUser());
  const [loading, setLoading] = useState(() => !!localStorage.getItem('auth_token'));
  const meGeneration = useRef(0);

  const applyUser = useCallback((next: User | null, token?: string) => {
    persistUser(next, token);
    setUser(next);
  }, []);

  useEffect(() => {
    const onCleared = () => setUser(null);
    window.addEventListener('auth:cleared', onCleared);
    return () => window.removeEventListener('auth:cleared', onCleared);
  }, []);

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
      setLoading(false);
      return;
    }

    const generation = ++meGeneration.current;
    authApi
      .me()
      .then((res) => {
        if (generation !== meGeneration.current) {
          return;
        }
        const normalized = normalizeUser(res.data.user);
        if (normalized) {
          applyUser(normalized);
        } else {
          applyUser(null);
        }
      })
      .catch(() => {
        if (generation !== meGeneration.current) {
          return;
        }
        applyUser(null);
      })
      .finally(() => {
        if (generation === meGeneration.current) {
          setLoading(false);
        }
      });
  }, [applyUser]);

  const login = useCallback(
    async (email: string, password: string) => {
      meGeneration.current += 1;
      const { data } = await authApi.login(email, password);
      const normalized = normalizeUser(data.user);
      if (!normalized) {
        throw new Error('Invalid user payload from login');
      }
      applyUser(normalized, data.token);
      setLoading(false);
    },
    [applyUser]
  );

  const register = useCallback(
    async (payload: {
      first_name: string;
      last_name?: string;
      email: string;
      password: string;
    }) => {
      meGeneration.current += 1;
      const { data } = await authApi.register(payload);
      const normalized = normalizeUser(data.user);
      if (!normalized) {
        throw new Error('Invalid user payload from register');
      }
      applyUser(normalized, data.token);
      setLoading(false);
    },
    [applyUser]
  );

  const reloadProfile = useCallback(async () => {
    const { data } = await authApi.me();
    const normalized = normalizeUser(data.user);
    if (normalized) {
      applyUser(normalized);
    }
  }, [applyUser]);

  const logout = useCallback(async () => {
    meGeneration.current += 1;
    try {
      await authApi.logout();
    } catch {
      // ignore
    }
    applyUser(null);
    setLoading(false);
  }, [applyUser]);

  const value = useMemo(
    () => ({ user, loading, login, register, logout, reloadProfile }),
    [user, loading, login, register, logout, reloadProfile]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
