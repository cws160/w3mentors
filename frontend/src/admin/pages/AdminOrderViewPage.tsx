import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { Link, useParams } from 'react-router-dom';
import moment from 'moment';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { useSite } from '../../w3mentors/context/SiteContext';
import { openOrderInvoice } from '../utils/orderInvoice';

type OrderDetail = {
  order: Record<string, unknown>;
  payments: Array<Record<string, unknown>>;
  total_paid_amount: number;
  pending_amount: number;
  payment_methods: Record<string, string>;
  child_order: Record<string, unknown> | null;
  bank_transfers: Array<Record<string, unknown>>;
  can_cancel: boolean;
  can_add_payment: boolean;
};

const formatMoney = (value: unknown) => Number(value ?? 0).toFixed(2);

const formatDate = (value: unknown) => {
  if (!value) return '—';
  const m = moment(String(value));
  return m.isValid() ? m.format('MMM DD, YYYY HH:mm') : String(value);
};

export function AdminOrderViewPage() {
  const { orderId: orderIdParam } = useParams<{ orderId: string }>();
  const orderId = Number(orderIdParam ?? 0);
  const { privileges } = useAdminAuth();
  const { lbl } = useSite();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const canEdit = Boolean(privileges.canEditOrders);

  const [data, setData] = useState<OrderDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [paymentForm, setPaymentForm] = useState({
    ordpay_pmethod_id: '',
    ordpay_txn_id: '',
    ordpay_amount: '',
    ordpay_response: '',
  });

  const load = useCallback(async () => {
    if (orderId < 1) return;
    setLoading(true);
    setError('');
    try {
      const res = await adminApi.orderShow(orderId);
      const payload = res.data.data as OrderDetail;
      setData(payload);
      setPaymentForm((prev) => ({
        ...prev,
        ordpay_pmethod_id: String(payload.order.order_pmethod_id ?? ''),
        ordpay_amount: String(payload.pending_amount ?? ''),
      }));
    } catch {
      setError(lbl('LBL_INVALID_REQUEST', 'Invalid request'));
      setData(null);
    } finally {
      setLoading(false);
    }
  }, [orderId, lbl]);

  useEffect(() => {
    setMeta({ title: lbl('LBL_CUSTOMER_ORDER_DETAIL', 'Customer order detail') });
    return () => clearMeta();
  }, [clearMeta, lbl, setMeta]);

  useEffect(() => {
    void load();
  }, [load]);

  const onCancel = async () => {
    if (!window.confirm(lbl('LBL_ARE_YOU_SURE', 'Are you sure?'))) return;
    try {
      await adminApi.cancelOrder(orderId);
      setMessage(lbl('LBL_ORDER_CANCELLED_SUCCESSFULLY', 'Order cancelled successfully'));
      await load();
    } catch (err: unknown) {
      const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
      setError(msg ?? lbl('LBL_INVALID_REQUEST', 'Invalid request'));
    }
  };

  const onPaymentSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setMessage('');
    setError('');
    try {
      await adminApi.addOrderPayment(orderId, {
        ordpay_pmethod_id: Number(paymentForm.ordpay_pmethod_id),
        ordpay_txn_id: paymentForm.ordpay_txn_id,
        ordpay_amount: Number(paymentForm.ordpay_amount),
        ordpay_response: paymentForm.ordpay_response,
      });
      setMessage(lbl('LBL_PAYMENT_DETAILS_ADDED_SUCCESSFULLY', 'Payment details added successfully'));
      await load();
    } catch (err: unknown) {
      const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
      setError(msg ?? lbl('LBL_INVALID_REQUEST', 'Invalid request'));
    }
  };

  const onBankTransfer = async (payId: number, status: number) => {
    setMessage('');
    setError('');
    try {
      await adminApi.updateBankTransferStatus(payId, status);
      setMessage(lbl('LBL_ORDER_PAYMENT_UPDATED_SUCCESSFULLY', 'Order payment updated successfully'));
      await load();
    } catch (err: unknown) {
      const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
      setError(msg ?? lbl('LBL_INVALID_REQUEST', 'Invalid request'));
    }
  };

  if (loading) {
    return <main className="main"><div className="container"><p>{lbl('LBL_LOADING', 'Loading...')}</p></div></main>;
  }

  if (!data) {
    return (
      <main className="main">
        <div className="container">
          <p>{error || lbl('LBL_INVALID_REQUEST', 'Invalid request')}</p>
          <Link to="/admin/orders" className="btn btn-primary">
            {lbl('LBL_BACK_TO_ORDER', 'Back to orders')}
          </Link>
        </div>
      </main>
    );
  }

  const { order, child_order: childOrder } = data;

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <div className="action-toolbar">
            <button
              type="button"
              className="btn btn-primary"
              onClick={() => void openOrderInvoice(orderId)}
            >
              {lbl('LBL_DOWNLOAD_INVOICE', 'Download invoice')}
            </button>
            <Link to="/admin/orders" className="btn btn-primary">
              {lbl('LBL_BACK_TO_ORDER', 'Back to orders')}
            </Link>
          </div>
        </div>

        {message ? <div className="alert alert-success">{message}</div> : null}
        {error ? <div className="alert alert-danger">{error}</div> : null}

        <div className="card">
          <div className="card-table">
            <div className="table-responsive">
              <table className="table table-coloum">
                <tbody>
                  <tr>
                    <td><strong>{lbl('LBL_ORDER_ID', 'Order ID')}:</strong> {String(order.order_id_formatted ?? '')}</td>
                    <td><strong>{lbl('LBL_ORDER_DATE', 'Order date')}:</strong> {formatDate(order.order_addedon)}</td>
                    <td><strong>{lbl('LBL_PAYMENT_STATUS', 'Payment status')}:</strong> {String(order.order_payment_status_label ?? '')}</td>
                    <td><strong>{lbl('LBL_ORDER_TOTAL_AMOUNT', 'Order total amount')}:</strong> {formatMoney(order.order_total_amount)}</td>
                  </tr>
                  <tr>
                    <td><strong>{lbl('LBL_ORDER_DISCOUNT', 'Order discount')}:</strong> {formatMoney(order.order_discount_value)}</td>
                    <td><strong>{lbl('LBL_ORDER_REWARDS', 'Order rewards')}:</strong> {formatMoney(order.order_reward_value)}</td>
                    <td><strong>{lbl('LBL_ORDER_NET_AMOUNT', 'Order net amount')}:</strong> {formatMoney(order.order_net_amount)}</td>
                    <td><strong>{lbl('LBL_ORDER_AMOUNT_PAID', 'Order amount paid')}:</strong> {formatMoney(data.total_paid_amount)}</td>
                  </tr>
                  <tr>
                    <td><strong>{lbl('LBL_ORDER_AMOUNT_PENDING', 'Order amount pending')}:</strong> {formatMoney(data.pending_amount)}</td>
                    <td><strong>{lbl('LBL_ORDER_STATUS', 'Order status')}:</strong> {String(order.order_status_label ?? '')}</td>
                    <td />
                    <td />
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div className="row">
          <div className="col-md-4">
            <div className="card card-height">
              <div className="card-head"><h3 className="card-head-title">{lbl('LBL_USER_DETAILS', 'User details')}</h3></div>
              <div className="card-body list-group">
                <p><strong>{lbl('LBL_NAME', 'Name')}:</strong> {String(order.learner_full_name ?? '')}</p>
                <p><strong>{lbl('LBL_EMAIL', 'Email')}:</strong> {String(order.learner_email ?? '')}</p>
                <p><strong>{lbl('LBL_USER_ID', 'User ID')}:</strong> {String(order.order_user_id ?? '')}</p>
                <p><strong>{lbl('LBL_USER_TIMEZONE', 'User timezone')}:</strong> {String(order.user_timezone ?? '')}</p>
              </div>
            </div>
          </div>
          <div className="col-md-4">
            <div className="card card-height">
              <div className="card-head"><h3 className="card-head-title">{lbl('LBL_ORDER_DETAILS', 'Order details')}</h3></div>
              <div className="card-body list-group">
                <p><strong>{lbl('LBL_ORDER_TYPE', 'Order type')}:</strong> {String(order.order_type_label ?? '')}</p>
                <p><strong>{lbl('LBL_ORDER/INVOICE_ID', 'Order/Invoice ID')}:</strong> {String(order.order_id_formatted ?? '')}</p>
                <p><strong>{lbl('LBL_ORDER_AMOUNT_PAID', 'Order amount paid')}:</strong> {formatMoney(data.total_paid_amount)}</p>
                <p><strong>{lbl('LBL_ORDER_DATE', 'Order date')}:</strong> {formatDate(order.order_addedon)}</p>
              </div>
            </div>
          </div>
          <div className="col-md-4">
            <div className="card card-height">
              <div className="card-head"><h3 className="card-head-title">{lbl('LBL_ORDER_DETAILS', 'Order details')}</h3></div>
              <div className="card-body list-group">
                {childOrder ? Object.entries(childOrder).filter(([k, v]) => k !== 'type' && v !== null && v !== '').map(([k, v]) => (
                  <p key={k}><strong>{k.replace(/_/g, ' ')}:</strong> {typeof v === 'number' && k.includes('amount') ? formatMoney(v) : String(v)}</p>
                )) : <p>—</p>}
              </div>
            </div>
          </div>
        </div>

        {data.payments.length > 0 ? (
          <div className="card">
            <div className="card-head"><h3 className="card-head-title">{lbl('LBL_PAYMENT_HISTORY', 'Payment history')}</h3></div>
            <div className="card-table">
              <table className="table table--hovered">
                <thead>
                  <tr>
                    <th>{lbl('LBL_TXN_ID', 'Txn ID')}</th>
                    <th>{lbl('LBL_AMOUNT', 'Amount')}</th>
                    <th>{lbl('LBL_DATETIME', 'Datetime')}</th>
                  </tr>
                </thead>
                <tbody>
                  {data.payments.map((payment) => (
                    <tr key={String(payment.ordpay_id)}>
                      <td>{String(payment.ordpay_txn_id ?? '')}</td>
                      <td>{formatMoney(payment.ordpay_amount)}</td>
                      <td>{formatDate(payment.ordpay_datetime)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        ) : null}

        {canEdit && data.can_add_payment ? (
          <div className="card">
            <div className="card-head"><h3 className="card-head-title">{lbl('LBL_PAYMENT_DETAILS', 'Payment details')}</h3></div>
            <div className="card-body">
              <form className="form" onSubmit={onPaymentSubmit}>
                <div className="row">
                  <div className="col-md-6">
                    <label>{lbl('LBL_PAYMENT_METHOD', 'Payment method')}</label>
                    <select
                      className="form-control"
                      required
                      value={paymentForm.ordpay_pmethod_id}
                      onChange={(e) => setPaymentForm({ ...paymentForm, ordpay_pmethod_id: e.target.value })}
                    >
                      <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                      {Object.entries(data.payment_methods).map(([id, code]) => (
                        <option key={id} value={id}>{code}</option>
                      ))}
                    </select>
                  </div>
                  <div className="col-md-6">
                    <label>{lbl('LBL_TXN_ID', 'Txn ID')}</label>
                    <input
                      className="form-control"
                      required
                      value={paymentForm.ordpay_txn_id}
                      onChange={(e) => setPaymentForm({ ...paymentForm, ordpay_txn_id: e.target.value })}
                    />
                  </div>
                  <div className="col-md-6">
                    <label>{lbl('LBL_AMOUNT', 'Amount')}</label>
                    <input
                      className="form-control"
                      required
                      readOnly
                      value={paymentForm.ordpay_amount}
                    />
                  </div>
                  <div className="col-md-12">
                    <label>{lbl('LBL_COMMENTS', 'Comments')}</label>
                    <textarea
                      className="form-control"
                      required
                      value={paymentForm.ordpay_response}
                      onChange={(e) => setPaymentForm({ ...paymentForm, ordpay_response: e.target.value })}
                    />
                  </div>
                </div>
                <button type="submit" className="btn btn-primary">{lbl('LBL_SAVE_CHANGES', 'Save changes')}</button>
              </form>
            </div>
          </div>
        ) : null}

        {data.bank_transfers.length > 0 ? (
          <div className="card">
            <div className="card-head"><h3 className="card-head-title">{lbl('LBL_BANK_TRANSFER', 'Bank transfer')}</h3></div>
            <div className="card-table">
              <table className="table table--hovered">
                <thead>
                  <tr>
                    <th>{lbl('LBL_TXN_ID', 'Txn ID')}</th>
                    <th>{lbl('LBL_AMOUNT', 'Amount')}</th>
                    <th>{lbl('LBL_STATUS', 'Status')}</th>
                    <th>{lbl('LBL_ACTION', 'Action')}</th>
                  </tr>
                </thead>
                <tbody>
                  {data.bank_transfers.map((bt) => (
                    <tr key={String(bt.bnktras_id)}>
                      <td>{String(bt.bnktras_txn_id ?? '')}</td>
                      <td>{formatMoney(bt.bnktras_amount)}</td>
                      <td>{String(bt.bnktras_status ?? '')}</td>
                      <td>
                        {canEdit && Number(bt.bnktras_status) === 0 ? (
                          <>
                            <button type="button" className="btn btn-sm btn-primary me-2" onClick={() => void onBankTransfer(Number(bt.bnktras_id), 1)}>
                              {lbl('LBL_APPROVE', 'Approve')}
                            </button>
                            <button type="button" className="btn btn-sm btn-secondary" onClick={() => void onBankTransfer(Number(bt.bnktras_id), 2)}>
                              {lbl('LBL_DECLINE', 'Decline')}
                            </button>
                          </>
                        ) : '—'}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        ) : null}

        {canEdit && data.can_cancel ? (
          <div className="mt-3">
            <button type="button" className="btn btn-danger" onClick={() => void onCancel()}>
              {lbl('LBL_CANCEL_ORDER', 'Cancel order')}
            </button>
          </div>
        ) : null}
      </div>
    </main>
  );
}
