import { useEffect, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import { adminApi } from '../api/adminClient';

export function AdminCertificatePreviewPage() {
  const { code = '', langId = '1' } = useParams();
  const [error, setError] = useState('');
  const wrotePreviewRef = useRef(false);

  useEffect(() => {
    if (!code) {
      setError('Invalid request');
      return;
    }
    void adminApi
      .previewCertificate(code, Number(langId) || 1)
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
            'Certificate template not found',
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
