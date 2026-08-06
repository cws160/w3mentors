import { useEffect, useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import moment from 'moment';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';
import type { OrderActionModule } from './AdminOrdersActions';

type Props = {
  module: OrderActionModule | null;
  row: Record<string, unknown> | null;
  onClose: () => void;
  lbl: (key: string, fallback: string) => string;
};

const formatMoney = (value: unknown) => {
  const amount = Number(value ?? 0);
  if (Number.isNaN(amount)) return '—';
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(amount);
};

const formatDate = (value: unknown) => {
  if (!value || value === '0000-00-00 00:00:00') {
    return '—';
  }
  const m = moment(String(value));
  return m.isValid() ? m.format('MMM DD, YYYY HH:mm') : '—';
};

const yesNo = (value: unknown, lbl: Props['lbl']) => {
  if (value === null || value === undefined || value === '') {
    return lbl('LBL_NO', 'No');
  }
  return Number(value) > 0 ? lbl('LBL_YES', 'Yes') : lbl('LBL_NO', 'No');
};

const na = (value: unknown, lbl: Props['lbl']) => {
  if (value === null || value === undefined || value === '') {
    return lbl('LBL_NA', 'N/A');
  }
  return String(value);
};

const DetailRow = ({
  label,
  value,
  wide = false,
}: {
  label: string;
  value: ReactNode;
  wide?: boolean;
}) => (
  <tr>
    <th width={wide ? '40%' : undefined}>{label}:</th>
    <td>{value}</td>
  </tr>
);

const OrderIdLink = ({
  orderId,
  orderIdFormatted,
  lbl,
}: {
  orderId: number;
  orderIdFormatted: string;
  lbl: Props['lbl'];
}) => (
  <Link to={`/admin/orders/${orderId}/view`} className="link-text link-underline" target="_blank">
    {lbl('LBL_VIEW', 'View')} {orderIdFormatted}
  </Link>
);

function LessonDetail({ data, lbl }: { data: Record<string, unknown>; lbl: Props['lbl'] }) {
  const orderId = Number(data.order_id ?? 0);
  const orderIdFormatted = String(data.order_id_formatted ?? orderId);

  return (
    <table className="table table-coloum">
      <tbody>
        <DetailRow label={lbl('LBL_LEARNER_NAME', 'Learner name')} value={na(data.learner_name, lbl)} />
        <DetailRow label={lbl('LBL_TEACHER_NAME', 'Teacher name')} value={na(data.teacher_name, lbl)} />
        <DetailRow label={lbl('LBL_LANGUAGE', 'Language')} value={na(data.ordles_tlang_name, lbl)} />
        <DetailRow label={lbl('LBL_SERVICE_TYPE', 'Service type')} value={na(data.service_type_label, lbl)} />
        <DetailRow label={lbl('LBL_STATUS', 'Status')} value={na(data.ordles_status_label, lbl)} />
        <DetailRow label={lbl('LBL_START_TIME', 'Start time')} value={formatDate(data.ordles_lesson_starttime)} />
        <DetailRow label={lbl('LBL_ENDS', 'Ends')} value={formatDate(data.ordles_lesson_endtime)} />
        <DetailRow
          label={lbl('LBL_TEACHER_START_TIME', 'Teacher start time')}
          value={formatDate(data.ordles_teacher_starttime)}
        />
        <DetailRow
          label={lbl('LBL_TEACHER_END_TIME', 'Teacher end time')}
          value={formatDate(data.ordles_teacher_endtime)}
        />
        <DetailRow
          label={lbl('LBL_LEARNER_START_TIME', 'Learner start time')}
          value={formatDate(data.ordles_student_starttime)}
        />
        <DetailRow
          label={lbl('LBL_LEARNER_END_TIME', 'Learner end time')}
          value={formatDate(data.ordles_student_endtime)}
        />
        <DetailRow label={lbl('LBL_LESSON_PRICE', 'Lesson price')} value={formatMoney(data.ordles_amount)} />
        <DetailRow
          wide
          label={lbl('LBL_ORDER_DISCOUNT_TOTAL', 'Order discount total')}
          value={formatMoney(data.ordles_discount)}
        />
        <DetailRow
          wide
          label={lbl('LBL_REWARD_DISCOUNT', 'Reward discount')}
          value={formatMoney(data.ordles_reward_discount)}
        />
        <DetailRow
          wide
          label={lbl('LBL_ORDER_NET_AMOUNT', 'Order net amount')}
          value={formatMoney(data.ordles_net_amount)}
        />
        <DetailRow
          label={lbl('LBL_ADMIN_COMMISSION', 'Admin commission')}
          value={formatMoney(data.ordles_commission_amount)}
        />
        <DetailRow
          label={lbl('LBL_AFFILIATE_COMMISSION', 'Affiliate commission')}
          value={formatMoney(data.ordles_affiliate_commission)}
        />
        <DetailRow label={lbl('LBL_TEACHER_PAID', 'Teacher paid')} value={yesNo(data.ordles_teacher_paid, lbl)} />
        <DetailRow
          label={lbl('LBL_REVIEWED_ON_LESSON', 'Reviewed on lesson')}
          value={yesNo(data.ordles_reviewed, lbl)}
        />
        <DetailRow
          label={lbl('LBL_ISSUE_REPORTED', 'Issue reported')}
          value={data.issue_reported ? lbl('LBL_YES', 'Yes') : lbl('LBL_NO', 'No')}
        />
        <DetailRow
          label={lbl('LBL_REFUND', 'Refund')}
          value={
            Number(data.ordles_refund ?? 0) > 0
              ? formatMoney(data.ordles_refund)
              : lbl('LBL_NA', 'N/A')
          }
        />
        <DetailRow
          label={lbl('LBL_DURATION', 'Duration')}
          value={
            Number(data.ordles_duration ?? 0) > 0
              ? `${data.ordles_duration} ${lbl('LBL_MINS', 'Mins')}`
              : lbl('LBL_NA', 'N/A')
          }
        />
        <DetailRow
          label={lbl('LBL_ORDER_ID', 'Order id')}
          value={
            orderId > 0 ? (
              <OrderIdLink orderId={orderId} orderIdFormatted={orderIdFormatted} lbl={lbl} />
            ) : (
              lbl('LBL_NA', 'N/A')
            )
          }
        />
        <DetailRow
          label={lbl('LBL_LESSON_ENDED_BY', 'Lesson ended by')}
          value={
            data.ordles_ended_by_label
              ? String(data.ordles_ended_by_label)
              : lbl('LBL_NA', 'N/A')
          }
        />
      </tbody>
    </table>
  );
}

function ClassDetail({ data, lbl }: { data: Record<string, unknown>; lbl: Props['lbl'] }) {
  const orderId = Number(data.order_id ?? 0);
  const orderIdFormatted = String(data.order_id_formatted ?? orderId);

  return (
    <table className="table table-coloum">
      <tbody>
        <DetailRow label={lbl('LBL_LEARNER_NAME', 'Learner name')} value={na(data.learner_name, lbl)} />
        <DetailRow label={lbl('LBL_TEACHER_NAME', 'Teacher name')} value={na(data.teacher_name, lbl)} />
        <DetailRow label={lbl('LBL_CLASS_NAME', 'Class name')} value={na(data.grpcls_title, lbl)} />
        <DetailRow label={lbl('LBL_LANGUAGE', 'Language')} value={na(data.grpcls_language_name, lbl)} />
        <DetailRow label={lbl('LBL_SERVICE_TYPE', 'Service type')} value={na(data.service_type_label, lbl)} />
        <DetailRow label={lbl('LBL_CLASS_STATUS', 'Class status')} value={na(data.ordcls_status_label, lbl)} />
        <DetailRow
          label={lbl('LBL_ORDER_PAYMENT_STATUS', 'Order payment status')}
          value={na(data.order_payment_status_label, lbl)}
        />
        <DetailRow label={lbl('LBL_START_TIME', 'Start time')} value={formatDate(data.grpcls_start_datetime)} />
        <DetailRow label={lbl('LBL_END_TIME', 'End time')} value={formatDate(data.grpcls_end_datetime)} />
        <DetailRow
          label={lbl('LBL_TEACHER_START_TIME', 'Teacher start time')}
          value={formatDate(data.ordcls_teacher_starttime)}
        />
        <DetailRow
          label={lbl('LBL_TEACHER_END_TIME', 'Teacher end time')}
          value={formatDate(data.ordcls_teacher_endtime)}
        />
        <DetailRow
          label={lbl('LBL_LEARNER_START_TIME', 'Learner start time')}
          value={formatDate(data.ordcls_format_starttime)}
        />
        <DetailRow
          label={lbl('LBL_LEARNER_END_TIME', 'Learner end time')}
          value={formatDate(data.ordcls_format_endtime)}
        />
        <DetailRow label={lbl('LBL_CLASS_PRICE', 'Class price')} value={formatMoney(data.ordcls_amount)} />
        <DetailRow
          wide
          label={lbl('LBL_ORDER_DISCOUNT_TOTAL', 'Order discount total')}
          value={formatMoney(data.ordcls_discount)}
        />
        <DetailRow
          wide
          label={lbl('LBL_REWARD_DISCOUNT', 'Reward discount')}
          value={formatMoney(data.ordcls_reward_discount)}
        />
        <DetailRow
          wide
          label={lbl('LBL_ORDER_NET_AMOUNT', 'Order net amount')}
          value={formatMoney(data.ordcls_net_amount)}
        />
        <DetailRow
          label={lbl('LBL_ADMIN_COMMISSION', 'Admin commission')}
          value={formatMoney(data.ordcls_commission_amount)}
        />
        <DetailRow
          label={lbl('LBL_AFFILIATE_COMMISSION', 'Affiliate commission')}
          value={formatMoney(data.ordcls_affiliate_commission)}
        />
        <DetailRow label={lbl('LBL_TEACHER_PAID', 'Teacher paid')} value={yesNo(data.ordcls_teacher_paid, lbl)} />
        <DetailRow
          label={lbl('LBL_REVIEWED_ON_CLASS', 'Reviewed on class')}
          value={yesNo(data.ordcls_reviewed, lbl)}
        />
        <DetailRow
          label={lbl('LBL_ISSUE_REPORTED', 'Issue reported')}
          value={data.issue_reported ? lbl('LBL_YES', 'Yes') : lbl('LBL_NO', 'No')}
        />
        <DetailRow
          label={lbl('LBL_REFUND', 'Refund')}
          value={
            Number(data.ordcls_refund ?? 0) > 0
              ? formatMoney(data.ordcls_refund)
              : lbl('LBL_NA', 'N/A')
          }
        />
        <DetailRow
          label={lbl('LBL_ORDER_ID', 'Order id')}
          value={
            orderId > 0 ? (
              <OrderIdLink orderId={orderId} orderIdFormatted={orderIdFormatted} lbl={lbl} />
            ) : (
              lbl('LBL_NA', 'N/A')
            )
          }
        />
        <DetailRow
          label={lbl('LBL_ENDED_BY', 'Ended by')}
          value={
            data.ordcls_ended_by_label
              ? String(data.ordcls_ended_by_label)
              : lbl('LBL_NA', 'N/A')
          }
        />
      </tbody>
    </table>
  );
}

function PackageDetail({ data, lbl }: { data: Record<string, unknown>; lbl: Props['lbl'] }) {
  const orderId = Number(data.order_id ?? 0);
  const orderIdFormatted = String(data.order_id_formatted ?? orderId);

  return (
    <table className="table table-coloum">
      <tbody>
        <DetailRow label={lbl('LBL_LEARNER_NAME', 'Learner name')} value={na(data.learner_name, lbl)} />
        <DetailRow label={lbl('LBL_TEACHER_NAME', 'Teacher name')} value={na(data.teacher_name, lbl)} />
        <DetailRow label={lbl('LBL_PACKAGE_NAME', 'Package name')} value={na(data.grpcls_title, lbl)} />
        <DetailRow label={lbl('LBL_LANGUAGE', 'Language')} value={na(data.grpcls_language_name, lbl)} />
        <DetailRow label={lbl('LBL_SERVICE_TYPE', 'Service type')} value={na(data.service_type_label, lbl)} />
        <DetailRow label={lbl('LBL_PACKAGE_STATUS', 'Package status')} value={na(data.ordpkg_status_label, lbl)} />
        <DetailRow
          label={lbl('LBL_ORDER_PAYMENT_STATUS', 'Order payment status')}
          value={na(data.order_payment_status_label, lbl)}
        />
        <DetailRow
          label={lbl('LBL_PACKAGE_START_TIME', 'Package start time')}
          value={formatDate(data.grpcls_start_datetime)}
        />
        <DetailRow
          label={lbl('LBL_PACKAGE_END_TIME', 'Package end time')}
          value={formatDate(data.grpcls_end_datetime)}
        />
        <DetailRow label={lbl('LBL_PACKAGE_PRICE', 'Package price')} value={formatMoney(data.ordpkg_amount)} />
        <DetailRow
          wide
          label={lbl('LBL_ORDER_DISCOUNT_TOTAL', 'Order discount total')}
          value={formatMoney(data.ordpkg_discount)}
        />
        <DetailRow
          wide
          label={lbl('LBL_REWARD_DISCOUNT', 'Reward discount')}
          value={formatMoney(data.ordpkg_reward_discount)}
        />
        <DetailRow
          wide
          label={lbl('LBL_ORDER_NET_AMOUNT', 'Order net amount')}
          value={formatMoney(data.order_net_amount)}
        />
        <DetailRow
          label={lbl('LBL_ORDER_ID', 'Order id')}
          value={
            orderId > 0 ? (
              <OrderIdLink orderId={orderId} orderIdFormatted={orderIdFormatted} lbl={lbl} />
            ) : (
              lbl('LBL_NA', 'N/A')
            )
          }
        />
        <DetailRow
          label={lbl('LBL_VIEW_CLASSES', 'View classes')}
          value={
            orderId > 0 ? (
              <Link
                to={`/admin/classes?order_id=${orderId}`}
                className="link-text link-underline"
                target="_blank"
              >
                {lbl('LBL_VIEW_CLASSES', 'View classes')}
              </Link>
            ) : (
              lbl('LBL_NA', 'N/A')
            )
          }
        />
      </tbody>
    </table>
  );
}

function GiftcardDetail({ data, lbl }: { data: Record<string, unknown>; lbl: Props['lbl'] }) {
  return (
    <table className="table table-coloum">
      <tbody>
        <DetailRow label={lbl('LBL_USER_NAME', 'User name')} value={na(data.user_full_name, lbl)} />
        <DetailRow
          label={lbl('LBL_PAYMENT_STATUS', 'Payment status')}
          value={na(data.order_payment_status_label, lbl)}
        />
        <DetailRow label={lbl('LBL_GIFTCARD_CODE', 'Giftcard code')} value={na(data.ordgift_code, lbl)} />
        <DetailRow label={lbl('LBL_RECIPIENT_NAME', 'Recipient name')} value={na(data.ordgift_receiver_name, lbl)} />
        <DetailRow
          label={lbl('LBL_RECIPIENT_EMAIL', 'Recipient email')}
          value={na(data.ordgift_receiver_email, lbl)}
        />
        <DetailRow label={lbl('LBL_GIFTCARD_STATUS', 'Giftcard status')} value={na(data.ordgift_status_label, lbl)} />
        <DetailRow label={lbl('LBL_AMOUNT', 'Amount')} value={formatMoney(data.order_total_amount)} />
      </tbody>
    </table>
  );
}

function CourseDetail({ data, lbl }: { data: Record<string, unknown>; lbl: Props['lbl'] }) {
  const orderId = Number(data.order_id ?? 0);
  const orderIdFormatted = String(data.order_id_formatted ?? orderId);

  return (
    <table className="table table-coloum">
      <tbody>
        <DetailRow label={lbl('LBL_LEARNER_NAME', 'Learner name')} value={na(data.learner_name, lbl)} />
        <DetailRow label={lbl('LBL_TEACHER_NAME', 'Teacher name')} value={na(data.teacher_name, lbl)} />
        <DetailRow label={lbl('LBL_TEACHER_EMAIL', 'Teacher email')} value={na(data.teacher_email, lbl)} />
        <DetailRow label={lbl('LBL_COURSE_TITLE', 'Course title')} value={na(data.course_title, lbl)} />
        <DetailRow label={lbl('LBL_LANGUAGE', 'Language')} value={na(data.clang_name, lbl)} />
        <DetailRow
          label={lbl('LBL_DURATION', 'Duration')}
          value={
            data.course_duration_label
              ? String(data.course_duration_label)
              : Number(data.course_duration ?? 0) > 0
                ? `${data.course_duration} ${lbl('LBL_MINS', 'Mins')}`
                : lbl('LBL_NA', 'N/A')
          }
        />
        <DetailRow label={lbl('LBL_STATUS', 'Status')} value={na(data.ordcrs_status_label, lbl)} />
        <DetailRow
          label={lbl('LBL_ORDER_PAYMENT_STATUS', 'Order payment status')}
          value={na(data.order_payment_status_label, lbl)}
        />
        <DetailRow
          label={lbl('LBL_PROGRESS', 'Progress')}
          value={`${Number(data.crspro_progress ?? 0)}%`}
        />
        <DetailRow label={lbl('LBL_AMOUNT', 'Amount')} value={formatMoney(data.ordcrs_amount)} />
        <DetailRow
          wide
          label={lbl('LBL_ORDER_DISCOUNT_TOTAL', 'Order discount total')}
          value={formatMoney(data.ordcrs_discount)}
        />
        <DetailRow
          wide
          label={lbl('LBL_REWARD_DISCOUNT', 'Reward discount')}
          value={formatMoney(data.order_reward_value)}
        />
        <DetailRow
          wide
          label={lbl('LBL_ORDER_NET_AMOUNT', 'Order net amount')}
          value={formatMoney(data.ordcrs_net_amount)}
        />
        <DetailRow
          label={lbl('LBL_ADMIN_COMMISSION', 'Admin commission')}
          value={formatMoney(data.ordcrs_commission_amount)}
        />
        <DetailRow
          label={lbl('LBL_AFFILIATE_COMMISSION', 'Affiliate commission')}
          value={formatMoney(data.ordcrs_affiliate_commission)}
        />
        <DetailRow label={lbl('LBL_TEACHER_PAID', 'Teacher paid')} value={yesNo(data.ordcrs_teacher_paid, lbl)} />
        <DetailRow
          label={lbl('LBL_REVIEWED_ON_COURSE', 'Reviewed on course')}
          value={yesNo(data.ordcrs_reviewed, lbl)}
        />
        <DetailRow
          label={lbl('LBL_ISSUE_REPORTED', 'Issue reported')}
          value={data.issue_reported ? lbl('LBL_YES', 'Yes') : lbl('LBL_NO', 'No')}
        />
        <DetailRow
          label={lbl('LBL_REFUND', 'Refund')}
          value={
            Number(data.ordcrs_refund ?? 0) > 0
              ? formatMoney(data.ordcrs_refund)
              : lbl('LBL_NA', 'N/A')
          }
        />
        <DetailRow
          label={lbl('LBL_ORDER_ID', 'Order id')}
          value={
            orderId > 0 ? (
              <OrderIdLink orderId={orderId} orderIdFormatted={orderIdFormatted} lbl={lbl} />
            ) : (
              lbl('LBL_NA', 'N/A')
            )
          }
        />
      </tbody>
    </table>
  );
}

function SubscriptionPlanDetail({ data, lbl }: { data: Record<string, unknown>; lbl: Props['lbl'] }) {
  const orderId = Number(data.order_id ?? 0);
  const orderIdFormatted = String(data.order_id_formatted ?? orderId);

  return (
    <table className="table table-coloum">
      <tbody>
        <DetailRow label={lbl('LBL_LEARNER_NAME', 'Learner name')} value={na(data.learner_name, lbl)} />
        <DetailRow label={lbl('LBL_STATUS', 'Status')} value={na(data.ordsplan_status_label, lbl)} />
        <DetailRow label={lbl('LBL_START_DATE', 'Start date')} value={formatDate(data.ordsplan_start_date)} />
        <DetailRow label={lbl('LBL_END_DATE', 'End date')} value={formatDate(data.ordsplan_end_date)} />
        <DetailRow label={lbl('LBL_PLAN_NAME', 'Plan name')} value={na(data.plan_name, lbl)} />
        <DetailRow
          label={lbl('LBL_Lesson_duration', 'Lesson duration')}
          value={Number(data.ordsplan_duration ?? 0) > 0 ? String(data.ordsplan_duration) : lbl('LBL_NA', 'N/A')}
        />
        <DetailRow
          label={lbl('LBL_LESSON_COUNT', 'Lesson count')}
          value={Number(data.ordsplan_lessons ?? 0) > 0 ? String(data.ordsplan_lessons) : lbl('LBL_NA', 'N/A')}
        />
        <DetailRow label={lbl('LBL_ORDER_NET_AMOUNT', 'Order net amount')} value={formatMoney(data.order_net_amount)} />
        <DetailRow
          label={lbl('LBL_ORDER_ID', 'Order id')}
          value={
            orderId > 0 ? (
              <OrderIdLink orderId={orderId} orderIdFormatted={orderIdFormatted} lbl={lbl} />
            ) : (
              lbl('LBL_NA', 'N/A')
            )
          }
        />
      </tbody>
    </table>
  );
}

export function AdminSubOrderViewModal({ module, row, onClose, lbl }: Props) {
  const [data, setData] = useState<Record<string, unknown> | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!module || !row) {
      setData(null);
      setError('');
      return;
    }

    const load = async () => {
      setLoading(true);
      setError('');
      try {
        let response;
        switch (module) {
          case 'lessons':
            response = await adminApi.lessonOrderShow(Number(row.ordles_id ?? row.id));
            break;
          case 'classes':
            response = await adminApi.classOrderShow(Number(row.ordcls_id ?? row.id));
            break;
          case 'packages':
            response = await adminApi.packageOrderShow(Number(row.ordpkg_id ?? row.id));
            break;
          case 'course-orders':
            response = await adminApi.courseOrderShow(Number(row.ordcrs_id ?? row.id));
            break;
          case 'giftcards':
            response = await adminApi.giftcardOrderShow(Number(row.order_id ?? row.id));
            break;
          case 'order-subscription-plans':
            response = await adminApi.orderSubscriptionPlanShow(Number(row.ordsplan_id ?? row.id));
            break;
          default:
            setData(null);
            return;
        }
        setData(response.data.data as Record<string, unknown>);
      } catch {
        setError(lbl('LBL_INVALID_REQUEST', 'Invalid request'));
        setData(null);
      } finally {
        setLoading(false);
      }
    };

    void load();
  }, [module, row, lbl]);

  if (!module || !row) {
    return null;
  }

  const title = (() => {
    switch (module) {
      case 'lessons':
        return lbl('LBL_VIEW_LESSON_DETAIL', 'View lesson detail');
      case 'classes':
        return lbl('LBL_CLASS_DETAIL', 'Class detail');
      case 'packages':
        return lbl('LBL_PACKAGE_DETAIL', 'Package detail');
      case 'course-orders':
        return lbl('LBL_VIEW_COURSE_DETAIL', 'View course detail');
      case 'giftcards':
        return lbl('LBL_VIEW_GIFT_CARD_DETAIL', 'View gift card detail');
      case 'order-subscription-plans':
        return lbl('LBL_VIEW_SUBSCRIPTION_PLAN_ORDER_DETAIL', 'View subscription plan order detail');
      default:
        return lbl('LBL_VIEW', 'View');
    }
  })();

  const renderBody = () => {
    if (!data) return null;
    switch (module) {
      case 'lessons':
        return <LessonDetail data={data} lbl={lbl} />;
      case 'classes':
        return <ClassDetail data={data} lbl={lbl} />;
      case 'packages':
        return <PackageDetail data={data} lbl={lbl} />;
      case 'course-orders':
        return <CourseDetail data={data} lbl={lbl} />;
      case 'giftcards':
        return <GiftcardDetail data={data} lbl={lbl} />;
      case 'order-subscription-plans':
        return <SubscriptionPlanDetail data={data} lbl={lbl} />;
      default:
        return null;
    }
  };

  return (
    <AdminModal open title={title} size="lg" onClose={onClose}>
      {loading ? (
        <div className="table-processing loaderJs p-5">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : null}
      {error ? <p className="p-4 text-danger">{error}</p> : null}
      {!loading && !error && data ? <div className="form-edit-body p-0">{renderBody()}</div> : null}
    </AdminModal>
  );
}
