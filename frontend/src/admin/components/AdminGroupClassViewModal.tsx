import { useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type ClassDetails = {
  id: number;
  title: string;
  description: string;
  teacher_label: string;
  language_label: string;
  service_type_label: string;
  class_address: string;
};

type Props = {
  classId: number | null;
  onClose: () => void;
};

function DetailRow({ label, value }: { label: string; value: string }) {
  return (
    <tr>
      <th width="40%">{label}</th>
      <td style={{ whiteSpace: 'pre-wrap' }}>{value || '—'}</td>
    </tr>
  );
}

export function AdminGroupClassViewModal({ classId, onClose }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [data, setData] = useState<ClassDetails | null>(null);

  const load = useCallback(async (id: number) => {
    setLoading(true);
    setError('');
    try {
      const res = await adminApi.groupClassDetails(id);
      setData(res.data.data);
    } catch (e: unknown) {
      setData(null);
      setError(
        (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Failed to load class details',
      );
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!classId) {
      setData(null);
      setError('');
      return;
    }
    void load(classId);
  }, [classId, load]);

  return (
    <AdminModal
      open={classId !== null}
      title={lbl('LBL_VIEW_CLASS_DETAILS', 'View Class Details')}
      size="md"
      onClose={onClose}
    >
      {loading ? (
        <div className="table-processing loaderJs p-5">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : null}
      {error ? <div className="alert alert-danger m-3">{error}</div> : null}
      {data && !loading ? (
        <div className="form-edit-body p-0">
          <table className="table table-coloum">
            <tbody>
              <DetailRow label={lbl('LBL_CLASS_NAME', 'Class name')} value={data.title} />
              <DetailRow
                label={lbl('LBL_CLASS_DESCRIPTION', 'Class description')}
                value={data.description}
              />
              <DetailRow label={lbl('LBL_TEACHER_NAME', 'Teacher name')} value={data.teacher_label} />
              <DetailRow label={lbl('LBL_LANGUAGE', 'Language')} value={data.language_label} />
              <DetailRow label={lbl('LBL_SERVICE_TYPE', 'Service type')} value={data.service_type_label} />
              <DetailRow label={lbl('LBL_CLASS_ADDRESS', 'Class Address')} value={data.class_address} />
            </tbody>
          </table>
        </div>
      ) : null}
    </AdminModal>
  );
}
