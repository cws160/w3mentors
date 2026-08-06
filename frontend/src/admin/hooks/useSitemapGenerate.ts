import { useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';

export function useSitemapGenerate() {
  const { lbl } = useSite();
  const [generating, setGenerating] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const onGenerateSitemap = () => {
    if (generating) {
      return;
    }

    setGenerating(true);
    setSuccessMessage('');

    void adminApi
      .generateSitemap()
      .then((res) => {
        setSuccessMessage(
          res.data.msg ||
            res.data.message ||
            lbl('MSG_SITEMAP_HAS_BEEN_UPDATED_SUCCESSFULLY', 'Sitemap has been updated successfully'),
        );
      })
      .catch(() => {
        window.alert(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
      })
      .finally(() => setGenerating(false));
  };

  return { generating, successMessage, onGenerateSitemap, setSuccessMessage };
}
