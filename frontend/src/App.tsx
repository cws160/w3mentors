import { BrowserRouter } from 'react-router-dom';
import { AdminAuthProvider } from './admin/context/AdminAuthContext';
import { AuthProvider } from './context/AuthContext';
import { AppRoutes } from './routes/AppRoutes';
import { RouteStyleSync } from './w3mentors/components/RouteStyleSync';
import { ModalProvider } from './w3mentors/context/ModalContext';
import { SiteProvider } from './w3mentors/context/SiteContext';

export default function App() {
  return (
    <AuthProvider>
      <AdminAuthProvider>
        <BrowserRouter>
          <RouteStyleSync />
          <SiteProvider>
            <ModalProvider>
              <AppRoutes />
            </ModalProvider>
          </SiteProvider>
        </BrowserRouter>
      </AdminAuthProvider>
    </AuthProvider>
  );
}
