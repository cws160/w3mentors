import { useEffect, useRef, useState } from 'react';
import {
  ensureAdminInnovEditor,
  initInnovEditorInHost,
  renderInnovEditorFallback,
  resetAdminInnovEditors,
} from '../hooks/adminInnovEditorResources';

type Props = {
  mountKey: string;
  editorId: string;
  initialHtml: string;
  layoutDirection: string;
  active: boolean;
};

export function AdminInnovHtmlEditor({ mountKey, editorId, initialHtml, layoutDirection, active }: Props) {
  const hostRef = useRef<HTMLDivElement | null>(null);
  const initKeyRef = useRef('');
  const htmlRef = useRef(initialHtml);
  const directionRef = useRef(layoutDirection);
  const [status, setStatus] = useState<'idle' | 'loading' | 'ready' | 'fallback'>('idle');

  htmlRef.current = initialHtml;
  directionRef.current = layoutDirection;

  useEffect(() => {
    const host = hostRef.current;
    if (!active || !host) {
      return;
    }

    if (initKeyRef.current === mountKey) {
      return;
    }

    let cancelled = false;
    setStatus('loading');

    const timer = window.setTimeout(() => {
      void ensureAdminInnovEditor()
        .then(() => {
          if (cancelled || !hostRef.current) {
            return;
          }
          try {
            initInnovEditorInHost(hostRef.current, editorId, htmlRef.current, directionRef.current);
            initKeyRef.current = mountKey;
            setStatus('ready');
          } catch {
            renderInnovEditorFallback(hostRef.current, editorId, htmlRef.current);
            initKeyRef.current = mountKey;
            setStatus('fallback');
          }
        })
        .catch(() => {
          if (!hostRef.current || cancelled) {
            return;
          }
          renderInnovEditorFallback(hostRef.current, editorId, htmlRef.current);
          initKeyRef.current = mountKey;
          setStatus('fallback');
        });
    }, 50);

    return () => {
      cancelled = true;
      window.clearTimeout(timer);
      resetAdminInnovEditors(hostRef.current);
      initKeyRef.current = '';
      setStatus('idle');
    };
  }, [active, mountKey, editorId]);

  return (
    <div className="admin-innov-editor-wrap">
      {status === 'loading' ? <div className="admin-innov-editor-loading">Loading editor...</div> : null}
      <div ref={hostRef} className="admin-innov-editor-host" data-editor-status={status} />
    </div>
  );
}
