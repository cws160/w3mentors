import { Link } from 'react-router-dom';
import type { TeacherListing } from '../../api/client';
import { AFILE, firstChar, formatMoney, imageUrl } from '../utils/assets';
import { SpriteIcon } from './SpriteIcon';

type Props = {
  teacher: TeacherListing;
  lbl: (key: string, fallback?: string) => string;
  isCourseEnabled?: boolean;
};

export function W3MentorsTeacherCard({ teacher, lbl, isCourseEnabled = true }: Props) {
  const profileUrl = teacher.username ? `/teachers/${teacher.username}` : `/teachers/${teacher.id}`;
  const totalSessions = teacher.lessons + teacher.classes;

  return (
    <div className="profile-card">
      <div className="profile-card__body">
        <div className="profile-card__media">
          <div className="avtar avtar--centered" data-title={firstChar(teacher.first_name ?? teacher.full_name)}>
            <Link to={profileUrl}>
              <img
                src={imageUrl(AFILE.USER_PROFILE, teacher.id, 'MEDIUM')}
                alt={teacher.full_name}
                loading="lazy"
              />
            </Link>
          </div>
        </div>
        <div className="profile-card__content">
          <div className="profile-detail">
            <div className="profile-info mb-2">
              <Link to={profileUrl} className="tutor-name">
                <h4>{teacher.full_name}</h4>
              </Link>
              {teacher.is_featured && (
                <div className="badge-secure is-hover">
                  <svg className="icon icon--small icon--featured" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="12" height="12">
                    <path
                      fillRule="evenodd"
                      d="M15.291 4.055 12 2 8.709 4.055l-3.78.874-.874 3.78L2 12l2.055 3.291.874 3.78 3.78.874L12 22l3.291-2.055 3.78-.874.874-3.78L22 12l-2.055-3.291-.874-3.78zM9.793 15.707l.707.707.707-.707 6-6-1.414-1.414-5.293 5.293-2.293-2.293-1.414 1.414z"
                      clipRule="evenodd"
                    />
                  </svg>
                </div>
              )}
              <div className="info-tag ratings">
                <SpriteIcon id="rating" className="icon icon--rating" />
                <span className="value">{teacher.ratings}</span>
                <span className="count">({teacher.reviews})</span>
              </div>
            </div>
            <div className="info-wrapper mb-3">
              <div className="meta-info">
                <span className="value">{teacher.students}</span> {lbl('LBL_Students', 'Students')}
              </div>
              <div className="meta-info">
                <span className="value">{totalSessions}</span> {lbl('LBL_Sessions', 'Sessions')}
              </div>
              {isCourseEnabled && (
                <div className="meta-info">
                  <span className="value">{teacher.courses}</span> {lbl('LBL_COURSES', 'Courses')}
                </div>
              )}
            </div>
            <div className="profile-card-price mb-3">
              {formatMoney(teacher.min_price)} - {formatMoney(teacher.max_price)}
            </div>
            <div className="info-group">
              <h6>{lbl('LBL_Teaches', 'Teaches')}:</h6>
              <span>{teacher.teach_languages}</span>
            </div>
            <div className="info-group">
              <h6>{lbl('LBL_Speaks', 'Speaks')}:</h6>
              <span>{teacher.speak_languages}</span>
            </div>
          </div>
          {teacher.biography && (
            <div className="profile-detail">
              <div className="info-group">
                <div className="info-group__head">
                  <h5>{lbl('LBL_About', 'About')}</h5>
                </div>
                <div className="info-group__body">
                  <p>{teacher.biography.slice(0, 220)}</p>
                  <Link className="txt-link" to={profileUrl}>
                    {lbl('LBL_View_Profile', 'View profile')}
                  </Link>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
      <div className="profile-card__foot">
        <div className="profile-card__actions">
          <div className="action-buttons">
            <Link to={profileUrl} className="btn btn--primary">
              {lbl('LBL_Book_Now', 'Book now')}
            </Link>
            <Link to={profileUrl} className="btn btn--bordered color-primary">
              {lbl('LBL_Contact', 'Contact')}
            </Link>
          </div>
          <Link to={profileUrl} className="txt-link color-primary">
            {lbl('LBL_View_availability', 'View availability')}
          </Link>
        </div>
      </div>
    </div>
  );
}
