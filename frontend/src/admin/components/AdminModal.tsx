import { useEffect, useRef, useState, type ReactNode } from 'react';
import { createPortal } from 'react-dom';

type Props = {
  open: boolean;
  title?: string;
  size?: 'sm' | 'md' | 'lg';
  hideHeader?: boolean;
  onClose: () => void;
  children: ReactNode;
};

const sizeClass: Record<NonNullable<Props['size']>, string> = {
  sm: 'modal-dialog-vertical-sm',
  md: 'modal-dialog-vertical-md',
  lg: 'modal-dialog-vertical-lg',
};

/** Matches manager.css: transform transition 0.3s ease-out on .modal.fade .modal-dialog */
const MODAL_TRANSITION_MS = 300;

export function AdminModal({ open, title, size = 'md', hideHeader = false, onClose, children }: Props) {
  const [mounted, setMounted] = useState(open);
  const [visible, setVisible] = useState(false);
  const closeTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    if (open) {
      if (closeTimerRef.current) {
        clearTimeout(closeTimerRef.current);
        closeTimerRef.current = null;
      }
      setMounted(true);
      const frame = requestAnimationFrame(() => {
        requestAnimationFrame(() => setVisible(true));
      });
      return () => cancelAnimationFrame(frame);
    }

    setVisible(false);
    closeTimerRef.current = setTimeout(() => {
      setMounted(false);
      closeTimerRef.current = null;
    }, MODAL_TRANSITION_MS);

    return () => {
      if (closeTimerRef.current) {
        clearTimeout(closeTimerRef.current);
        closeTimerRef.current = null;
      }
    };
  }, [open]);

  useEffect(() => {
    if (!mounted) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    };
    document.body.classList.add('modal-open');
    window.addEventListener('keydown', onKey);
    return () => {
      document.body.classList.remove('modal-open');
      window.removeEventListener('keydown', onKey);
    };
  }, [mounted, onClose]);

  if (!mounted) return null;

  const modal = (
    <>
      <div className={`modal-backdrop fade${visible ? ' show' : ''}`} onClick={onClose} />
      <div
        className={`modal fixed-right fade${visible ? ' show' : ''} d-block`}
        tabIndex={-1}
        role="dialog"
        aria-modal="true"
        onClick={onClose}
      >
        <div
          className={`modal-dialog modal-dialog-vertical ${sizeClass[size]}`}
          role="document"
          onClick={(e) => e.stopPropagation()}
        >
          <div className="modal-content contentBodyJs">
            {!hideHeader ? (
            <div className="modal-header">
              {title ? <h5 className="modal-title">{title}</h5> : null}
              <button
                type="button"
                className="btn-close w3mentorsmodalJs"
                aria-label="Close"
                onClick={onClose}
              />
            </div>
            ) : null}
            <div className="modal-body p-0">{children}</div>
          </div>
        </div>
      </div>
    </>
  );

  return createPortal(modal, document.body);
}
