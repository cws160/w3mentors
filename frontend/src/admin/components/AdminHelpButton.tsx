import { useEffect, useRef, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';

const MODAL_TRANSITION_MS = 300;

export function AdminHelpButton() {
  const { lbl } = useSite();
  const { meta } = useAdminPageMeta();
  const [open, setOpen] = useState(false);
  const [mounted, setMounted] = useState(false);
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
    document.body.classList.add('modal-open');
    return () => document.body.classList.remove('modal-open');
  }, [mounted]);

  if (!meta.helpingText) {
    return null;
  }

  const pageTitle = meta.title ?? '';

  return (
    <div id="helpCenterJs">
      <button type="button" className="help-btn btn btn-primary" onClick={() => setOpen(true)}>
        <span className="help_label">{lbl('LBL_HELP', 'Help')}</span>
      </button>

      {mounted ? (
        <>
          <div className={`modal-backdrop fade${visible ? ' show' : ''}`} onClick={() => setOpen(false)} />
          <div
            className={`modal fixed-right fade${visible ? ' show' : ''} d-block`}
            id="help"
            tabIndex={-1}
            role="dialog"
            aria-modal="true"
            onClick={() => setOpen(false)}
          >
            <div
              className="modal-dialog modal-dialog-vertical"
              role="document"
              onClick={(e) => e.stopPropagation()}
            >
              <div className="modal-content">
                <div className="modal-header">
                  <h5 className="modal-title">{pageTitle}</h5>
                  <button type="button" className="btn-close" aria-label="Close" onClick={() => setOpen(false)} />
                </div>
                <div className="modal-body">
                  <div
                    className="cms help-data"
                    dangerouslySetInnerHTML={{ __html: meta.helpingText }}
                  />
                </div>
              </div>
            </div>
          </div>
        </>
      ) : null}
    </div>
  );
}
