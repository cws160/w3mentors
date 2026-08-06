import { useEffect, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import { adminApi } from '../api/adminClient';

export function AdminEmailTemplatePreviewPage() {
  const { code = '', langId = '1' } = useParams();
  const [error, setError] = useState('');
  const wrotePreviewRef = useRef(false);

  useEffect(() => {
    if (!code) {
      setError('Invalid request');
      return;
    }
    void adminApi
      .previewEmailTemplate(code, Number(langId) || 1)
      .then((res) => {
        if (wrotePreviewRef.current) return;
        wrotePreviewRef.current = true;
        document.open();
        document.write(String(res.data ?? ''));
        document.close();
      })
      .catch((err: unknown) => {
        setError(
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            'Email content not available for this language',
        );
      });
  }, [code, langId]);

  if (error) {
    return <div className="p-4">{error}</div>;
  }

  return (
    <div className="table-processing loaderJs">
      <div className="spinner spinner--sm spinner--brand" />
    </div>
  );
}
