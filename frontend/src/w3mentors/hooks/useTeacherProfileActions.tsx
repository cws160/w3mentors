import { useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { chatsApi, type TeacherProfile } from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { useModal } from '../context/ModalContext';
import { ContactTeacherModalContent } from '../modals/ContactTeacherModalContent';
import { TeacherBookModalContent } from '../modals/TeacherBookModalContent';
import { useAuthModals } from './useAuthModals';
import { $ } from '../lib/setup-jquery';

const BOOK_MODAL_OPTS = { size: 'modal-lg', addClass: 'checkout--modal' } as const;
const CONTACT_MODAL_OPTS = { size: 'modal-md' } as const;

export function useTeacherProfileActions(teacher: TeacherProfile | null, slugOrId: string) {
  const { user } = useAuth();
  const { showModal } = useModal();
  const { openLoginModal } = useAuthModals();
  const navigate = useNavigate();

  const isSelf = Boolean(user && teacher && user.id === teacher.id);
  const requiresAuth = !user;

  const scrollToAvailability = useCallback(() => {
    if (typeof window === 'undefined' || !teacher) return;
    if (window.innerWidth < 767) {
      const $panel = $('#teacherAvailability');
      const $trigger = $panel.children('.panel__head-trigger-js');
      if (!$trigger.hasClass('is-active')) {
        $trigger.trigger('click');
      }
    }
    const el = document.getElementById('teacherAvailability');
    if (el) {
      const top = el.getBoundingClientRect().top + window.scrollY - 200;
      window.scrollTo({ top, behavior: 'smooth' });
    }
  }, [teacher]);

  const openBookModal = useCallback(
    (initialTlangId?: number, initialDuration?: number) => {
      if (!teacher) return;
      if (requiresAuth) {
        openLoginModal();
        return;
      }
      if (isSelf) return;
      showModal(
        <TeacherBookModalContent
          slugOrId={slugOrId}
          initialTlangId={initialTlangId}
          initialDuration={initialDuration}
        />,
        BOOK_MODAL_OPTS
      );
    },
    [teacher, slugOrId, requiresAuth, isSelf, showModal, openLoginModal]
  );

  const openContact = useCallback(async () => {
    if (!teacher) return;
    if (requiresAuth) {
      openLoginModal();
      return;
    }
    if (isSelf) return;

    try {
      const res = await chatsApi.privateThread(teacher.id);
      const data = res.data.data;
      if (data.thread_id) {
        navigate(`/dashboard?thread_id=${data.thread_id}`);
        return;
      }
      if (data.needs_message) {
        showModal(
          <ContactTeacherModalContent
            teacherId={teacher.id}
            teacherName={teacher.full_name}
            onSent={(threadId) => navigate(`/dashboard?thread_id=${threadId}`)}
          />,
          CONTACT_MODAL_OPTS
        );
      }
    } catch {
      showModal(
        <ContactTeacherModalContent
          teacherId={teacher.id}
          teacherName={teacher.full_name}
          onSent={(threadId) => navigate(`/dashboard?thread_id=${threadId}`)}
        />,
        CONTACT_MODAL_OPTS
      );
    }
  }, [teacher, requiresAuth, isSelf, showModal, openLoginModal, navigate]);

  return {
    isSelf,
    openBookModal,
    scrollToAvailability,
    openContact,
  };
}
