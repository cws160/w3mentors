import { Outlet } from 'react-router-dom';
import { useEffect } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { ensureAdminSprites } from '../utils/adminSprite';
import { AdminHelpButton } from '../components/AdminHelpButton';
import { useAdminFormValidation } from '../hooks/useAdminFormValidation';
import { AdminHeader } from './AdminHeader';
import { AdminSidebar } from './AdminSidebar';
import '../admin-overrides.css';

export function AdminLayout() {
  const { lbl, site } = useSite();
  useAdminFormValidation();

  useEffect(() => {
    void ensureAdminSprites();
  }, []);

  return (
    <div className="app">
      <AdminSidebar />
      <div className="wrap">
        <AdminHeader />
        <Outlet />
        <AdminHelpButton />
        <footer id="footer" className="footer">
          <div className="container">
            <div className="row justify-content-between">
              <div className="col-md-auto">
                <div className="copyright">
                  &copy; {new Date().getFullYear()}{' '}
                  <span className="bold-600">{site?.name ?? 'w3mentors'}</span>
                </div>
              </div>
              <div className="col-auto">
                <div className="copyright">{lbl('LBL_DEVELOPED_BY_TEXT', 'Developed by FATbit Technologies')}</div>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>
  );
}
