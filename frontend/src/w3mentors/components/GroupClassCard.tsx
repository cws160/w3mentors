import { Link } from 'react-router-dom';
import type { GroupClassItem } from '../../api/client';
import { AFILE, firstChar, formatMoney, imageUrl } from '../utils/assets';
import { SpriteIcon } from './SpriteIcon';

type Props = {
  item: GroupClassItem;
  lbl: (key: string, fallback?: string) => string;
};

function formatDate(iso: string): string {
  try {
    return new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
  } catch {
    return iso;
  }
}

function formatTime(iso: string): string {
  try {
    return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
  } catch {
    return '';
  }
}

export function GroupClassCard({ item, lbl }: Props) {
  const teacherUrl = item.teacher_username
    ? `/teachers/${item.teacher_username}`
    : `/teachers/${item.teacher_id}`;

  return (
    <div className="col-sm-6 col-lg-4">
      <div className="card-class">
        <div className="card-class__head">
          <div className="card-class__media ratio ratio--16by9">
            <Link to={`/group-classes/${item.slug}`}>
              <img
                src={imageUrl(AFILE.GROUP_CLASS_BANNER, item.id, 'MEDIUM')}
                alt={item.title}
                loading="lazy"
              />
            </Link>
          </div>
          <div className="card-class__action">
            <button type="button" className="btn btn--white" disabled>
              {lbl('LBL_BOOK_NOW', 'Book now')}
            </button>
          </div>
        </div>
        <div className="card-class__body">
          <div className="card-flex-group">
            <div className="card-flex-group__item">
              <span className="card-class__date">{formatDate(item.start_at)}</span>
            </div>
          </div>
          <div className="card-class__title">
            <Link to={`/group-classes/${item.slug}`}>{item.title}</Link>
          </div>
          <div className="card-element">
            <div className="card-element__item">
              <span>
                {formatTime(item.start_at)} ({item.duration} {lbl('LBL_Minutes', 'Minutes')})
              </span>
            </div>
            <div className="card-element__item">
              <span>
                {item.total_seats} {lbl('LBL_SEATS', 'Seats')}
              </span>
            </div>
          </div>
          <h4 className="bold-700 price-value">{formatMoney(item.entry_fee)}</h4>
        </div>
        <div className="card-class__footer">
          <Link to={teacherUrl} className="profile-meta d-flex align-items-center">
            <div className="profile-meta__media">
              <span className="avtar avtar--medium avtar--round" data-title={firstChar(item.teacher_name)}>
                <img src={imageUrl(AFILE.USER_PROFILE, item.teacher_id, 'SMALL')} alt={item.teacher_name} />
              </span>
            </div>
            <div className="profile-meta__details">
              <h6 className="bold-600 profile-meta__title">{item.teacher_name}</h6>
              <div className="rating">
                <SpriteIcon id="rating" className="rating__media" />
                <span className="rating__value">{item.teacher_ratings ?? 0}</span>
                <span className="rating__count">({item.teacher_reviews ?? 0})</span>
              </div>
            </div>
          </Link>
        </div>
      </div>
    </div>
  );
}
