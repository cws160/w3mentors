import { useCallback, useEffect, useState } from 'react';
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom';
import { bindCourseDetailPage } from '../lib/w3mentors-ui';
import {
  coursesApi,
  formatDuration,
  type Course,
  type CourseReview,
  type CourseReviewStat,
  type CourseTeacher,
  type CurriculumSection,
  type EnrollmentInfo,
  type IntendedLearners,
} from '../../api/client';
import { useAuth } from '../../context/AuthContext';
import { useSite } from '../context/SiteContext';
import { W3MentorsPageMessage } from '../components/W3MentorsPageMessage';
import { SpriteIcon } from '../components/SpriteIcon';
import { AFILE, firstChar, formatMoney, imageUrl } from '../utils/assets';
import { normalizeLegacyHtml } from '../utils/legacyHtml';

type CourseDetail = Course;

function previewVideoSrc(course: CourseDetail): string | null {
  const ref = course.preview_video?.trim();
  if (!ref) return null;
  if (/^https?:\/\//i.test(ref)) return ref;
  if (/^\d+$/.test(ref)) {
    return `/image/show-by-id/${ref}/LARGE`;
  }
  return imageUrl(AFILE.COURSE_PREVIEW_VIDEO, course.id, 'LARGE');
}

function shareUrl(): string {
  return typeof window !== 'undefined' ? window.location.href : '';
}

export function W3MentorsCourseDetailPage() {
  const { slugOrId } = useParams();
  const location = useLocation();
  const navigate = useNavigate();
  const { user } = useAuth();
  const { lbl } = useSite();
  const [course, setCourse] = useState<CourseDetail | null>(null);
  const [enrollment, setEnrollment] = useState<EnrollmentInfo | null>(null);
  const [curriculum, setCurriculum] = useState<CurriculumSection[]>([]);
  const [intended, setIntended] = useState<IntendedLearners | null>(null);
  const [reviews, setReviews] = useState<CourseReview[]>([]);
  const [reviewsPage, setReviewsPage] = useState(1);
  const [reviewsLastPage, setReviewsLastPage] = useState(1);
  const [reviewSort, setReviewSort] = useState<'DESC' | 'ASC'>('DESC');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [enrolling, setEnrolling] = useState(false);
  const [enrollError, setEnrollError] = useState('');
  const [showPreview, setShowPreview] = useState(false);
  const [copiedShare, setCopiedShare] = useState(false);

  const loadReviews = useCallback(
    (courseId: number, page: number, sort: 'DESC' | 'ASC', append: boolean) => {
      coursesApi.reviews(courseId, { page, sort }).then((res) => {
        setReviews((prev) => (append ? [...prev, ...res.data.data] : res.data.data));
        setReviewsPage(res.data.meta.current_page);
        setReviewsLastPage(res.data.meta.last_page);
      });
    },
    []
  );

  useEffect(() => {
    if (!slugOrId) return;
    setLoading(true);
    setError('');

    coursesApi
      .get(slugOrId)
      .then((courseRes) => {
        const c = courseRes.data.data as CourseDetail;
        setCourse(c);
        setEnrollment(courseRes.data.enrollment);
        if ((c.reviews ?? 0) > 0) {
          loadReviews(c.id, 1, 'DESC', false);
        }
        return Promise.all([
          coursesApi.curriculum(c.id),
          coursesApi.intendedLearners(c.id),
        ]);
      })
      .then(([curriculumRes, intendedRes]) => {
        setCurriculum(curriculumRes.data.data);
        setIntended(intendedRes.data.data);
      })
      .catch(() =>
        setError(lbl('LBL_Something_went_wrong', 'Course not found or API unavailable.'))
      )
      .finally(() => setLoading(false));
  }, [slugOrId, user?.id, lbl, loadReviews]);

  useEffect(() => {
    if (!course) return;
    return bindCourseDetailPage();
  }, [course?.id]);

  const handleEnroll = async () => {
    if (!course) return;
    if (!user) {
      navigate('/login', { state: { from: location.pathname } });
      return;
    }
    setEnrolling(true);
    setEnrollError('');
    try {
      const res = await coursesApi.enroll(course.id);
      if (res.data.payment_required) {
        setEnrollError(
          res.data.message ??
            lbl('LBL_PAYMENT_METHOD_NOT_AVAILABLE', 'Checkout is not available in the app yet.')
        );
        return;
      }
      if (res.data.enrolled && res.data.enrollment) {
        setEnrollment(res.data.enrollment);
        navigate(`/my/courses/${course.id}`);
      }
    } catch {
      setEnrollError(lbl('LBL_Something_went_wrong', 'Could not enroll. Please try again.'));
    } finally {
      setEnrolling(false);
    }
  };

  const handleCopyShare = async () => {
    try {
      await navigator.clipboard.writeText(shareUrl());
      setCopiedShare(true);
      setTimeout(() => setCopiedShare(false), 2000);
    } catch {
      /* ignore */
    }
  };

  if (loading) {
    return <W3MentorsPageMessage message={lbl('LBL_Loading', 'Loading...')} />;
  }
  if (error || !course) {
    return <W3MentorsPageMessage message={error || lbl('LBL_NOT_FOUND', 'Not found')} error />;
  }

  const teacher = course.teacher as CourseTeacher | undefined;
  const teacherUrl =
    teacher?.username && teacher.profile_complete !== false
      ? `/teachers/${teacher.username}`
      : teacher?.id
        ? `/teachers/${teacher.id}`
        : '#';
  const reviewStats = (course.review_stats ?? []) as CourseReviewStat[];
  const videoSrc = previewVideoSrc(course);
  const catLink = course.category_id ? `/courses?catg=${course.category_id}` : '/courses';
  const subCatLink =
    course.subcategory_id && course.subcategory_id > 0
      ? `/courses?catg=${course.subcategory_id}`
      : null;

  return (
    <>
      {showPreview && videoSrc && (
        <div
          className="modal fade show"
          style={{ display: 'block', background: 'rgba(0,0,0,0.6)' }}
          role="dialog"
          onClick={() => setShowPreview(false)}
        >
          <div
            className="modal-dialog modal-dialog-centered modal-lg"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title">{course.title}</h5>
                <button type="button" className="btn-close" onClick={() => setShowPreview(false)} />
              </div>
              <div className="modal-body p-0">
                {/\.(mp4|webm|ogg)(\?|$)/i.test(videoSrc) || videoSrc.includes('/image/') ? (
                  <video src={videoSrc} controls autoPlay className="w-100" />
                ) : (
                  <div className="ratio ratio--16by9">
                    <iframe src={videoSrc} title={course.title ?? 'Preview'} allowFullScreen />
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      <section className="section bg-gradiant section--page-header">
        <div className="container container--narrow">
          <div className="breadcrumbs mb-4 p-sm-0 px-2">
            <ul>
              <li>
                <Link to="/">{lbl('LBL_Home', 'Home')}</Link>
              </li>
              <li>
                <Link to="/courses">{lbl('LBL_Courses', 'Courses')}</Link>
              </li>
              <li>{course.title}</li>
            </ul>
          </div>
          <div className="details-view p-sm-0 px-2">
            <div className="details-view__media">
              <div className="course-preview">
                <div className="course-preview__media ratio ratio--16by9">
                  <img
                    src={imageUrl(AFILE.COURSE_IMAGE, course.id, 'LARGE')}
                    alt={course.title ?? ''}
                  />
                </div>
                {videoSrc && (
                  <button
                    type="button"
                    className="course-preview__action border-0 bg-transparent p-0"
                    onClick={() => setShowPreview(true)}
                    aria-label={lbl('LBL_PREVIEW', 'Preview')}
                  >
                    <span />
                  </button>
                )}
              </div>
            </div>
            <div className="details-view__content">
              <hgroup>
                <div className="rating mb-3">
                  <SpriteIcon id="rating" className="rating__media" />
                  <span className="rating__value">{course.ratings}</span>
                  <span className="rating__count">({course.reviews ?? 0})</span>
                </div>
                <span className="course-card__label mb-3">
                  {course.category_name && <Link to={catLink}>{course.category_name}</Link>}
                  {course.subcategory_name && (
                    <>
                      {' / '}
                      {subCatLink ? (
                        <Link to={subCatLink}>{course.subcategory_name}</Link>
                      ) : (
                        <span>{course.subcategory_name}</span>
                      )}
                    </>
                  )}
                </span>
                <h1 className="page-heading">{course.title}</h1>
                {course.subtitle && <h4 className="page-subheading mt-3">{course.subtitle}</h4>}
              </hgroup>
              <div className="course-counts pt-4">
                {teacher && (
                  <div className="course-counts__item">
                    <Link
                      to={teacherUrl}
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
                          />
                        </span>
                      </div>
                      <div className="profile-meta__details">
                        <span className="color-black bold-600">{teacher.full_name}</span>
                      </div>
                    </Link>
                  </div>
                )}
                <div className="course-counts__item">
                  <div className="course-info">
                    <div className="course-info__media">
                      <SpriteIcon id="icon-cap" className="icon icon--18" />
                    </div>
                    <div className="course-info__title">
                      <strong>{course.students}</strong>{' '}
                      {lbl('LBL_STUDENTS_ENROLLED', 'Students enrolled')}
                    </div>
                  </div>
                </div>
                {course.level_name && (
                  <div className="course-counts__item">
                    <div className="course-info">
                      <div className="course-info__media">
                        <SpriteIcon id="icon-expert" className="icon icon--18" />
                      </div>
                      <div className="course-info__title">{course.level_name}</div>
                    </div>
                  </div>
                )}
                {course.language_name && (
                  <div className="course-counts__item" title="Course language">
                    <div className="course-info">
                      <div className="course-info__media">
                        <SpriteIcon id="icon-globe" className="icon icon--18" />
                      </div>
                      <div className="course-info__title">{course.language_name}</div>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container container--narrow">
          <div className="page-flex">
            <div className="page-flex__small">
              <div className="page-flex__sticky scrolling" id="STICKY">
                <div className="page-box">
                  <div className="page-box__head">
                    <h5>{lbl('LBL_THIS_COURSE_INCLUDES:', 'This course includes:')}</h5>
                  </div>
                  <div className="page-box__body">
                    <div className="course-options">
                      <ul>
                        {course.duration > 0 && (
                          <li className="course-options__item">
                            <span className="course-options__item-media">
                              <SpriteIcon id="icon-course-video" className="icon icon--small" />
                            </span>
                            <span className="course-options__item-label">
                              {formatDuration(course.duration)}
                            </span>
                          </li>
                        )}
                        <li className="course-options__item">
                          <span className="course-options__item-media">
                            <SpriteIcon id="icon-course-lecture" className="icon icon--small" />
                          </span>
                          <span className="course-options__item-label">
                            <strong>{course.lectures}</strong> {lbl('LBL_LECTURES', 'Lectures')}
                          </span>
                        </li>
                        {(course.resources_count ?? 0) > 0 && (
                          <li className="course-options__item">
                            <span className="course-options__item-media">
                              <SpriteIcon id="icon-course-assets" className="icon icon--small" />
                            </span>
                            <span className="course-options__item-label">
                              <strong>{course.resources_count}</strong>{' '}
                              {lbl('LBL_DOWNLOADABLE_ASSETS', 'Downloadable assets')}
                            </span>
                          </li>
                        )}
                        <li className="course-options__item">
                          <span className="course-options__item-media">
                            <SpriteIcon id="icon-course-access" className="icon icon--small" />
                          </span>
                          <span className="course-options__item-label">
                            {lbl('LBL_FULL_LIFETIME_ACCESS', 'Full lifetime access')}
                          </span>
                        </li>
                        <li className="course-options__item">
                          <span className="course-options__item-media">
                            <SpriteIcon id="icon-course-tv" className="icon icon--small" />
                          </span>
                          <span className="course-options__item-label">
                            {lbl('LBL__ACCESS_ON_MOBILE_AND_TV', 'Access on mobile and TV')}
                          </span>
                        </li>
                        {course.certificate && (
                          <li className="course-options__item">
                            <span className="course-options__item-media">
                              <SpriteIcon id="icon-course-certificate" className="icon icon--small" />
                            </span>
                            <span className="course-options__item-label">
                              {lbl('LBL_CERTIFICATE_ON_COMPLETION', 'Certificate on completion')}
                            </span>
                          </li>
                        )}
                        {course.has_quiz && (
                          <li className="course-options__item">
                            <span className="course-options__item-media">
                              <SpriteIcon id="icon-course-lecture" className="icon icon--small" />
                            </span>
                            <span className="course-options__item-label">
                              {lbl('LBL_QUIZ_FOR_EVALUATION', 'Quiz for evaluation')}
                            </span>
                          </li>
                        )}
                      </ul>
                    </div>
                  </div>
                  <div className="page-box__footer">
                    <div className="course-pricing mb-3">
                      <div className="course-pricing__head text-center mb-3">
                        {course.is_free ? (
                          <h3 className="free-text color-red">{lbl('LBL_FREE', 'Free')}</h3>
                        ) : (
                          <span className="course-pricing__price">{formatMoney(course.price)}</span>
                        )}
                      </div>
                      <div className="course-pricing__body">
                        {enrollError && (
                          <p className="color-secondary small mb-2 text-center">{enrollError}</p>
                        )}
                        {enrollment?.is_enrolled ? (
                          <Link
                            to={`/my/courses/${course.id}`}
                            className="btn btn--block btn--primary btn--large"
                          >
                            {lbl('LBL_GO_TO_COURSE', 'Go to course')}
                          </Link>
                        ) : (
                          <button
                            type="button"
                            className="btn btn--block btn--primary btn--large"
                            disabled={enrolling}
                            onClick={handleEnroll}
                          >
                            {enrolling
                              ? lbl('LBL_PLEASE_WAIT', 'Please wait...')
                              : lbl('LBL_ENROLL_NOW', 'Enroll now')}
                          </button>
                        )}
                      </div>
                    </div>
                    <button
                      type="button"
                      className="btn btn--primary-bordered btn--favorite btn--block"
                      onClick={() => undefined}
                    >
                      <SpriteIcon id="icon-heart" className="icon icon--heart fav-icon" />
                      {lbl('LBL_FAVORITE', 'Favorite')}
                    </button>
                    <div className="sharing-view align-center mt-4 p-0 m-0">
                      <h6>{lbl('LBL_SHARE_THIS_COURSE', 'Share this course')}</h6>
                      <ul className="social--share pt-3">
                        <li className="social--fb">
                          <a
                            href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl())}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            title={lbl('LBL_FACEBOOK', 'Facebook')}
                          >
                            <img src="/images/social_01.svg" alt={lbl('LBL_FACEBOOK', 'Facebook')} />
                          </a>
                        </li>
                        <li className="social--tw">
                          <a
                            href={`https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl())}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            title={lbl('LBL_X', 'X')}
                          >
                            <img src="/images/social_02.svg" alt={lbl('LBL_X', 'X')} />
                          </a>
                        </li>
                        <li className="social--pt">
                          <a
                            href={`https://pinterest.com/pin/create/button/?url=${encodeURIComponent(shareUrl())}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            title={lbl('LBL_PINTEREST', 'Pinterest')}
                          >
                            <img src="/images/social_05.svg" alt={lbl('LBL_PINTEREST', 'Pinterest')} />
                          </a>
                        </li>
                        <li className="social--mail">
                          <a
                            href="javascript:void(0)"
                            onClick={(e) => {
                              e.preventDefault();
                              void handleCopyShare();
                            }}
                            title={lbl('LBL_EMAIL', 'Email')}
                          >
                            <img src="/images/social_06.svg" alt={lbl('LBL_EMAIL', 'Email')} />
                          </a>
                        </li>
                      </ul>
                      {copiedShare && (
                        <p className="small color-primary mt-2">
                          {lbl('LBL_LINK_COPIED', 'Link copied')}
                        </p>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div className="page-flex__large">
              <nav className="page-nav tabs page-nav-js" id="TAB-STICKY">
                <ul>
                  <li className="is-active" data-id="panel-content-1">
                    <a href="#panel-content-1">{lbl('LBL_OVERVIEW', 'Overview')}</a>
                  </li>
                  <li data-id="panel-content-2">
                    <a href="#panel-content-2">{lbl('LBL_COURSE_CONTENT', 'Course content')}</a>
                  </li>
                  {teacher && (
                    <li data-id="panel-content-3">
                      <a href="#panel-content-3">{lbl('LBL_ABOUT_TUTOR', 'About tutor')}</a>
                    </li>
                  )}
                  {(course.reviews ?? 0) > 0 && (
                    <li data-id="panel-content-4">
                      <a href="#panel-content-4">
                        {lbl('LBL_REVIEWS', 'Reviews')} ({course.reviews})
                      </a>
                    </li>
                  )}
                </ul>
              </nav>

              <div className="panels-container panels-container-js">
                <div data-id="panel-content-1" className="panel-content panel-content-js">
                  <div className="panel-content__head d-sm-none d-block panel-trigger-js">
                    <h3>{lbl('LBL_OVERVIEW', 'Overview')}</h3>
                  </div>
                  <div className="panel-content__body panel-target-js">
                    {intended && intended.learning_outcomes.length > 0 && (
                      <div className="content-group">
                        <h5 className="mb-4">
                          {lbl('LBL_WHAT_YOU_WILL_LEARN', "What you'll learn")}
                        </h5>
                        <div className="border p-4 rounded-4">
                          <div className="check-list check-list--half">
                            <ul className="check-listing">
                              {intended.learning_outcomes.map((item) => (
                                <li key={item.id}>{item.text}</li>
                              ))}
                            </ul>
                          </div>
                        </div>
                      </div>
                    )}
                    {intended && intended.requirements.length > 0 && (
                      <div className="content-group">
                        <h5 className="mb-4">{lbl('LBL_REQUIREMENTS', 'Requirements')}</h5>
                        <div className="border p-4 rounded-4">
                          <ul className="check-listing">
                            {intended.requirements.map((item) => (
                              <li key={item.id}>{item.text}</li>
                            ))}
                          </ul>
                        </div>
                      </div>
                    )}
                    {intended && intended.target_audience.length > 0 && (
                      <div className="content-group">
                        <h5 className="mb-4">
                          {lbl('LBL_WHO_IS_THE_COURSE_FOR', 'Who is this course for')}
                        </h5>
                        <div className="border p-4 rounded-4">
                          <div className="check-list check-list--half">
                            <ul className="check-listing">
                              {intended.target_audience.map((item) => (
                                <li key={item.id}>{item.text}</li>
                              ))}
                            </ul>
                          </div>
                        </div>
                      </div>
                    )}
                    {course.description && (
                      <div className="content-group">
                        <h5 className="mb-4">{lbl('LBL_DESCRIPTION', 'Description')}</h5>
                        <div
                          className="editor-content"
                          dangerouslySetInnerHTML={{
                            __html: normalizeLegacyHtml(course.description),
                          }}
                        />
                      </div>
                    )}
                    {course.tags && course.tags.length > 0 && (
                      <div className="content-group">
                        <h5 className="mb-4">{lbl('LBL_COURSE_TAGS', 'Course tags')}</h5>
                        <div className="tags">
                          {course.tags.map((tag) => (
                            <span key={tag} className="tags__item badge badge--curve">
                              {tag}
                            </span>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                </div>

                <div data-id="panel-content-2" className="panel-content panel-content-js">
                  <div className="panel-content__head panel-trigger-js">
                    <h3>{lbl('LBL_COURSE_CONTENT', 'Course content')}</h3>
                  </div>
                  <div className="panel-content__body panel-target-js" id="accordionParent">
                    <div className="inline-list mt-4 mt-sm-2">
                      <ul>
                        <li>
                          {course.sections} {lbl('LBL_SECTIONS', 'Sections')}
                        </li>
                        <li>
                          {course.lectures} {lbl('LBL_LECTURES', 'Lectures')}
                        </li>
                        {course.duration > 0 && (
                          <li>
                            {formatDuration(course.duration)}{' '}
                            {lbl('LBL_TOTAL_LENGTH', 'total length')}
                          </li>
                        )}
                      </ul>
                    </div>
                    {curriculum.map((section, i) => (
                      <div className="course-layout" key={section.id}>
                        <div
                          className={`course-layout__head ${i !== 0 ? 'collapsed' : ''}`}
                          data-bs-toggle="collapse"
                          data-bs-target={`#course-${section.id}`}
                        >
                          <div className="course-content">
                            <h5>{section.title}</h5>
                            <div className="course-counts">
                              <div className="course-counts__item">
                                {formatDuration(section.duration)}
                              </div>
                              <div className="course-counts__item">
                                {section.lectures.length} {lbl('LBL_LECTURES', 'Lectures')}
                              </div>
                            </div>
                            {section.details && (
                              <p className="mt-2 mb-0">{section.details}</p>
                            )}
                          </div>
                        </div>
                        <div
                          className={`course-layout__body collapse ${i === 0 ? 'show' : ''}`}
                          id={`course-${section.id}`}
                        >
                          <div className="course-layout-inner">
                            <div className="course-topic-list">
                              {section.lectures.map((lecture) => {
                                const preview =
                                  lecture.is_trial && lecture.is_accessible;
                                const inner = (
                                  <>
                                    <div className="course-topic__title">
                                      <SpriteIcon
                                        id="icon-play"
                                        className="icon icon--play icon--18"
                                      />
                                      <span className="course-topic__name">{lecture.title}</span>
                                    </div>
                                    <div className="course-topic__content">
                                      {preview && (
                                        <span className="course-topic__preview">
                                          {lbl('LBL_PREVIEW', 'Preview')}
                                        </span>
                                      )}
                                      <span className="course-topic__time">
                                        {formatDuration(lecture.duration)}
                                      </span>
                                    </div>
                                  </>
                                );
                                return (
                                  <div className="course-topic" key={lecture.id}>
                                    {preview ? (
                                      <Link
                                        to={`/courses/${course.id}/learn/${lecture.id}`}
                                        className="course-topic__action d-flex justify-content-between align-items-center text-decoration-none"
                                      >
                                        {inner}
                                      </Link>
                                    ) : (
                                      <div className="d-flex justify-content-between align-items-center">
                                        {inner}
                                      </div>
                                    )}
                                  </div>
                                );
                              })}
                            </div>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                {teacher && (
                  <div data-id="panel-content-3" className="panel-content panel-content-js">
                    <div className="panel-content__head panel-trigger-js">
                      <h3>{lbl('LBL_ABOUT_TUTOR', 'About tutor')}</h3>
                    </div>
                    <div className="panel-content__body panel-target-js">
                      <div className="author-box mt-3">
                        <div className="author-box__media">
                          <div
                            className="media ratio ratio--3by4"
                            data-title={firstChar(teacher.first_name ?? teacher.full_name)}
                          >
                            <img
                              src={imageUrl(AFILE.USER_PROFILE, teacher.id, 'MEDIUM')}
                              alt={teacher.full_name}
                            />
                          </div>
                          <div className="rating mt-3 d-inline-flex">
                            <SpriteIcon id="rating" className="rating__media" />
                            <span className="rating__value">{teacher.ratings ?? 0}</span>
                            <span className="rating__count">({teacher.reviews ?? 0})</span>
                          </div>
                        </div>
                        <div className="author-box__content">
                          <h4 className="author-name mb-2">
                            <Link to={teacherUrl}>{teacher.full_name}</Link>
                          </h4>
                          <div className="course-counts mb-2">
                            <div className="course-counts__item">
                              <div className="course-info">
                                <div className="course-info__media">
                                  <SpriteIcon id="icon-lecture" className="icon icon--level" />
                                </div>
                                <div className="course-info__title">
                                  {lbl('LBL_COURSES', 'Courses')}{' '}
                                  <strong>{teacher.courses ?? 0}</strong>
                                </div>
                              </div>
                            </div>
                          </div>
                          {teacher.biography && (
                            <div className="author-bio mb-3">
                              <p style={{ whiteSpace: 'pre-wrap' }}>{teacher.biography}</p>
                            </div>
                          )}
                          {teacher.profile_complete !== false && (
                            <Link to={teacherUrl} className="btn btn--primary-bordered">
                              {lbl('LBL_VIEW_PROFILE', 'View profile')}
                            </Link>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                )}

                {(course.reviews ?? 0) > 0 && (
                  <div data-id="panel-content-4" className="panel-content panel-content-js">
                    <div className="panel-content__head panel-trigger-js">
                      <h3>{lbl('LBL_RATINGS_&_REVIEWS', 'Ratings & reviews')}</h3>
                    </div>
                    <div className="panel-content__body panel-target-js">
                      <div className="reviews-section mt-5">
                        <div className="reviews-section__head">
                          <div className="rating-details mb-4">
                            <div className="rating-card">
                              <div className="rating-card__counter">
                                <div className="rating__count">
                                  <h1>{course.ratings}</h1>
                                  <SpriteIcon id="rating" className="icon icon--30 icon--rating" />
                                </div>
                                <div className="rating__info">
                                  <b>{lbl('LBL_Overall_Ratings', 'Overall ratings')}</b>
                                </div>
                              </div>
                              <div className="rating-card__progressbar">
                                <div className="progressbar-wrapper">
                                  <ul className="listing">
                                    {reviewStats.map((row) => (
                                      <li className="rating" key={row.rating}>
                                        <span className="rating__stars">
                                          {row.rating}
                                          <SpriteIcon
                                            id="rating"
                                            className="icon icon--xsmall icon--rating"
                                          />
                                        </span>
                                        <div className="rating__progressbar">
                                          {row.percent > 0 && (
                                            <div
                                              className="fill"
                                              style={{ width: `${row.percent}%` }}
                                            />
                                          )}
                                        </div>
                                        <span className="rating__percentage">{row.percent}%</span>
                                      </li>
                                    ))}
                                  </ul>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div className="reviews-wrapper">
                          <div className="reviews-wrapper__head mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div className="reviews-counter">
                              {lbl('LBL_REVIEWS', 'Reviews')} ({course.reviews})
                            </div>
                            <div className="review-sorting">
                              <select
                                value={reviewSort}
                                onChange={(e) => {
                                  const sort = e.target.value as 'DESC' | 'ASC';
                                  setReviewSort(sort);
                                  loadReviews(course.id, 1, sort, false);
                                }}
                              >
                                <option value="DESC">
                                  {lbl('LBL_SORT_BY_NEWEST', 'Newest')}
                                </option>
                                <option value="ASC">
                                  {lbl('LBL_SORT_BY_OLDEST', 'Oldest')}
                                </option>
                              </select>
                            </div>
                          </div>
                          <div className="reviews-list reviews-wrapper__body">
                            {reviews.length === 0 ? (
                              <p>{lbl('LBL_NO_REVIEWS_POSTED', 'No reviews posted')}</p>
                            ) : (
                              reviews.map((review) => (
                                <div className="review-row" key={review.id}>
                                  <div className="review-profile">
                                    <div
                                      className="avtar avtar--md avtar--round"
                                      data-title={firstChar(review.first_name)}
                                    >
                                      <img
                                        src={imageUrl(
                                          AFILE.USER_PROFILE,
                                          review.user_id,
                                          'SMALL'
                                        )}
                                        alt={review.first_name}
                                      />
                                    </div>
                                    <div className="user-info">
                                      <h6>
                                        {review.first_name} {review.last_name}
                                      </h6>
                                      <p>{review.created_at}</p>
                                    </div>
                                  </div>
                                  <div className="review-content">
                                    <div className="review-content__head">
                                      <div className="ratings mb-2">
                                        <SpriteIcon id="rating" className="icon icon--rating" />
                                        <span className="value">{review.rating}</span>
                                      </div>
                                      {review.title && <h6>{review.title}</h6>}
                                    </div>
                                    <div className="review-content__body">
                                      <p style={{ whiteSpace: 'pre-wrap' }}>{review.detail}</p>
                                    </div>
                                  </div>
                                </div>
                              ))
                            )}
                          </div>
                          {reviewsPage < reviewsLastPage && (
                            <div className="reviews-wrapper__foot show-more-container mt-3">
                              <button
                                type="button"
                                className="btn btn--grey btn--block btn--show"
                                onClick={() =>
                                  loadReviews(course.id, reviewsPage + 1, reviewSort, true)
                                }
                              >
                                {lbl('Lbl_SHOW_MORE', 'Show more')}
                              </button>
                            </div>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </section>

      {course.more_courses && course.more_courses.length > 0 && teacher && (
        <section className="section">
          <div className="container container--narrow">
            <div className="section__head d-flex justify-content-between align-items-center">
              <h3>
                {lbl('LBL_MORE_COURSES_FROM_{teacher-name}', 'More courses from')}{' '}
                <strong className="bold-700">{teacher.full_name}</strong>
              </h3>
            </div>
            <div className="section__body">
              <div className="slider slider-onethird-js">
                {course.more_courses.map((crs) => (
                  <div className="slider__item" key={crs.id}>
                    <div className="card-cover">
                      <div className="short-card">
                        <div className="short-card__head">
                          <div className="short-card__media ratio ratio--16by9">
                            <Link to={`/courses/${crs.slug}`}>
                              <img
                                src={imageUrl(AFILE.COURSE_IMAGE, crs.id, 'MEDIUM')}
                                alt={crs.title ?? ''}
                              />
                            </Link>
                          </div>
                        </div>
                        <div className="short-card__body">
                          <div className="rating">
                            <SpriteIcon id="rating" className="rating__media" />
                            <span className="rating__value">{crs.ratings}</span>
                            <span className="rating__count"> ({crs.reviews}) </span>
                          </div>
                          <h6 className="short-card__title">
                            <Link to={`/courses/${crs.slug}`}>{crs.title}</Link>
                          </h6>
                          <div className="card-element">
                            {crs.duration > 0 && (
                              <span className="card-element__item">
                                <SpriteIcon id="icon-clock" className="icon" />
                                {formatDuration(crs.duration)}
                              </span>
                            )}
                            <span className="card-element__item">
                              <SpriteIcon id="icon-lectures" className="icon" />
                              {crs.lectures} {lbl('LBL_LECTURES', 'Lectures')}
                            </span>
                          </div>
                          {crs.is_free ? (
                            <h4 className="free-text color-red bold-700 price-value">
                              {lbl('LBL_FREE', 'Free')}
                            </h4>
                          ) : (
                            <h4 className="bold-700 price-value">{formatMoney(crs.price)}</h4>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </section>
      )}
    </>
  );
}
