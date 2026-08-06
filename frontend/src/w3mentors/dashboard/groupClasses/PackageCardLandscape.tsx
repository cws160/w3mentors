import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useDashboardMoney } from '../hooks/useDashboardMoney';
import { formatMoney } from '../../utils/assets';
import { dashboardPath } from '../dashboardPaths';
import { groupClassStatusLabel } from './groupClassStatus';

export type PackageCardItem = {
  id: number;
  grpcls_id?: number;
  title: string;
  start_time: string | null;
  offline: boolean;
  status: number;
  status_label?: string;
  entry_fee?: number;
  booked_seats?: number;
  total_seats?: number;
  can_edit?: boolean;
  can_cancel?: boolean;
};

type Props = {
  item: PackageCardItem;
  lbl: (key: string, fallback: string) => string;
};

function formatLegacyTime(iso: string): string {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

function formatLegacyDate(iso: string): string {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  return d.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
}

/** Legacy dashboard/views/packages/search.php */
export function PackageCardLandscape({ item, lbl }: Props) {
  const moneySymbol = useDashboardMoney();
  const grpclsId = item.grpcls_id ?? item.id;
  const offlineClass = item.offline ? 'bg-yellow' : 'bg-info';
  const offlineTooltip = item.offline
    ? lbl('LBL_IN-PERSON_SESSION', 'In-person session')
    : lbl('LBL_ONLINE_SESSION', 'Online session');

  return (
    <div className="lessons-group mt-5">
      <span className="date uppercase small bold-600">&nbsp;</span>
      <div className="card-landscape">
        <div className="card-landscape__colum card-landscape__colum--first">
          {item.start_time && (
            <div className="card-landscape__head">
              <time className="card-landscape__time">{formatLegacyTime(item.start_time)}</time>
              <time className="card-landscape__date">{formatLegacyDate(item.start_time)}</time>
            </div>
          )}
        </div>
        <div className="card-landscape__colum card-landscape__colum--second">
          <div className="gcard-landscape__body">
            <div className="detal-list">
              <div className="detal-list__item">
                <span className="card-landscape__title">
                  <span
                    className={`badge--round box-hint list-inline-item m-0 -no-border ${offlineClass}`}
                    title={offlineTooltip}
                  >
                    &nbsp;
                  </span>
                  {item.title}
                </span>
              </div>
              <div className="detal-list__item">
                <span className="card-landscape__status badge color-secondary badge--curve badge--small ms-0">
                  {item.status_label ?? groupClassStatusLabel(item.status, lbl)}
                </span>
                <span className="card-landscape__status badge color-primary badge--curve badge--small ms-0">
                  {lbl('LBL_ENTRY_FEE', 'Entry fee')}: {formatMoney(item.entry_fee ?? 0, moneySymbol)}
                </span>
                <span className="card-landscape__status badge color-primary badge--curve badge--small ms-0">
                  {lbl('LBL_BOOKED_SEATS', 'Booked seats')}: {item.booked_seats ?? 0}/
                  {item.total_seats ?? 0}
                </span>
              </div>
            </div>
          </div>
        </div>
        <div className="card-landscape__colum card-landscape__colum--third">
          <div className="card-landscape__actions">
            <div className="profile-meta" />
            <div className="actions-group">
              <a
                href={`${dashboardPath('teacher', 'classes')}?package_id=${grpclsId}`}
                className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
              >
                <DashboardSpriteIcon id="view" className="icon icon--enter icon--18" />
                <div className="tooltip tooltip--top bg-black">
                  {lbl('LBL_VIEW_CLASSES', 'View classes')}
                </div>
              </a>
              {item.can_edit && (
                <button
                  type="button"
                  className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                  disabled
                  title={lbl('LBL_EDIT', 'Edit')}
                >
                  <DashboardSpriteIcon id="edit" className="icon icon--edit icon--small" />
                  <div className="tooltip tooltip--top bg-black">{lbl('LBL_EDIT', 'Edit')}</div>
                </button>
              )}
              {item.can_cancel && (
                <button
                  type="button"
                  className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                  disabled
                  title={lbl('LBL_CANCEL', 'Cancel')}
                >
                  <DashboardSpriteIcon id="cancel" className="icon icon--cancel icon--small" />
                  <div className="tooltip tooltip--top bg-black">{lbl('LBL_CANCEL', 'Cancel')}</div>
                </button>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
