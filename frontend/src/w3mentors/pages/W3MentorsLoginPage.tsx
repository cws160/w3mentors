import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuthModals } from '../hooks/useAuthModals';

/** Deep link /login — opens the same modal as the header Login button. */
export function W3MentorsLoginPage() {
  const { openLoginModal } = useAuthModals();
  const navigate = useNavigate();

  useEffect(() => {
    openLoginModal();
    navigate('/', { replace: true });
  }, [openLoginModal, navigate]);

  return null;
}
