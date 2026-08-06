import { Link } from 'react-router-dom';
import { formatDuration, type Course } from '../../api/client';
import { AFILE, firstChar, formatMoney, imageUrl } from '../utils/assets';
import { SpriteIcon } from './SpriteIcon';

type Props = {
  course: Partial<Course> & Pick<Course, 'id' | 'title' | 'price' | 'ratings'>;
  lbl: (key: string, fallback?: string) => string;
};

export function ShortCourseCard({ course, lbl }: Props) {
  return (
    <div className="slider__item">
      <div className="card-cover">
        <div className="short-card">
          <div className="short-card__head">
            <div className="short-card__media ratio ratio--16by9">
              <Link to={`/courses/${course.slug ?? course.id}`}>
                <img
                  src={imageUrl(AFILE.COURSE_IMAGE, course.id, 'MEDIUM')}
                  alt={course.title ?? ''}
                  loading="lazy"
                />
              </Link>
            </div>
          </div>
          <div className="short-card__body">
            <div className="rating">
              <SpriteIcon id="rating" className="rating__media" />
              <span className="rating__value">{course.ratings}</span>
              <span className="rating__count">({course.reviews ?? 0})</span>
            </div>
            <h6 className="short-card__title">
              <Link to={`/courses/${course.slug ?? course.id}`}>{course.title}</Link>
            </h6>
            <div className="card-element">
              <span className="card-element__item">
                <svg width="16" height="16">
                  <use xlinkHref="/images/sprite.svg#icon-clock" />
                </svg>
                {formatDuration(course.duration ?? 0)}
              </span>
              <span className="card-element__item">
                <svg width="20" height="14">
                  <use xlinkHref="/images/sprite.svg#icon-lectures" />
                </svg>
                {course.lectures ?? 0} {lbl('LBL_LECTURES', 'Lectures')}
              </span>
            </div>
            {course.is_free ? (
              <h4 className="bold-700 price-value color-red">{lbl('LBL_FREE', 'Free')}</h4>
            ) : (
              <h4 className="bold-700 price-value">{formatMoney(course.price)}</h4>
            )}
          </div>
          {course.teacher && (
            <div className="short-card__footer">
              <Link
                to={course.teacher.username ? `/teachers/${course.teacher.username}` : '#'}
                className="profile-meta d-flex align-items-center"
              >
                <div className="profile-meta__media">
                  <span
                    className="avtar avtar--medium avtar--round"
                    data-title={firstChar(course.teacher.first_name ?? course.teacher.full_name)}
                  >
                    <img
                      src={imageUrl(AFILE.USER_PROFILE, course.teacher.id, 'SMALL')}
                      alt=""
                      loading="lazy"
                    />
                  </span>
                </div>
                <div className="profile-meta__details">
                  <h6 className="profile-meta__title">{course.teacher.full_name}</h6>
                </div>
              </Link>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
