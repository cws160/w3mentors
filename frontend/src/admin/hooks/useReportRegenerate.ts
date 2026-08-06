import { type MouseEvent, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';

export function useReportRegenerate(onReload: () => void) {
  const { lbl } = useSite();
  const { setReportGeneratedAt } = useAdminAuth();
  const [regenerating, setRegenerating] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const onRegenerate = (e: MouseEvent) => {
    e.preventDefault();
    if (regenerating) {
      return;
    }

    setRegenerating(true);
    setSuccessMessage('');

    void adminApi
      .regenerateSalesReport()
      .then((res) => {
        const generatedAt = res.data.report_generated_at ?? null;
        if (generatedAt) {
          setReportGeneratedAt(generatedAt);
        }
        onReload();
        setSuccessMessage(
          res.data.msg ||
            res.data.message ||
            lbl('LBL_REPORT_REGENERATED_SUCCESSFULLY', 'Report regenerated successfully'),
        );
      })
      .catch(() => {
        window.alert(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
      })
      .finally(() => setRegenerating(false));
  };

  return { regenerating, successMessage, onRegenerate };
}
