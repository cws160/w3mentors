import { useCallback } from 'react';
import { LoginModalContent } from '../modals/LoginModalContent';
import { RegisterModalContent } from '../modals/RegisterModalContent';
import { AUTH_MODAL_OPTS } from '../modals/authModalOptions';
import { useModal } from '../context/ModalContext';

export function useAuthModals() {
  const { showModal } = useModal();

  const openLoginModal = useCallback(() => {
    showModal(
      <LoginModalContent
        onRegisterClick={() =>
          showModal(
            <RegisterModalContent onSignInClick={openLoginModal} />,
            AUTH_MODAL_OPTS
          )
        }
      />,
      AUTH_MODAL_OPTS
    );
  }, [showModal]);

  const openSignupModal = useCallback(() => {
    showModal(
      <RegisterModalContent onSignInClick={openLoginModal} />,
      AUTH_MODAL_OPTS
    );
  }, [showModal, openLoginModal]);

  return { openLoginModal, openSignupModal };
}

export { isLegacyLoginHref, isLegacySignupHref } from './authModalLinks';
