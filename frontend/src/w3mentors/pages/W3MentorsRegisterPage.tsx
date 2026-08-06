import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuthModals } from '../hooks/useAuthModals';

/** Deep link /register — opens the same modal as the header Sign up button. */
export function W3MentorsRegisterPage() {
  const { openSignupModal } = useAuthModals();
  const navigate = useNavigate();

  useEffect(() => {
    openSignupModal();
    navigate('/', { replace: true });
  }, [openSignupModal, navigate]);

  return null;
}
