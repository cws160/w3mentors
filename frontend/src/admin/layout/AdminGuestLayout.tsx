import { useLayoutEffect } from 'react';
import { Outlet } from 'react-router-dom';

/** Matches AdminGuestController bodyClass: page--front */
export function AdminGuestLayout() {
  useLayoutEffect(() => {
    document.body.classList.add('page--front');
    return () => {
      document.body.classList.remove('page--front');
    };
  }, []);

  return <Outlet />;
}
