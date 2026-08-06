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
import { formatLegacyDateTime } from '../quiz/quizFormat';

type FilterOption = { value: number; label: string };

type GiftcardRow = {
  id: number;
  order_id: number;
  order_id_formatted: string;
  code: string;
  amount: number;
  is_received: boolean;
  sender_name: string;
  receiver_name: string;
  receiver_email: string;
  status: number;
  status_label: string;
  status_class: string;
  created_at: string | null;
};

type GiftcardsResponse = {
  data: GiftcardRow[];
  meta: Paginated<unknown>['meta'];
  filters: {
    giftcard_type: number;
    giftcard_types: FilterOption[];
    giftcard_statuses: FilterOption[];
  };
};

const TYPE_PURCHASED = 1;
const TYPE_RECEIVED = 2;

/** Legacy dashboard/views/giftcard/index.php + search.php */
export function DashboardGiftCardsPage() {
  const { lbl } = useSite();
  const moneySymbol = useDashboardMoney();
  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [giftcardType, setGiftcardType] = useState(String(TYPE_PURCHASED));
  const [giftcardStatus, setGiftcardStatus] = useState('');
  const [rows, setRows] = useState<GiftcardRow[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [typeOptions, setTypeOptions] = useState<FilterOption[]>([]);
  const [statusOptions, setStatusOptions] = useState<FilterOption[]>([]);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    setLoading(true);
    api
      .get<GiftcardsResponse>('/dashboard/giftcards', {
        params: {
          page,
          keyword: keyword || undefined,
          giftcard_type: giftcardType || undefined,
          giftcard_status: giftcardStatus || undefined,
        },
      })
      .then((res) => {
        setRows(res.data.data);
        setMeta(res.data.meta);
        setTypeOptions(res.data.filters?.giftcard_types ?? []);
        setStatusOptions(res.data.filters?.giftcard_statuses ?? []);
      })
      .catch(() => {
        setRows([]);
        setMeta(null);
      })
      .finally(() => setLoading(false));
  }, [page, keyword, giftcardType, giftcardStatus]);

  useEffect(() => {
    load();
  }, [load]);

  const clearSearch = () => {
    setKeyword('');
    setGiftcardType(String(TYPE_PURCHASED));
    setGiftcardStatus('');
    setPage(1);
  };

  const personLabel =
    Number(giftcardType) === TYPE_RECEIVED
      ? lbl('LBL_SENDER', 'Sender')
      : lbl('LBL_RECEIVER', 'Receiver');

  return (
    <div className="container container--fixed">
      <DashboardManagePageHead
        title={lbl('LBL_GIFT_CARDS', 'Gift cards')}
        searchOpen={searchOpen}
        searchToggle={<DashboardSearchToggle onClick={() => setSearchOpen((v) => !v)} />}
        actions={
          <a href={dashboardUrl('giftcard/form')} className="btn color-secondary btn--bordered">
            <DashboardSpriteIcon id="giftcards" className="icon icon--gift icon--small me-2" />
            {lbl('LBL_BUY_GIFTCARD', 'Buy giftcard')}
          </a>
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
                        placeholder={lbl('LBL_SEARCH_BY_NAME_EMAIL_CODE', 'Search by name, email or code')}
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
                    <label className="field_label">{lbl('LBL_TYPE', 'Type')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={giftcardType}
                        onChange={(e) => setGiftcardType(e.target.value)}
                      >
                        {typeOptions.map((opt) => (
                          <option key={opt.value} value={opt.value}>
                            {opt.label}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-lg-4 col-sm-6">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_STATUS', 'Status')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={giftcardStatus}
                        onChange={(e) => setGiftcardStatus(e.target.value)}
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        {statusOptions.map((opt) => (
                          <option key={opt.value} value={opt.value}>
                            {opt.label}
                          </option>
                        ))}
                      </select>
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
        <div className="page-content" id="listing">
          {loading ? (
            <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
          ) : rows.length === 0 ? (
            <DashboardNoRecord />
          ) : (
            <>
              <div className="table-scroll">
                <table className="table table--styled table--responsive">
                  <tr className="title-row">
                    <th>{lbl('LBL_ORDER_ID', 'Order ID')}</th>
                    <th>{lbl('LBL_CODE', 'Code')}</th>
                    <th>{lbl('LBL_AMOUNT', 'Amount')}</th>
                    <th>{personLabel}</th>
                    <th>{lbl('LBL_DATE', 'Date')}</th>
                    <th>{lbl('LBL_STATUS', 'Status')}</th>
                  </tr>
                  {rows.map((row) => (
                    <tr key={row.id}>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_ORDER_ID', 'Order ID')}>
                          {row.order_id_formatted}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_CODE', 'Code')}>{row.code}</DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_AMOUNT', 'Amount')}>
                          {formatMoney(row.amount, moneySymbol)}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={personLabel}>
                          {row.is_received ? (
                            <div className="data-group">
                              <span>{row.sender_name}</span>
                            </div>
                          ) : (
                            <div className="data-group">
                              <span>{row.receiver_name}</span>
                              {row.receiver_email && (
                                <>
                                  <br />
                                  <span>
                                    <small>{row.receiver_email}</small>
                                  </span>
                                </>
                              )}
                            </div>
                          )}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_DATE', 'Date')}>
                          {formatLegacyDateTime(row.created_at)}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_STATUS', 'Status')}>
                          <span className={`badge ${row.status_class} badge--curve`}>{row.status_label}</span>
                        </DashboardFlexCell>
                      </td>
                    </tr>
                  ))}
                </table>
              </div>
              {meta && (
                <DashboardListingPagination meta={meta} page={page} loading={loading} onPageChange={setPage} />
              )}
            </>
          )}
        </div>
      </div>
    </div>
  );
}
