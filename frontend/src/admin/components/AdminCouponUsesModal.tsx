import { useCallback, useEffect, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminLegacyPagination } from './AdminLegacyPagination';
import { AdminModal } from './AdminModal';

type UsesRow = {
  order_id: string;
  customer_name: string;
  order_total_amount: string;
  order_addedon: string;
  is_released: boolean;
};

type Props = {
  open: boolean;
  couponId: number;
  onClose: () => void;
};

export function AdminCouponUsesModal({ open, couponId, onClose }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [rows, setRows] = useState<UsesRow[]>([]);
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
  });

  const load = useCallback(
    (nextPage: number) => {
      if (couponId < 1) {
        return;
      }
      setLoading(true);
      void adminApi
        .couponUses(couponId, nextPage)
        .then((res) => {
          setRows((res.data.data ?? []) as UsesRow[]);
          setMeta({
            current_page: Number(res.data.meta?.current_page ?? 1),
            per_page: Number(res.data.meta?.per_page ?? 10),
            total: Number(res.data.meta?.total ?? 0),
            last_page: Number(res.data.meta?.last_page ?? 1),
          });
        })
        .catch(() => setRows([]))
        .finally(() => setLoading(false));
    },
    [couponId],
  );

  useEffect(() => {
    if (!open) {
      setRows([]);
      setPage(1);
      return;
    }
    load(page);
  }, [load, open, page]);

  useEffect(() => {
    if (open) {
      setPage(1);
    }
  }, [couponId, open]);

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_History', 'History')}
      size="lg"
      onClose={onClose}
    >
      <div className="form-edit-body p-0">
        {loading ? (
          <div className="table-processing loaderJs p-5">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : (
          <>
            <div className="table-responsive">
              <table className="table" width="100%">
                <thead>
                  <tr>
                    <th>{lbl('LBL_Sr._No', 'Sr no')}</th>
                    <th>{lbl('LBL_Order_Id', 'Order id')}</th>
                    <th>{lbl('LBL_Customer', 'Customer')}</th>
                    <th>{lbl('LBL_Amount', 'Amount')}</th>
                    <th>{lbl('LBL_Date', 'Date')}</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.length === 0 ? (
                    <tr>
                      <td colSpan={5} className="text-center">
                        {lbl('LBL_NO_RECORDS_FOUND', 'No records found')}
                      </td>
                    </tr>
                  ) : (
                    rows.map((row, index) => (
                      <tr key={`${row.order_id}-${index}`}>
                        <td className={row.is_released ? 'text-danger' : ''}>
                          {(page - 1) * meta.per_page + index + 1}
                        </td>
                        <td className={row.is_released ? 'text-danger' : ''}>{row.order_id}</td>
                        <td className={row.is_released ? 'text-danger' : ''}>{row.customer_name}</td>
                        <td className={row.is_released ? 'text-danger' : ''}>{row.order_total_amount}</td>
                        <td className={row.is_released ? 'text-danger' : ''}>{row.order_addedon}</td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
            {meta.last_page > 1 ? (
              <AdminLegacyPagination
                page={meta.current_page}
                lastPage={meta.last_page}
                perPage={meta.per_page}
                total={meta.total}
                onPageChange={setPage}
                labels={{
                  showing: lbl('LBL_Showing', 'Showing'),
                  to: lbl('LBL_to', 'to'),
                  of: lbl('LBL_of', 'of'),
                  entries: lbl('LBL_Entries', 'Entries'),
                }}
              />
            ) : null}
          </>
        )}
      </div>
    </AdminModal>
  );
}
