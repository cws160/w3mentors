import { Fragment, useCallback, useEffect, useState } from 'react';
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
import { formatOrderId } from '../orders/orderFormat';
import { formatLegacyDateTime } from '../quiz/quizFormat';

type FilterOption = { value: number; label: string; code?: string };

type OrderRow = {
  id: number;
  order_id_formatted: string;
  type: number;
  type_label: string;
  item_count: number;
  amount: number;
  payment_method_id: number;
  payment_method_code: string;
  payment_method_label: string;
  payment_status: number;
  payment_status_label: string;
  is_paid: boolean;
  status: number;
  status_label: string;
  created_at: string | null;
};

type OrdersResponse = {
  data: OrderRow[];
  meta: Paginated<unknown>['meta'];
  filters: {
    order_types: FilterOption[];
    payment_methods: FilterOption[];
  };
};

/** Legacy dashboard/views/orders/index.php + search.php */
export function DashboardOrdersPage() {
  const { lbl } = useSite();
  const moneySymbol = useDashboardMoney();
  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [orderType, setOrderType] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [rows, setRows] = useState<OrderRow[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [orderTypes, setOrderTypes] = useState<FilterOption[]>([]);
  const [paymentMethods, setPaymentMethods] = useState<FilterOption[]>([]);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [expandedId, setExpandedId] = useState<number | null>(null);

  const load = useCallback(() => {
    setLoading(true);
    api
      .get<OrdersResponse>('/dashboard/orders', {
        params: {
          page,
          keyword: keyword || undefined,
          order_type: orderType || undefined,
          order_pmethod_id: paymentMethod || undefined,
          date_from: dateFrom || undefined,
          date_to: dateTo || undefined,
        },
      })
      .then((res) => {
        setRows(res.data.data);
        setMeta(res.data.meta);
        setOrderTypes(res.data.filters?.order_types ?? []);
        setPaymentMethods(res.data.filters?.payment_methods ?? []);
      })
      .catch(() => {
        setRows([]);
        setMeta(null);
      })
      .finally(() => setLoading(false));
  }, [page, keyword, orderType, paymentMethod, dateFrom, dateTo]);

  useEffect(() => {
    load();
  }, [load]);

  const clearSearch = () => {
    setKeyword('');
    setOrderType('');
    setPaymentMethod('');
    setDateFrom('');
    setDateTo('');
    setPage(1);
  };

  const toggleView = (row: OrderRow) => {
    setExpandedId((current) => (current === row.id ? null : row.id));
  };

  const invoiceUrl = (orderId: number) =>
    `${dashboardUrl(`orders/viewInvoice/${orderId}`)}?t=${Date.now()}`;

  return (
    <div className="container container--fixed">
      <DashboardManagePageHead
        title={lbl('LBL_MY_ORDERS', 'My orders')}
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
                        placeholder={lbl('LBL_SEARCH_BY_KEYWORD', 'Search by keyword')}
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
                    <label className="field_label">{lbl('LBL_ORDER_TYPE', 'Order type')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select className="form-control" value={orderType} onChange={(e) => setOrderType(e.target.value)}>
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        {orderTypes.map((opt) => (
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
                    <label className="field_label">{lbl('LBL_PAYMENT_METHOD', 'Payment method')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={paymentMethod}
                        onChange={(e) => setPaymentMethod(e.target.value)}
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        {paymentMethods.map((opt) => (
                          <option key={opt.value} value={opt.value}>
                            {opt.code ? lbl(`LBL_${opt.code}`, opt.label) : opt.label}
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
        <div className="page-content" id="order-listing">
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
                    <th>{lbl('LBL_TYPE', 'Type')}</th>
                    <th>{lbl('LBL_ITEMS', 'Items')}</th>
                    <th>{lbl('LBL_NET_AMOUNT', 'Net amount')}</th>
                    <th>{lbl('LBL_PAYMENT_METHOD', 'Payment method')}</th>
                    <th>{lbl('LBL_PAYMENT', 'Payment')}</th>
                    <th>{lbl('LBL_STATUS', 'Status')}</th>
                    <th>{lbl('LBL_DATETIME', 'Date & time')}</th>
                    <th>{lbl('LBL_ACTION', 'Action')}</th>
                  </tr>
                  {rows.map((row) => (
                    <Fragment key={row.id}>
                      <tr className={`row-trigger${expandedId === row.id ? ' action-trigger is-active' : ''}`}>
                        <td>
                          <DashboardFlexCell label={lbl('LBL_ORDER_ID', 'Order ID')}>
                            <div className="order-action">
                              <div
                                className="d-flex align-items-center"
                                role="button"
                                tabIndex={0}
                                onClick={() => toggleView(row)}
                                onKeyDown={(e) => {
                                  if (e.key === 'Enter' || e.key === ' ') toggleView(row);
                                }}
                              >
                                <a href="javascript:void(0);" className="color-primary bold-600">
                                  {row.order_id_formatted || formatOrderId(row.id)}
                                </a>
                                <span className="arrow-icon" />
                              </div>
                            </div>
                          </DashboardFlexCell>
                        </td>
                        <td>
                          <DashboardFlexCell label={lbl('LBL_TYPE', 'Type')}>
                            <div style={{ maxWidth: 250 }}>{row.type_label}</div>
                          </DashboardFlexCell>
                        </td>
                        <td>
                          <DashboardFlexCell label={lbl('LBL_ITEMS', 'Items')}>
                            <div style={{ maxWidth: 250 }}>{row.item_count}</div>
                          </DashboardFlexCell>
                        </td>
                        <td>
                          <DashboardFlexCell label={lbl('LBL_NET_AMOUNT', 'Net amount')}>
                            <div style={{ maxWidth: 250 }}>{formatMoney(row.amount, moneySymbol)}</div>
                          </DashboardFlexCell>
                        </td>
                        <td>
                          <DashboardFlexCell label={lbl('LBL_PAYMENT_METHOD', 'Payment method')}>
                            <div style={{ maxWidth: 250 }}>
                              {row.payment_method_code
                                ? lbl(`LBL_${row.payment_method_code}`, row.payment_method_label)
                                : row.payment_method_label}
                            </div>
                          </DashboardFlexCell>
                        </td>
                        <td>
                          <DashboardFlexCell label={lbl('LBL_PAYMENT', 'Payment')}>
                            <span
                              className={`badge badge--curve color-${row.is_paid ? 'green' : 'yellow'}`}
                            >
                              {row.payment_status_label}
                            </span>
                          </DashboardFlexCell>
                        </td>
                        <td>
                          <DashboardFlexCell label={lbl('LBL_STATUS', 'Status')}>
                            <span>{row.status_label}</span>
                          </DashboardFlexCell>
                        </td>
                        <td>
                          <DashboardFlexCell label={lbl('LBL_DATETIME', 'Date & time')}>
                            <div style={{ maxWidth: 250 }}>{formatLegacyDateTime(row.created_at)}</div>
                          </DashboardFlexCell>
                        </td>
                        <td>
                          <DashboardFlexCell label={lbl('LBL_ACTION', 'Action')}>
                            <div className="actions-group">
                              <button
                                type="button"
                                className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                                onClick={() => toggleView(row)}
                                title={lbl('LBL_VIEW', 'View')}
                              >
                                <DashboardSpriteIcon id="list" className="icon icon--cancel icon--small" />
                                <div className="tooltip tooltip--top bg-black">{lbl('LBL_VIEW', 'View')}</div>
                              </button>
                              <a
                                href={invoiceUrl(row.id)}
                                target="_blank"
                                rel="noreferrer"
                                className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                                title={lbl('LBL_DOWNLOAD_INVOICE', 'Download invoice')}
                              >
                                <DashboardSpriteIcon id="download" className="icon icon--cancel icon--small" />
                                <div className="tooltip tooltip--top bg-black">
                                  {lbl('LBL_DOWNLOAD_INVOICE', 'Download invoice')}
                                </div>
                              </a>
                            </div>
                          </DashboardFlexCell>
                        </td>
                      </tr>
                      {expandedId === row.id && (
                        <tr className="target-data target-data-js is-active is-expanded">
                          <td className="-no-padding" colSpan={9}>
                            <div className="target-data__group">
                              <div className="detail-list">
                                <div className="detail-list__item">
                                  <div className="detail-info">
                                    <div className="detail-info__title detail-title">
                                      <h6>{lbl('LBL_ORDER_DETAIL', 'Order detail')}</h6>
                                    </div>
                                    <div className="detail-info__listing">
                                      <div className="detail-info__row">
                                        {lbl('LBL_ORDER_ID', 'Order ID')}:{' '}
                                        {row.order_id_formatted || formatOrderId(row.id)}
                                      </div>
                                      <div className="detail-info__row">
                                        {lbl('LBL_ORDER_DATE', 'Order date')}: {formatLegacyDateTime(row.created_at)}
                                      </div>
                                      <div className="detail-info__row">
                                        {lbl('LBL_NET_AMOUNT', 'Net amount')}: {formatMoney(row.amount, moneySymbol)}
                                      </div>
                                      <div className="detail-info__row">
                                        {lbl('LBL_PAY_METHOD', 'Payment method')}:{' '}
                                        {row.payment_method_code
                                          ? lbl(`LBL_${row.payment_method_code}`, row.payment_method_label)
                                          : row.payment_method_label}
                                      </div>
                                      <div className="detail-info__row">
                                        {lbl('LBL_TYPE', 'Type')}: {row.type_label}
                                      </div>
                                      <div className="detail-info__row">
                                        {lbl('LBL_STATUS', 'Status')}: {row.status_label}
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </td>
                        </tr>
                      )}
                    </Fragment>
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
