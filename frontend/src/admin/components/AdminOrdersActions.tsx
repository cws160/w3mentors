import { useNavigate } from 'react-router-dom';
import { AdminSpriteIcon } from './AdminSpriteIcon';
import { openOrderInvoice } from '../utils/orderInvoice';

export type OrderActionModule =
  | 'orders'
  | 'lessons'
  | 'subscriptions'
  | 'classes'
  | 'course-orders'
  | 'packages'
  | 'giftcards'
  | 'wallet'
  | 'order-subscription-plans';

type Props = {
  module: OrderActionModule;
  row: Record<string, unknown>;
  canEditOrders: boolean;
  labels: {
    view: string;
    downloadInvoice: string;
    cancel: string;
  };
  onViewSubOrder?: (module: OrderActionModule, row: Record<string, unknown>) => void;
  onCancelOrder?: (orderId: number) => void;
};

export function AdminOrdersActions({
  module,
  row,
  canEditOrders,
  labels,
  onViewSubOrder,
  onCancelOrder,
}: Props) {
  const navigate = useNavigate();
  const orderId = Number(row.order_id ?? row.id ?? 0);

  const actionLink = (title: string, onClick: () => void) => ({
    href: '#',
    title,
    onClick: (e: React.MouseEvent) => {
      e.preventDefault();
      e.stopPropagation();
      onClick();
    },
  });

  const invoiceSubOrderId = (() => {
    switch (module) {
      case 'lessons':
        return Number(row.ordles_id ?? 0) || undefined;
      case 'classes':
        return Number(row.ordcls_id ?? 0) || undefined;
      case 'order-subscription-plans':
        return Number(row.ordsplan_id ?? 0) || undefined;
      default:
        return undefined;
    }
  })();

  const canCancel =
    module === 'orders' &&
    canEditOrders &&
    Number(row.order_payment_status ?? -1) === 0 &&
    Number(row.order_status ?? 0) !== 3;

  const showView =
    module === 'orders' ||
    module === 'course-orders' ||
    module === 'lessons' ||
    module === 'classes' ||
    module === 'packages' ||
    module === 'giftcards' ||
    module === 'order-subscription-plans';

  const handleView = () => {
    if (!orderId || orderId < 1) {
      window.alert('Invalid order.');
      return;
    }
    if (module === 'orders') {
      navigate(`/admin/orders/${orderId}/view`);
      return;
    }
    onViewSubOrder?.(module, row);
  };

  const handleInvoice = () => {
    void openOrderInvoice(orderId, invoiceSubOrderId);
  };

  const handleCancel = () => {
    if (!orderId || orderId < 1) {
      window.alert('Invalid order.');
      return;
    }
    onCancelOrder?.(orderId);
  };

  return (
    <ul className="actions">
      {showView ? (
        <li title={labels.view}>
          <a {...actionLink(labels.view, handleView)}>
            <AdminSpriteIcon icon="view" />
          </a>
        </li>
      ) : null}
      <li title={labels.downloadInvoice}>
        <a {...actionLink(labels.downloadInvoice, handleInvoice)}>
          <AdminSpriteIcon icon="download" />
        </a>
      </li>
      {canCancel ? (
        <li title={labels.cancel}>
          <a {...actionLink(labels.cancel, handleCancel)}>
            <AdminSpriteIcon icon="delete" />
          </a>
        </li>
      ) : null}
    </ul>
  );
}
