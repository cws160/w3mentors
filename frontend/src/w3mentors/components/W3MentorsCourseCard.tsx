import { Link } from 'react-router-dom';
import { formatDuration, type Course } from '../../api/client';
import { AFILE, firstChar, formatMoney, imageUrl } from '../utils/assets';
import { SpriteIcon } from './SpriteIcon';

type Props = {
  course: Course;
  lbl: (key: string, fallback?: string) => string;
  levelLabel?: string;
};

export function W3MentorsCourseCard({ course, lbl, levelLabel }: Props) {
  const teacher = course.teacher;

  return (
    <div className="course-card">
      <div className="course-grid">
        <div className="course-grid__head">
          <div className="course-media ratio ratio--16by9">
            <img
              src={imageUrl(AFILE.COURSE_IMAGE, course.id, 'LARGE')}
              alt={course.title ?? ''}
              loading="lazy"
            />
            {course.preview_video && (
              <a href="#preview" className="course-preview__action" onClick={(e) => e.preventDefault()}>
                <span />
              </a>
            )}
          </div>
          <button type="button" className="mark-option" data-status="0">
            <svg className="fav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25.32 25.32">
              <g>
                <path
                  className="cls-1"
                  d="M17.16,3.41c3.04,0,5.5,2.5,5.5,6,0,7-7.5,11-10,12.5-2.5-1.5-10-5.5-10-12.5,0-3.5,2.5-6,5.5-6,1.86,0,3.5,1,4.5,2,1-1,2.64-2,4.5-2Z"
                />
              </g>
            </svg>
          </button>
        </div>
        <div className="course-grid__body">
          {course.certificate && (
            <span className="course-tag">
              <SpriteIcon id="icon-course-certificate" className="icon icon--award icon--small" />
              <span>{lbl('LBL_CERTIFICATE_ON_COMPLETION', 'Certificate on completion')}</span>
            </span>
          )}
          <div className="rating mb-2">
            <SpriteIcon id="rating" className="rating__media" />
            <span className="rating__value">{course.ratings}</span>
            <span className="rating__count">({course.reviews ?? 0})</span>
          </div>
          <h4 className="course-card__title mb-2">
            <Link to={`/courses/${course.slug ?? course.id}`} className="snakeline-hover">
              {course.title}
            </Link>
          </h4>
          <div className="course-stats mb-3">
            {levelLabel && (
              <div className="course-stats__item">
                <span>
                  {lbl('LBL_LEVEL:', 'Level:')} <strong>{levelLabel}</strong>
                </span>
              </div>
            )}
            <div className="course-stats__item">
              <span>
                {lbl('LBL_LECTURES', 'Lectures')}: <strong>{course.lectures}</strong>
              </span>
            </div>
            <div className="course-stats__item">
              <span>
                {lbl('LBL_TIME', 'Time')}: <strong>{formatDuration(course.duration)}</strong>
              </span>
            </div>
            <div className="course-stats__item">
              <span>
                {lbl('LBL_Students', 'Students')}: <strong>{course.students}</strong>
              </span>
            </div>
          </div>
          {course.is_free ? (
            <h4 className="bold-700 color-red">{lbl('LBL_FREE', 'Free')}</h4>
          ) : (
            <h4 className="bold-700">{formatMoney(course.price)}</h4>
          )}
          <div className="course-actions border-top pt-4 mt-4">
            <div className="course-actions__grid course-actions__grid-left">
              {teacher && (
                <Link
                  to={teacher.username ? `/teachers/${teacher.username}` : '#'}
                  className="profile-meta d-flex align-items-center gap-3"
                >
                  <div className="profile-meta__media">
                    <span
                      className="avtar avtar--medium avtar--round"
                      data-title={firstChar(teacher.first_name ?? teacher.full_name)}
                    >
                      <img
                        src={imageUrl(AFILE.USER_PROFILE, teacher.id, 'MEDIUM')}
                        alt={teacher.full_name}
                        loading="lazy"
                      />
                    </span>
                  </div>
                  <div className="profile-meta__details">
                    <span className="color-black">{teacher.full_name}</span>
                  </div>
                </Link>
              )}
            </div>
            <div className="course-actions__grid course-actions__grid-right">
              <div className="course-controls">
                <div className="course-controls__item">
                  <Link to={`/courses/${course.id}`} className="btn btn--small btn--primary-bordered">
                    {lbl('LBL_View_Course', 'View course')}
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
