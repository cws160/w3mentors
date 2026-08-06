import { useEffect, useState } from 'react';
import { homeApi, type Course, type HomeData } from '../../api/client';
import { apiErrorMessage } from '../../utils/apiError';
import { useSite } from '../context/SiteContext';
import { useStatsCounters, useW3MentorsAos, useW3MentorsSliders } from '../hooks/useW3MentorsSliders';
import { HomeBlockRenderer } from '../home/HomeBlockRenderer';

function normalizeHomePayload(raw: HomeData): HomeData {
  const unwrap = <T,>(value: T | { data: T }): T => {
    if (value && typeof value === 'object' && 'data' in value && Array.isArray((value as { data: T }).data)) {
      return (value as { data: T }).data;
    }
    return value as T;
  };

  return {
    ...raw,
    courses: unwrap<Course[]>(raw.courses as Course[] | { data: Course[] }),
    teachers: unwrap(raw.teachers as HomeData['teachers'] | { data: HomeData['teachers'] }),
  };
}

export function W3MentorsHomePage() {
  const { lbl } = useSite();
  const [data, setData] = useState<HomeData | null>(null);
  const [error, setError] = useState('');

  useEffect(() => {
    homeApi
      .get()
      .then((res) => setData(normalizeHomePayload(res.data)))
      .catch((err) =>
        setError(
          apiErrorMessage(
            err,
            lbl('LBL_Something_went_wrong', 'Could not load home page. Is the API running?')
          )
        )
      );
  }, [lbl]);

  useW3MentorsSliders([data]);
  useW3MentorsAos([data]);
  useStatsCounters([data]);

  if (error) {
    return (
      <section className="section">
        <div className="container container--xl text-center">
          <p className="color-red">{error}</p>
        </div>
      </section>
    );
  }

  if (!data) {
    return (
      <section className="section">
        <div className="container container--xl text-center">
          <p>{lbl('LBL_Loading', 'Loading...')}</p>
        </div>
      </section>
    );
  }

  return <HomeBlockRenderer data={data} lbl={lbl} />;
}
