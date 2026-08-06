import { useEffect, useState } from 'react';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type HistoryRow = {
  user_id: number;
  is_global: boolean;
  affiliate_name: string;
  commission: string;
  created_at: string;
};

type Props = {
  open: boolean;
  userId: number;
  onClose: () => void;
};

export function AdminAffiliateCommissionHistoryModal({ open, userId, onClose }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [rows, setRows] = useState<HistoryRow[]>([]);

  useEffect(() => {
    if (!open) {
      setRows([]);
      return;
    }

    setLoading(true);
    void adminApi
      .affiliateCommissionHistory(userId)
      .then((res) => setRows((res.data.data ?? []) as HistoryRow[]))
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [open, userId]);

  const formatDate = (value: string) => {
    if (!value || value.startsWith('0000-00-00')) return lbl('LBL_NA', 'NA');
    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM D, YYYY HH:mm') : value;
  };

  const affiliateLabel = (row: HistoryRow) => {
    if (row.is_global) {
      return (
        <span className="label label-success">{lbl('LBL_GLOBAL_COMMISSION', 'Global commission')}</span>
      );
    }
    return row.affiliate_name || lbl('LBL_NA', 'NA');
  };

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_AFFILIATE_COMMISSION_HISTORY', 'Affiliate commission history')}
      size="lg"
      onClose={onClose}
    >
      <div className="form-edit-body p-0">
        {loading ? (
          <div className="table-processing loaderJs p-5">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : (
          <div className="table-responsive">
            <table className="table" width="100%">
              <thead>
                <tr>
                  <th>{lbl('LBL_Sr._No', 'Sr no')}</th>
                  <th>{lbl('LBL_USER', 'User')}</th>
                  <th>{lbl('LBL_COMMISSION_[%]', 'Commission (%)')}</th>
                  <th>{lbl('LBL_ADDED_ON', 'Added on')}</th>
                </tr>
              </thead>
              <tbody>
                {rows.length === 0 ? (
                  <tr>
                    <td colSpan={4} className="text-center">
                      {lbl('LBL_NO_RECORD_FOUND', 'No record found')}
                    </td>
                  </tr>
                ) : (
                  rows.map((row, index) => (
                    <tr key={`${row.created_at}-${index}`}>
                      <td>{index + 1}</td>
                      <td>{affiliateLabel(row)}</td>
                      <td>{row.commission}</td>
                      <td>{formatDate(row.created_at)}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </AdminModal>
  );
}
