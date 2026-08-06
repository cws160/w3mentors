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
import { createPortal } from 'react-dom';

type ModalOptions = {
  /** Legacy: applied to #w3mentorsModal root (e.g. modal-form) */
  size?: string;
  addClass?: string;
  /** Legacy w3mentors-modal.js default is static */
  backdrop?: boolean | 'static';
};

type ModalState = {
  open: boolean;
  content: ReactNode;
  options: ModalOptions;
};

type ModalContextValue = {
  showModal: (content: ReactNode, options?: ModalOptions) => void;
  closeModal: () => void;
};

const ModalContext = createContext<ModalContextValue | null>(null);

const MODAL_ID = 'w3mentorsModal';

export function ModalProvider({ children }: { children: ReactNode }) {
  const [state, setState] = useState<ModalState>({
    open: false,
    content: null,
    options: {},
  });

  const showModal = useCallback((content: ReactNode, options: ModalOptions = {}) => {
    setState({ open: true, content, options });
  }, []);

  const closeModal = useCallback(() => {
    setState((s) => ({ ...s, open: false, content: null }));
  }, []);

  const value = useMemo(() => ({ showModal, closeModal }), [showModal, closeModal]);

  return (
    <ModalContext.Provider value={value}>
      {children}
      <W3MentorsModal {...state} onClose={closeModal} />
    </ModalContext.Provider>
  );
}

export function useModal() {
  const ctx = useContext(ModalContext);
  if (!ctx) throw new Error('useModal must be used within ModalProvider');
  return ctx;
}

function W3MentorsModal({
  open,
  content,
  options,
  onClose,
}: ModalState & { onClose: () => void }) {
  const modalRef = useRef<HTMLDivElement>(null);
  const bsInstanceRef = useRef<{ hide: () => void; dispose: () => void } | null>(null);
  const suppressHiddenRef = useRef(false);

  /** Legacy w3mentors-modal.js puts `size` (modal-form, modal-xl, …) on #w3mentorsModal, not on .modal-dialog */
  const outerClass = ['modal', 'fade', options.size, options.addClass].filter(Boolean).join(' ');

  useEffect(() => {
    if (!open) {
      document.documentElement.classList.remove('modal-open');
      document.body.classList.remove('modal-open');
      return;
    }

    const el = modalRef.current;
    if (!el) return;

    type BsModal = {
      show: () => void;
      hide: () => void;
      dispose: () => void;
    };
    type BsModalCtor = {
      getOrCreateInstance: (el: Element, opts?: object) => BsModal;
    };
    const ModalCtor = (window as Window & { bootstrap?: { Modal: BsModalCtor } }).bootstrap?.Modal;

    const onHidden = () => {
      if (suppressHiddenRef.current) return;
      onClose();
    };

    if (ModalCtor) {
      const backdrop = options.backdrop ?? true;
      const instance = ModalCtor.getOrCreateInstance(el, {
        backdrop,
        keyboard: backdrop !== 'static',
      });
      bsInstanceRef.current = instance;
      instance.show();
      el.addEventListener('hidden.bs.modal', onHidden);

      return () => {
        suppressHiddenRef.current = true;
        el.removeEventListener('hidden.bs.modal', onHidden);
        instance.hide();
        instance.dispose();
        bsInstanceRef.current = null;
        suppressHiddenRef.current = false;
        document.querySelectorAll('.modal-backdrop').forEach((node) => node.remove());
        document.documentElement.classList.remove('modal-open');
        document.body.classList.remove('modal-open');
      };
    }

    el.classList.add('show');
    el.style.display = 'block';
    document.documentElement.classList.add('modal-open');
    document.body.classList.add('modal-open');

    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    const closeOnBackdrop = () => {
      if (options.backdrop !== 'static') {
        onClose();
      }
    };
    backdrop.addEventListener('click', closeOnBackdrop);
    document.body.appendChild(backdrop);

    const onModalShellClick = (e: MouseEvent) => {
      if (options.backdrop === 'static') return;
      if (e.target === el) {
        onClose();
      }
    };
    el.addEventListener('click', onModalShellClick);

    return () => {
      suppressHiddenRef.current = true;
      el.classList.remove('show');
      el.style.display = '';
      backdrop.removeEventListener('click', closeOnBackdrop);
      el.removeEventListener('click', onModalShellClick);
      backdrop.remove();
      suppressHiddenRef.current = false;
      document.documentElement.classList.remove('modal-open');
      document.body.classList.remove('modal-open');
    };
  }, [open, onClose, options.backdrop]);

  useEffect(() => {
    if (!open) return;

    const onW3mentorsClose = (e: MouseEvent) => {
      const target = e.target as HTMLElement | null;
      if (target?.closest('.w3mentorsmodalJs')) {
        e.preventDefault();
        if (bsInstanceRef.current) {
          bsInstanceRef.current.hide();
        } else {
          onClose();
        }
      }
    };

    document.addEventListener('click', onW3mentorsClose);
    return () => document.removeEventListener('click', onW3mentorsClose);
  }, [open, onClose, options.backdrop]);

  if (!open || typeof document === 'undefined') return null;

  const closeFromOverlay = () => {
    if (options.backdrop === 'static') return;
    if (bsInstanceRef.current) {
      bsInstanceRef.current.hide();
    } else {
      onClose();
    }
  };

  return createPortal(
    <div
      ref={modalRef}
      id={MODAL_ID}
      className={outerClass}
      tabIndex={-1}
      role="dialog"
      data-bs-backdrop={options.backdrop === 'static' ? 'static' : 'true'}
      aria-modal="true"
      onClick={closeFromOverlay}
    >
      <div
        className="modal-dialog modal-dialog-centered modal-dialog-vertical"
        role="document"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="modal-content contentBodyJs">{content}</div>
      </div>
    </div>,
    document.body
  );
}
