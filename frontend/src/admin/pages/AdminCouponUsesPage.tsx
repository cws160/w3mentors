import { useCallback, useEffect, useState } from 'react';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminLegacyPagination } from '../components/AdminLegacyPagination';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';

type UsesRow = {
  order_id: string;
  customer_name: string;
  order_total_amount: string;
  order_addedon: string;
  is_released: boolean;
};

export function AdminCouponUsesPage() {
  const { lbl } = useSite();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const { couponId: couponIdParam } = useParams<{ couponId: string }>();
  const [searchParams, setSearchParams] = useSearchParams();
  const couponId = Number(couponIdParam ?? 0);
  const page = Number(searchParams.get('page') ?? 1);

  const [loading, setLoading] = useState(true);
  const [rows, setRows] = useState<UsesRow[]>([]);
  const [meta, setListMeta] = useState({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
  });

  const load = useCallback(() => {
    if (couponId < 1) {
      setRows([]);
      setLoading(false);
      return;
    }
    setLoading(true);
    void adminApi
      .couponUses(couponId, page)
      .then((res) => {
        setRows((res.data.data ?? []) as UsesRow[]);
        setListMeta({
          current_page: Number(res.data.meta?.current_page ?? 1),
          per_page: Number(res.data.meta?.per_page ?? 10),
          total: Number(res.data.meta?.total ?? 0),
          last_page: Number(res.data.meta?.last_page ?? 1),
        });
      })
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, [couponId, page]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    setMeta({ title: lbl('LBL_History', 'History') });
    return () => clearMeta();
  }, [clearMeta, lbl, setMeta]);

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">
              <Link to="/admin/coupons">{lbl('LBL_Coupons', 'Coupons')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_History', 'History')}</li>
          </ul>
        </div>

        <div className="card">
          <div className="card-table">
            <div className="table-responsive" id="listing">
              {loading ? (
                <div className="table-processing loaderJs">
                  <div className="spinner spinner--sm spinner--brand" />
                </div>
              ) : (
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
                          <td>{(page - 1) * meta.per_page + index + 1}</td>
                          <td>{row.order_id}</td>
                          <td>{row.customer_name}</td>
                          <td>{row.order_total_amount}</td>
                          <td>{row.order_addedon}</td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              )}
            </div>
          </div>
        </div>

        {!loading && meta.last_page > 1 ? (
          <AdminLegacyPagination
            page={meta.current_page}
            pageCount={meta.last_page}
            recordCount={meta.total}
            pageSize={meta.per_page}
            onPageChange={(nextPage) => {
              const next = new URLSearchParams(searchParams);
              next.set('page', String(nextPage));
              setSearchParams(next);
            }}
          />
        ) : null}
      </div>
    </main>
  );
}
