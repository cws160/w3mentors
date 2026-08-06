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
import { formatTxnId } from '../wallet/walletFormat';
import { formatLegacyDateTime } from '../quiz/quizFormat';

type WalletTxn = {
  id: number;
  txn_id_formatted: string;
  amount: number;
  type: number;
  type_label: string;
  comment: string;
  created_at: string | null;
};

type WalletResponse = {
  data: {
    balance: number;
    transactions: WalletTxn[];
  };
  meta: Paginated<unknown>['meta'];
};

/** Legacy dashboard/views/wallet/index.php + search.php */
export function DashboardWalletPage() {
  const { lbl } = useSite();
  const moneySymbol = useDashboardMoney();
  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [balance, setBalance] = useState(0);
  const [rows, setRows] = useState<WalletTxn[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    setLoading(true);
    api
      .get<WalletResponse>('/dashboard/wallet', {
        params: {
          page,
          keyword: keyword || undefined,
          date_from: dateFrom || undefined,
          date_to: dateTo || undefined,
        },
      })
      .then((res) => {
        setBalance(res.data.data.balance);
        setRows(res.data.data.transactions);
        setMeta(res.data.meta);
      })
      .catch(() => {
        setBalance(0);
        setRows([]);
        setMeta(null);
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
        title={lbl('LBL_MY_WALLET', 'My wallet')}
        searchOpen={searchOpen}
        searchToggle={<DashboardSearchToggle onClick={() => setSearchOpen((v) => !v)} />}
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
          <div className="wallet-box page-container mb-4 p-4">
            <div className="row justify-content-between align-items-center">
              <div className="col-sm-4">
                <div className="wallet d-flex">
                  <div className="wallet__media">
                    <DashboardSpriteIcon id="wallet-large" className="icon icon--wallet icon--large me-3" />
                  </div>
                  <div className="wallet__content">
                    <span className="m-0">{lbl('LBL_WALLET_BALANCE', 'Wallet balance')}</span>
                    <h3 className="bold-700">{formatMoney(balance, moneySymbol)}</h3>
                  </div>
                </div>
              </div>
              <div className="col-xl-auto col-lg-8 col-12">
                <div className="buttons-group d-flex align-items-center gap-2">
                  <a
                    href={dashboardUrl('wallet')}
                    target="_blank"
                    rel="noreferrer"
                    className="btn btn--transparent color-primary"
                  >
                    <DashboardSpriteIcon id="plus" className="icon icon--issue icon--small me-2" />
                    {lbl('LBL_RECHARGE_WALLET', 'Recharge wallet')}
                  </a>
                  <a
                    href={dashboardUrl('wallet')}
                    target="_blank"
                    rel="noreferrer"
                    className="btn btn--transparent color-primary"
                  >
                    <DashboardSpriteIcon id="giftcards" className="icon icon--gift me-1" />
                    {lbl('LBL_REDEEM_GIFT_CARD', 'Redeem gift card')}
                  </a>
                </div>
              </div>
            </div>
          </div>
          <div id="wallet-listing" className="table-scroll">
            {loading ? (
              <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
            ) : rows.length === 0 ? (
              <DashboardNoRecord />
            ) : (
              <>
                <table className="table table--styled table--responsive table--aligned-middle">
                  <tr className="title-row">
                    <th>{lbl('LBL_TXN_ID', 'Txn ID')}</th>
                    <th>{lbl('LBL_TYPE', 'Type')}</th>
                    <th>{lbl('LBL_AMOUNT', 'Amount')}</th>
                    <th>{lbl('LBL_DATE', 'Date')}</th>
                    <th>{lbl('LBL_COMMENTS', 'Comments')}</th>
                  </tr>
                  {rows.map((row) => (
                    <tr key={row.id}>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_TXN_ID', 'Txn ID')}>
                          {row.txn_id_formatted || formatTxnId(row.id)}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_TYPE', 'Type')}>
                          {row.type_label || row.type}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_AMOUNT', 'Amount')}>
                          {formatMoney(row.amount, moneySymbol)}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_DATE', 'Date')}>
                          {formatLegacyDateTime(row.created_at)}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_COMMENTS', 'Comments')}>
                          {row.comment || '—'}
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
