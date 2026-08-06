import { useCallback, useEffect, useState } from 'react';
import { api, type Paginated } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { formatMoney } from '../../utils/assets';
import { dashboardUrl } from '../../utils/dashboard';
import { DashboardFlexCell } from '../components/DashboardFlexCell';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardManagePageHead } from '../components/DashboardManagePageHead';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { DashboardSearchToggle } from '../components/DashboardSearchToggle';
import { useDashboardMoney } from '../hooks/useDashboardMoney';
import { formatWithdrawalId } from '../wallet/walletFormat';
import { formatLegacyDateTime } from '../quiz/quizFormat';

type WithdrawRow = {
  id: number;
  request_id_formatted: string;
  amount: number;
  transaction_fee: number;
  comments: string;
  status: number;
  status_label: string;
  requested_at: string | null;
};

type WithdrawResponse = {
  data: WithdrawRow[];
  meta: Paginated<unknown>['meta'];
  balance: number;
  can_withdraw: boolean;
};

/** Legacy dashboard/views/wallet/withdraw-requests.php + search-withdraw-requests.php */
export function DashboardWithdrawRequestsPage() {
  const { lbl } = useSite();
  const moneySymbol = useDashboardMoney();
  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [rows, setRows] = useState<WithdrawRow[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [canWithdraw, setCanWithdraw] = useState(false);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    setLoading(true);
    api
      .get<WithdrawResponse>('/dashboard/withdrawals', {
        params: {
          page,
          keyword: keyword || undefined,
          date_from: dateFrom || undefined,
          date_to: dateTo || undefined,
        },
      })
      .then((res) => {
        setRows(res.data.data);
        setMeta(res.data.meta);
        setCanWithdraw(res.data.can_withdraw);
      })
      .catch(() => {
        setRows([]);
        setMeta(null);
        setCanWithdraw(false);
      })
      .finally(() => setLoading(false));
  }, [page, keyword, dateFrom, dateTo]);

  useEffect(() => {
    load();
  }, [load]);

  const clearSearch = () => {
    setKeyword('');
    setDateFrom('');
    setDateTo('');
    setPage(1);
  };

  return (
    <div className="container container--fixed">
      <DashboardManagePageHead
        title={lbl('LBL_WITHDRAW_REQUESTS', 'Withdraw requests')}
        searchOpen={searchOpen}
        searchToggle={<DashboardSearchToggle onClick={() => setSearchOpen((v) => !v)} />}
        actions={
          canWithdraw ? (
            <a
              href={dashboardUrl('wallet/withdrawRequests')}
              target="_blank"
              rel="noreferrer"
              className="btn btn--bordered btn--transparent color-secondary"
            >
              <DashboardSpriteIcon id="withdraw" className="icon icon--withdraw icon--small me-2" />
              {lbl('LBL_REQUEST_WITHDRAWAL', 'Request withdrawal')}
            </a>
          ) : undefined
        }
        searchPanel={
          <form
            className="form form--small"
            onSubmit={(e) => {
              e.preventDefault();
              setPage(1);
              load();
            }}
          >
            <div className="row">
              <div className="col-lg-4 col-sm-6">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_KEYWORD', 'Keyword')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="text"
                        className="form-control"
                        value={keyword}
                        onChange={(e) => setKeyword(e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-lg-4 col-sm-6">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_DATE_FROM', 'Date from')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="date"
                        className="form-control"
                        value={dateFrom}
                        onChange={(e) => setDateFrom(e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-lg-4 col-sm-6">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_DATE_TO', 'Date to')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="date"
                        className="form-control"
                        value={dateTo}
                        onChange={(e) => setDateTo(e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-lg-4 col-sm-6 form-buttons-group">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label" />
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input type="submit" className="btn btn--primary" value={lbl('LBL_SEARCH', 'Search')} />
                      <input
                        type="button"
                        className="btn btn--secondary ms-2"
                        value={lbl('LBL_CLEAR', 'Clear')}
                        onClick={clearSearch}
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        }
      />
      <div className="page__body">
        <div className="page-content">
          <div id="withdraw-listing" className="table-scroll">
            {loading ? (
              <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
            ) : rows.length === 0 ? (
              <DashboardNoRecord />
            ) : (
              <>
                <table className="table table--styled table--responsive table--aligned-middle">
                  <tr className="title-row">
                    <th>{lbl('LBL_WITHDRAWAL_ID', 'Withdrawal ID')}</th>
                    <th>{lbl('LBL_AMOUNT', 'Amount')}</th>
                    <th>{lbl('LBL_TXN_FEE', 'Txn fee')}</th>
                    <th>{lbl('LBL_COMMENTS', 'Comments')}</th>
                    <th>{lbl('LBL_DATE', 'Date')}</th>
                    <th>{lbl('LBL_STATUS', 'Status')}</th>
                  </tr>
                  {rows.map((row) => (
                    <tr key={row.id}>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_WITHDRAWAL_ID', 'Withdrawal ID')}>
                          {row.request_id_formatted || formatWithdrawalId(row.id)}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_AMOUNT', 'Amount')}>
                          {formatMoney(row.amount, moneySymbol)}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_TXN_FEE', 'Txn fee')}>
                          {formatMoney(row.transaction_fee, moneySymbol)}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_COMMENTS', 'Comments')}>
                          <span style={{ whiteSpace: 'pre-wrap' }}>{row.comments || '—'}</span>
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_DATE', 'Date')}>
                          {formatLegacyDateTime(row.requested_at)}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_STATUS', 'Status')}>
                          {row.status_label || row.status}
                        </DashboardFlexCell>
                      </td>
                    </tr>
                  ))}
                </table>
                {meta && (
                  <DashboardListingPagination meta={meta} page={page} loading={loading} onPageChange={setPage} />
                )}
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
