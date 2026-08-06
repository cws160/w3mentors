import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { adminApi } from '../api/adminClient';
import { adminLogoSrc, adminSiteTitle } from '../utils/adminBranding';
import { DashboardSpriteIcon } from '../../w3mentors/components/DashboardSpriteIcon';
import { SpriteIcon } from '../../w3mentors/components/SpriteIcon';
import { useSite } from '../../w3mentors/context/SiteContext';
import { AFILE, imageUrl } from '../../w3mentors/utils/assets';

type PreviewSection = {
  id: number;
  title: string;
  order: number;
  lectures_count: number;
  duration: number;
  lectures: Array<{
    id: number;
    title: string;
    order: number;
    duration: number;
    resources_count: number;
  }>;
};

type PreviewCourse = {
  id: number;
  title: string;
  teacher_name: string;
  teacher_id: number;
  reviews: number;
  ratings: number;
};

type PreviewAttachment = {
  id: number;
  type: number;
  name: string;
};

type PreviewLecture = {
  id: number;
  title: string;
  details: string;
  duration: number;
  order: number;
  section: { id: number; title: string; order: number };
  has_video: boolean;
  video_link: string;
  video_name: string;
  attachments: PreviewAttachment[];
  quiz_link_id: number | null;
  previous_lecture_id: number | null;
  next_lecture_id: number | null;
  previous_lecture_title: string | null;
  next_lecture_title: string | null;
};

type PreviewNote = {
  id: number;
  notes: string;
  lecture_title: string;
  lecture_order: number;
};

type PreviewReview = {
  id: number;
  user_id: number;
  first_name: string;
  last_name: string;
  title: string;
  detail: string;
  rating: number;
  created_at: string;
};

type PreviewReviewStat = {
  rating: number;
  count: number;
  percent: number;
};

type PreviewTeacher = {
  id: number;
  username: string;
  first_name: string;
  last_name: string;
  biography: string;
  ratings: number;
  reviews: number;
  courses: number;
  profile_complete: boolean;
};

type PreviewTab = 'lecture' | 'notes' | 'reviews' | 'tutor';

function formatLegacyDuration(seconds: number): string {
  if (seconds <= 0) {
    return '00m';
  }
  const hrs = Math.floor(seconds / 3600);
  const min = Math.floor((seconds % 3600) / 60);
  const parts: string[] = [];
  if (hrs > 0) {
    parts.push(`${hrs}h`);
  }
  if (min > 0) {
    parts.push(`${min}m`);
  }
  return parts.join(' ') || '00m';
}

function isYoutubeUrl(url: string): boolean {
  return /youtube\.com|youtu\.be/i.test(url);
}

function youtubeEmbedUrl(url: string): string | null {
  const match = url.match(/(?:youtu\.be\/|v=)([\w-]{11})/i);
  return match ? `https://www.youtube.com/embed/${match[1]}` : null;
}

function LectureVideo({ link, name }: { link: string; name: string }) {
  if (!link) {
    return null;
  }

  if (isYoutubeUrl(link)) {
    const embed = youtubeEmbedUrl(link);
    if (embed) {
      return (
        <iframe
          src={embed}
          title={name || 'Lecture video'}
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowFullScreen
          style={{ border: 0, width: '100%', height: '100%' }}
        />
      );
    }
  }

  if (!link.includes('/') && !link.includes('http')) {
    return (
      // @ts-expect-error mux-player custom element
      <mux-player
        playback-id={link}
        metadata-video-title={name || 'Lecture'}
        accent-color="#FF0000"
        style={{ width: '100%', height: '100%' }}
      />
    );
  }

  return (
    <iframe
      src={link}
      title={name || 'Lecture video'}
      style={{ border: 0, width: '100%', height: '100%' }}
      allowFullScreen
    />
  );
}

async function downloadPreviewResource(courseId: number, resourceId: number, filename: string) {
  const API_URL = import.meta.env.VITE_API_URL || '/api/v1';
  const token = localStorage.getItem('admin_token');
  const res = await fetch(
    `${API_URL}/admin/courses/${courseId}/preview/resources/${resourceId}/download`,
    { headers: token ? { Authorization: `Bearer ${token}` } : {} },
  );
  if (!res.ok) {
    return;
  }
  const blob = await res.blob();
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = filename || 'resource';
  anchor.click();
  URL.revokeObjectURL(url);
}

function formatReviewDate(value: string): string {
  if (!value) {
    return '';
  }
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }
  return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function findSectionIdForLecture(sections: PreviewSection[], lectureId: number): number | null {
  for (const section of sections) {
    if (section.lectures.some((l) => l.id === lectureId)) {
      return section.id;
    }
  }
  return null;
}

export function AdminCoursePreviewPage() {
  const { courseId: courseIdParam } = useParams();
  const courseId = Number(courseIdParam);
  const { site, lbl } = useSite();

  const [course, setCourse] = useState<PreviewCourse | null>(null);
  const [teacher, setTeacher] = useState<PreviewTeacher | null>(null);
  const [sections, setSections] = useState<PreviewSection[]>([]);
  const [quiz, setQuiz] = useState<{ id: number; title: string } | null>(null);
  const [activeLectureId, setActiveLectureId] = useState<number | null>(null);
  const [expandedSectionId, setExpandedSectionId] = useState<number | null>(null);
  const [lecture, setLecture] = useState<PreviewLecture | null>(null);
  const [activeTab, setActiveTab] = useState<PreviewTab>('lecture');
  const [mobilePanel, setMobilePanel] = useState<'sidebar' | 'content'>('content');
  const [loading, setLoading] = useState(true);
  const [lectureLoading, setLectureLoading] = useState(false);
  const [error, setError] = useState('');
  const [notesKeyword, setNotesKeyword] = useState('');
  const [notes, setNotes] = useState<PreviewNote[]>([]);
  const [notesMeta, setNotesMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [notesLoading, setNotesLoading] = useState(false);
  const [reviews, setReviews] = useState<PreviewReview[]>([]);
  const [reviewStats, setReviewStats] = useState<PreviewReviewStat[]>([]);
  const [reviewsMeta, setReviewsMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [reviewSort, setReviewSort] = useState<'ASC' | 'DESC'>('DESC');
  const [reviewsLoading, setReviewsLoading] = useState(false);
  const [completedLectures, setCompletedLectures] = useState<Set<number>>(() => new Set());

  const loadLecture = useCallback((lectureId: number) => {
    setActiveLectureId(lectureId);
    setActiveTab('lecture');
    setMobilePanel('content');
  }, []);

  useEffect(() => {
    if (!courseId || Number.isNaN(courseId)) {
      setError('Invalid course.');
      setLoading(false);
      return;
    }

    setLoading(true);
    adminApi
      .coursePreview(courseId)
      .then((res) => {
        const data = res.data.data;
        setCourse(data.course as PreviewCourse);
        setTeacher((data.teacher as PreviewTeacher | null) ?? null);
        setSections((data.sections ?? []) as PreviewSection[]);
        setQuiz((data.quiz as { id: number; title: string } | null) ?? null);
        const firstId = Number(data.first_lecture_id ?? 0);
        if (firstId > 0) {
          loadLecture(firstId);
          const sectionId = findSectionIdForLecture(
            (data.sections ?? []) as PreviewSection[],
            firstId,
          );
          setExpandedSectionId(sectionId);
        }
      })
      .catch((err: unknown) => {
        const message =
          err && typeof err === 'object' && 'response' in err
            ? String((err as { response?: { data?: { message?: string } } }).response?.data?.message ?? '')
            : '';
        setError(message || 'Unable to load course preview.');
      })
      .finally(() => setLoading(false));
  }, [courseId, loadLecture]);

  useEffect(() => {
    if (!courseId || !activeLectureId) {
      setLecture(null);
      return;
    }

    setLectureLoading(true);
    adminApi
      .coursePreviewLecture(courseId, activeLectureId)
      .then((res) => {
        const next = res.data.data as PreviewLecture;
        setLecture(next);
        setExpandedSectionId(findSectionIdForLecture(sections, next.id));
      })
      .catch(() => setLecture(null))
      .finally(() => setLectureLoading(false));
  }, [courseId, activeLectureId, sections]);

  useEffect(() => {
    if (!lecture?.video_link || lecture.video_link.includes('http')) {
      return;
    }
    if (document.querySelector('script[data-mux-player]')) {
      return;
    }
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/@mux/mux-player';
    script.async = true;
    script.dataset.muxPlayer = '1';
    document.head.appendChild(script);
  }, [lecture?.video_link]);

  const loadNotes = useCallback(
    (page = 1, keyword = notesKeyword) => {
      if (!courseId) {
        return;
      }
      setNotesLoading(true);
      adminApi
        .coursePreviewNotes(courseId, { keyword, page })
        .then((res) => {
          setNotes(res.data.data.data);
          setNotesMeta(res.data.data.meta);
        })
        .catch(() => {
          setNotes([]);
          setNotesMeta({ current_page: 1, last_page: 1, total: 0 });
        })
        .finally(() => setNotesLoading(false));
    },
    [courseId, notesKeyword],
  );

  const loadReviews = useCallback(
    (page = 1, sort = reviewSort) => {
      if (!courseId) {
        return;
      }
      setReviewsLoading(true);
      adminApi
        .coursePreviewReviews(courseId, { sort, page })
        .then((res) => {
          setReviews(res.data.data.reviews);
          setReviewStats(res.data.data.stats);
          setReviewsMeta(res.data.data.meta);
        })
        .catch(() => {
          setReviews([]);
          setReviewStats([]);
          setReviewsMeta({ current_page: 1, last_page: 1, total: 0 });
        })
        .finally(() => setReviewsLoading(false));
    },
    [courseId, reviewSort],
  );

  useEffect(() => {
    if (activeTab === 'notes') {
      loadNotes(1);
    }
  }, [activeTab, loadNotes]);

  useEffect(() => {
    if (activeTab === 'reviews') {
      loadReviews(1);
    }
  }, [activeTab, loadReviews]);

  const attachments = lecture?.attachments ?? [];
  const hasVideo = Boolean(lecture?.has_video && lecture.video_link);

  const lectureTitle = lecture
    ? `${lecture.order}. ${lecture.title}`
    : '';

  if (loading) {
    return (
      <div className="table-processing loaderJs" style={{ minHeight: '100vh' }}>
        <div className="spinner spinner--sm spinner--brand" />
      </div>
    );
  }

  if (error || !course) {
    return (
      <div className="container py-5">
        <p className="color-danger">{error || 'Preview not available.'}</p>
        <Link to="/admin/courses" className="btn btn-brand btn-sm">
          Back to courses
        </Link>
      </div>
    );
  }

  return (
    <page className="page">
      <main className="page-container">
        <header className="header">
          <div className="header-primary d-sm-flex justify-content-sm-between align-items-sm-center">
            <div className="header-primary__right order-sm-2">
              <div className="d-flex justify-content-between align-items-center">
                <div className="course-progress in-progress">
                  <a
                    href="#course-progress"
                    className="course-progress__trigger d-flex align-items-center trigger-js"
                    onClick={(e) => e.preventDefault()}
                  >
                    <div className="course-progress__count me-1">
                      <div className="percent">
                        <svg className="percent__progress" viewBox="0 0 300 300">
                          <circle cx="150" cy="150" r="100" />
                          <circle cx="150" cy="150" r="100" style={{ ['--percent' as string]: 0 }} />
                        </svg>
                        <DashboardSpriteIcon id="trophy" className="icon icon--trophy percent__media" />
                      </div>
                    </div>
                    <div className="course-progress__content">
                      <h6>Course progress</h6>
                      <small className="progressPercent">0% completed</small>
                    </div>
                  </a>
                </div>
                <div className="account">
                  <a
                    href="#accout-target"
                    className="avtar avtar--small avtar--round account__trigger trigger-js"
                    data-title={teacher?.first_name?.[0] ?? 'T'}
                    onClick={(e) => e.preventDefault()}
                  >
                    <img
                      src={imageUrl(AFILE.USER_PROFILE, course.teacher_id, 'SMALL')}
                      alt={course.teacher_name}
                    />
                  </a>
                </div>
              </div>
            </div>
            <div className="header-primary__left order-sm-1">
              <div className="d-sm-flex justify-content-sm-between align-items-sm-center">
                <figure className="header-logo">
                  <Link to="/">
                    <img
                      src={adminLogoSrc()}
                      alt={adminSiteTitle(site?.name)}
                    />
                  </Link>
                </figure>
                <h1 className="page-title">
                  <a href="javascript:void(0);">{course.title}</a>
                </h1>
              </div>
            </div>
          </div>
        </header>

        <div className="body">
          <div className="body-panel">
            <div className="section-intro videoContentJs">
              <div
                className="course-video ratio ratio--2by1"
                style={{ display: hasVideo && !lectureLoading ? 'block' : 'none' }}
              >
                {lecture ? (
                  <LectureVideo link={lecture.video_link} name={lecture.video_name} />
                ) : null}
              </div>
              <div
                className="course-video-error ratio ratio--2by1 heading-4 color-danger"
                style={{
                  display: !lectureLoading && lecture && !hasVideo ? 'flex' : 'none',
                }}
              >
                <div className="d-flex justify-content-center align-items-center direction-column">
                  <DashboardSpriteIcon id="issue" className="icon" />
                  <span>No video attached to this lecture.</span>
                </div>
              </div>
              <div className="directions">
                {lecture?.previous_lecture_id ? (
                  <a
                    href="javascript:void(0)"
                    className="directions-prev getPrevJs"
                    onClick={(e) => {
                      e.preventDefault();
                      loadLecture(lecture.previous_lecture_id!);
                    }}
                  >
                    <span className="directions-title directionTitleJs">
                      {lecture.previous_lecture_title}
                    </span>
                    <span className="directions-prev__control" />
                  </a>
                ) : null}
                {lecture?.next_lecture_id ? (
                  <a
                    href="javascript:void(0)"
                    className="directions-next getNextJs"
                    onClick={(e) => {
                      e.preventDefault();
                      loadLecture(lecture.next_lecture_id!);
                    }}
                  >
                    <span className="directions-title directionTitleJs">
                      {lecture.next_lecture_title}
                    </span>
                    <span className="directions-next__control" />
                  </a>
                ) : null}
              </div>
            </div>

            <div className="section-layout">
              <div className="section-layout__head">
                <div className="container">
                  <div className="breadcrumbs">
                    <ul>
                      <li>
                        <Link to="/dashboard/teacher">Dashboard</Link>
                      </li>
                      <li>
                        <Link to="/dashboard/teacher/courses">My courses</Link>
                      </li>
                      <li>{course.title}</li>
                    </ul>
                  </div>
                  <h2 className="page-subtitle mb-4 lectureTitleJs">{lectureTitle}</h2>
                  <div className="section-links">
                    <div className="section-links__left">
                      <nav className="tabs tabs--line border-bottom-0 tabs-scrollable-js tutorialTabsJs">
                        <ul>
                          <li className="d-xl-none d-block responsive-toggle-js">
                            <a
                              href="javascript:void(0);"
                              className={mobilePanel === 'sidebar' ? 'is-active' : ''}
                              onClick={(e) => {
                                e.preventDefault();
                                setMobilePanel('sidebar');
                              }}
                            >
                              Course lectures
                            </a>
                          </li>
                          <li className={activeTab === 'lecture' ? 'is-active' : ''}>
                            <a
                              href="javascript:void(0);"
                              className="crsDetailTabJs lecTitleJs"
                              onClick={(e) => {
                                e.preventDefault();
                                setActiveTab('lecture');
                                setMobilePanel('content');
                              }}
                            >
                              Lecture detail
                            </a>
                          </li>
                          <li className={activeTab === 'notes' ? 'is-active' : ''}>
                            <a
                              href="javascript:void(0);"
                              onClick={(e) => {
                                e.preventDefault();
                                setActiveTab('notes');
                                setMobilePanel('content');
                              }}
                            >
                              Notes
                            </a>
                          </li>
                          <li className={activeTab === 'reviews' ? 'is-active' : ''}>
                            <a
                              href="javascript:void(0);"
                              onClick={(e) => {
                                e.preventDefault();
                                setActiveTab('reviews');
                                setMobilePanel('content');
                              }}
                            >
                              Reviews ({course.reviews})
                            </a>
                          </li>
                          <li className={activeTab === 'tutor' ? 'is-active' : ''}>
                            <a
                              href="javascript:void(0);"
                              onClick={(e) => {
                                e.preventDefault();
                                setActiveTab('tutor');
                                setMobilePanel('content');
                              }}
                            >
                              Tutor&apos;s info
                            </a>
                          </li>
                        </ul>
                      </nav>
                    </div>
                    <div className="section-links__right" />
                  </div>
                </div>
              </div>

              <div className="section-layout__body">
                <div className="container">
                  <sidebar
                    className={`body-side responsive-target-js sidebarPanelJs${mobilePanel === 'content' ? ' d-none d-xl-block' : ''}`}
                  >
                    <div className="toggle-control-list">
                      {sections.map((section) => {
                        const isSectionActive =
                          expandedSectionId === section.id ||
                          section.lectures.some((l) => l.id === activeLectureId);
                        return (
                          <div
                            key={section.id}
                            className={`toggle-control control-group-js sectionListJs${isSectionActive ? ' is-active' : ''}`}
                          >
                            <div
                              className="toggle-control__action control-trigger-js"
                              role="button"
                              tabIndex={0}
                              onClick={() =>
                                setExpandedSectionId((prev) =>
                                  prev === section.id ? null : section.id,
                                )
                              }
                              onKeyDown={(e) => {
                                if (e.key === 'Enter' || e.key === ' ') {
                                  e.preventDefault();
                                  setExpandedSectionId((prev) =>
                                    prev === section.id ? null : section.id,
                                  );
                                }
                              }}
                            >
                              <h6>
                                Section {section.order}: {section.title}
                              </h6>
                              <p>
                                <span className={`completedLecture${section.id}`}>0</span>
                                {' / '}
                                {section.lectures_count} | {formatLegacyDuration(section.duration)}
                              </p>
                            </div>
                            <div
                              className="toggle-control__target control-target-js"
                              style={{
                                display: expandedSectionId === section.id ? 'block' : 'none',
                              }}
                            >
                              <div className="lecture-list lecturesListJs">
                                {section.lectures.map((item) => (
                                  <div
                                    key={item.id}
                                    className={`lecture${activeLectureId === item.id ? ' is-active' : ''}`}
                                    id={`lectureJs${item.id}`}
                                  >
                                    <div className="lecture__control is-hover">
                                      <label className="lecture-checkbox">
                                        <input
                                          type="checkbox"
                                          name="lecture_id"
                                          data-section={section.id}
                                          value={item.id}
                                          checked={completedLectures.has(item.id)}
                                          onChange={() => {
                                            setCompletedLectures((prev) => {
                                              const next = new Set(prev);
                                              if (next.has(item.id)) {
                                                next.delete(item.id);
                                              } else {
                                                next.add(item.id);
                                              }
                                              return next;
                                            });
                                          }}
                                        />
                                        <i className="lecture-checkbox__view" />
                                      </label>
                                      <div className="tooltip tooltip--right bg-black">Mark read</div>
                                    </div>
                                    <div
                                      className="lecture__content"
                                      role="button"
                                      tabIndex={0}
                                      onClick={() => loadLecture(item.id)}
                                      onKeyDown={(e) => {
                                        if (e.key === 'Enter' || e.key === ' ') {
                                          e.preventDefault();
                                          loadLecture(item.id);
                                        }
                                      }}
                                    >
                                      <p className="lectureName">
                                        {item.order}. {item.title}
                                      </p>
                                      <div className="lecture-meta">
                                        <div className="lecture-meta__item d-flex align-items-center">
                                          <DashboardSpriteIcon
                                            id="icon-play"
                                            className="icon icon--play icon--xsmall me-1"
                                            width={14}
                                            height={14}
                                          />
                                          <span>{formatLegacyDuration(item.duration)}</span>
                                        </div>
                                        {item.resources_count > 0 ? (
                                          <div className="lecture-meta__item d-flex align-items-center">
                                            <DashboardSpriteIcon
                                              id="icon-attachments"
                                              className="icon icon--attachment"
                                              width={14}
                                              height={14}
                                            />
                                            <span>
                                              {item.resources_count} resources
                                            </span>
                                          </div>
                                        ) : null}
                                      </div>
                                    </div>
                                  </div>
                                ))}
                              </div>
                            </div>
                          </div>
                        );
                      })}
                      {quiz ? (
                        <div className="toggle-control control-group-js quizListJs">
                          <div className="toggle-control__action control-trigger-js no-after">
                            <h6 className="lectureName quizLectureJs">Quiz: {quiz.title}</h6>
                          </div>
                        </div>
                      ) : null}
                    </div>
                  </sidebar>

                  <div
                    className={`content-area responsive-target-js tabsPanelJs${mobilePanel === 'sidebar' ? ' d-none d-xl-block' : ''}`}
                  >
                    {activeTab === 'lecture' ? (
                      <div className="lectureDetailJs">
                        {lectureLoading ? (
                          <div className="table-processing">
                            <div className="spinner spinner--sm spinner--brand" />
                          </div>
                        ) : lecture ? (
                          <>
                            <div className="row justify-content-between">
                              <div className={attachments.length > 0 ? 'col-xl-7' : 'col-xl-12'}>
                                <div className="cms-container">
                                  {lecture.details ? (
                                    <div
                                      className="editor-content iframe-content"
                                      dangerouslySetInnerHTML={{ __html: lecture.details }}
                                    />
                                  ) : (
                                    <div className="editor-content iframe-content">
                                      <p className="color-gray-700">
                                        {lbl('LBL_NO_LECTURE_DESCRIPTION', 'No lecture description.')}
                                      </p>
                                    </div>
                                  )}
                                </div>
                              </div>
                              {attachments.length > 0 ? (
                                <div className="col-xl-5 d-flex justify-content-xl-end align-items-xl-start">
                                  <div className="box-outlined">
                                    <div className="box-outlined__head mb-4">
                                      <h6>
                                        {lbl('LBL_LECTURE_RESOURCES', 'Lecture resources')} (
                                        {attachments.length})
                                      </h6>
                                    </div>
                                    <div className="box-outlined__body">
                                      <div className="lecture-attachment">
                                        {attachments.map((resource) => (
                                          <a
                                            key={resource.id}
                                            href="javascript:void(0);"
                                            className="lecture-attachment__item"
                                            onClick={(e) => {
                                              e.preventDefault();
                                              void downloadPreviewResource(
                                                courseId,
                                                resource.id,
                                                resource.name,
                                              );
                                            }}
                                          >
                                            <figure className="lecture-attachment__media">
                                              <svg className="attached-media">
                                                <use xlinkHref="/dashboard/images/sprite.svg#pdf-attachment" />
                                              </svg>
                                            </figure>
                                            <span className="lecture-attachment__content">
                                              <p className="mb-0 color-black">
                                                {resource.name || 'Resource'}
                                              </p>
                                            </span>
                                          </a>
                                        ))}
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              ) : null}
                            </div>
                            <div className="page-directions border-top">
                              <div className="row justify-content-between">
                                <div className="col-sm-6">
                                  {!hasVideo ? (
                                    <a
                                      href="javascript:void(0);"
                                      className={`btn btn--primary btn--sm-block ${
                                        completedLectures.has(lecture.id) ? 'btn--disabled' : ''
                                      }`}
                                      onClick={(e) => {
                                        e.preventDefault();
                                        if (completedLectures.has(lecture.id)) {
                                          return;
                                        }
                                        setCompletedLectures((prev) => new Set(prev).add(lecture.id));
                                      }}
                                    >
                                      {lbl('LBL_MARK_LECTURE_COMPLETE', 'Mark lecture complete')}
                                    </a>
                                  ) : null}
                                </div>
                                <div className="col-sm-auto">
                                  <div className="btn-actions">
                                    <a
                                      href="javascript:void(0);"
                                      className={`btn btn--primary-bordered mr-1 getPrevJs ${!lecture.previous_lecture_id ? 'btn--disabled' : ''}`}
                                      onClick={(e) => {
                                        e.preventDefault();
                                        if (lecture.previous_lecture_id) {
                                          loadLecture(lecture.previous_lecture_id);
                                        }
                                      }}
                                    >
                                      <DashboardSpriteIcon
                                        id="prev"
                                        className="icon icon--arrow icon--xsmall me-2"
                                        width={14}
                                        height={14}
                                      />
                                      {lbl('LBL_PREV', 'Prev')}
                                    </a>
                                    {lecture.quiz_link_id && !lecture.next_lecture_id ? (
                                      <a
                                        href="javascript:void(0);"
                                        className="btn btn--primary-bordered ms-1 quizNavJs"
                                        onClick={(e) => e.preventDefault()}
                                      >
                                        {lbl('LBL_NEXT', 'Next')}
                                        <DashboardSpriteIcon
                                          id="next"
                                          className="icon icon--arrow icon--xsmall ms-2"
                                          width={14}
                                          height={14}
                                        />
                                      </a>
                                    ) : (
                                      <a
                                        href="javascript:void(0);"
                                        className={`btn btn--primary-bordered ms-1 getNextJs ${!lecture.next_lecture_id ? 'btn--disabled' : ''}`}
                                        onClick={(e) => {
                                          e.preventDefault();
                                          if (lecture.next_lecture_id) {
                                            loadLecture(lecture.next_lecture_id);
                                          }
                                        }}
                                      >
                                        {lbl('LBL_NEXT', 'Next')}
                                        <DashboardSpriteIcon
                                          id="next"
                                          className="icon icon--arrow icon--xsmall ms-2"
                                          width={14}
                                          height={14}
                                        />
                                      </a>
                                    )}
                                  </div>
                                </div>
                              </div>
                            </div>
                          </>
                        ) : null}
                      </div>
                    ) : null}

                    {activeTab === 'notes' ? (
                      <div className="row justify-content-center notesJs">
                        <div className="col-lg-8">
                          <div className="notes-container">
                            <div className="notes-container__head notesHeadJs">
                              <div className="search-view">
                                <div className="search-view__large">
                                  <form
                                    className="form-search"
                                    onSubmit={(e) => {
                                      e.preventDefault();
                                      loadNotes(1, notesKeyword);
                                    }}
                                  >
                                    <div className="form-search__field">
                                      <input
                                        type="text"
                                        id="notesKeywordJs"
                                        placeholder={lbl('LBL_SEARCH_BY_KEYWORD', 'Search by keyword')}
                                        value={notesKeyword}
                                        onChange={(e) => setNotesKeyword(e.target.value)}
                                      />
                                    </div>
                                    <div className="form-search__action form-search__action--submit">
                                      <button type="submit" className="btn btn--equal btn--transparent color-black">
                                        <DashboardSpriteIcon
                                          id="search"
                                          className="icon icon--search icon--small"
                                        />
                                      </button>
                                    </div>
                                    {notesKeyword ? (
                                      <div className="form-search__action form-search__action--reset">
                                        <button
                                          type="button"
                                          className="btn btn--equal btn--transparent color-black"
                                          onClick={() => {
                                            setNotesKeyword('');
                                            loadNotes(1, '');
                                          }}
                                        >
                                          <span className="form-reset" />
                                        </button>
                                      </div>
                                    ) : null}
                                  </form>
                                </div>
                                <div className="search-view__small">
                                  <a
                                    href="javascript:void(0);"
                                    className="btn btn--secondary btn--disabled"
                                    onClick={(e) => e.preventDefault()}
                                  >
                                    <DashboardSpriteIcon id="plus-more" className="icon" />
                                    {lbl('LBL_ADD_NEW_NOTE', 'Add new note')}
                                  </a>
                                </div>
                              </div>
                            </div>
                            <div className="notes-container__body">
                              <div className="notes-listing notesListingJs">
                                {notesLoading ? (
                                  <div className="table-processing">
                                    <div className="spinner spinner--sm spinner--brand" />
                                  </div>
                                ) : notes.length === 0 ? (
                                  <div className="message-display">
                                    <h4>
                                      {lbl(
                                        'LBL_YOU_HAVE_NOT_ADDED_ANY_NOTE_YET.',
                                        'You have not added any note yet.',
                                      )}
                                    </h4>
                                    <p>
                                      {lbl(
                                        'LBL_CLICK_TO_"ADD_A_NEW_NOTE"_BUTTON_TO_MAKE_YOUR_FIRST_NOTE.',
                                        'Click the Add a new note button to make your first note.',
                                      )}
                                    </p>
                                    <a
                                      href="javascript:void(0);"
                                      className="btn btn--secondary btn--disabled"
                                      onClick={(e) => e.preventDefault()}
                                    >
                                      <DashboardSpriteIcon id="plus-more" className="icon" />
                                      {lbl('LBL_ADD_A_NEW_NOTE', 'Add a new note')}
                                    </a>
                                  </div>
                                ) : (
                                  <>
                                    {notes.map((note) => (
                                      <div className="notes" key={note.id}>
                                        <div className="notes__body">
                                          <div className="notes__content">
                                            <h6 className="notes__title">
                                              {note.lecture_order}. {note.lecture_title}
                                            </h6>
                                            <p style={{ whiteSpace: 'pre-wrap' }}>{note.notes}</p>
                                          </div>
                                        </div>
                                      </div>
                                    ))}
                                    {notesMeta.last_page > 1 ? (
                                      <div className="pagination pagination--centered mt-5">
                                        <ul className="pagination">
                                          <li className={notesMeta.current_page <= 1 ? 'disabled' : ''}>
                                            <button
                                              type="button"
                                              className="btn btn--transparent"
                                              disabled={notesMeta.current_page <= 1}
                                              onClick={() => loadNotes(notesMeta.current_page - 1)}
                                            >
                                              {lbl('LBL_PREV', 'Prev')}
                                            </button>
                                          </li>
                                          <li>
                                            <span>
                                              {notesMeta.current_page} / {notesMeta.last_page}
                                            </span>
                                          </li>
                                          <li
                                            className={
                                              notesMeta.current_page >= notesMeta.last_page
                                                ? 'disabled'
                                                : ''
                                            }
                                          >
                                            <button
                                              type="button"
                                              className="btn btn--transparent"
                                              disabled={notesMeta.current_page >= notesMeta.last_page}
                                              onClick={() => loadNotes(notesMeta.current_page + 1)}
                                            >
                                              {lbl('LBL_NEXT', 'Next')}
                                            </button>
                                          </li>
                                        </ul>
                                      </div>
                                    ) : null}
                                  </>
                                )}
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    ) : null}

                    {activeTab === 'reviews' ? (
                      <div className="row justify-content-center reviewsJs">
                        <div className="col-xl-10 col-lg-10">
                          {reviewsLoading && reviews.length === 0 ? (
                            <div className="table-processing">
                              <div className="spinner spinner--sm spinner--brand" />
                            </div>
                          ) : (
                            <div className="reviews-section">
                              <div className="reviews-section__head">
                                <div className="reviews-stats">
                                  <div className="row justify-content-between">
                                    <div className="col-4 col-sm-2">
                                      <div className="reviews-total">
                                        <div className="reviews-media">
                                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 110 110">
                                            <g transform="translate(-28.999 -29)">
                                              <path
                                                d="M892.348,2341l17.582,31.851,35.759,6.861L920.8,2406.26l4.518,36.091-32.967-15.445-32.968,15.445,4.518-36.091-24.892-26.546,35.759-6.861L892.348,2341"
                                                transform="translate(-808.008 -2308.001)"
                                              />
                                            </g>
                                          </svg>
                                          <span className="reviews-count">{course.ratings}</span>
                                        </div>
                                        <div className="reviews-value">
                                          {course.reviews} {lbl('LBL_REVIEWS', 'Reviews')}
                                        </div>
                                      </div>
                                    </div>
                                    <div className="col-8 col-sm-6">
                                      <div className="reviews-counter">
                                        {reviewStats.map((row) => (
                                          <div className="reviews-counter__item" key={row.rating}>
                                            <div className="reviews-progress">
                                              <div className="reviews-progress__value">{row.rating}</div>
                                              <div className="reviews-progress__content">
                                                <div className="progress progress--small progress--round">
                                                  {row.percent > 0 ? (
                                                    <div
                                                      className="progress__bar bg-yellow"
                                                      role="progressbar"
                                                      style={{ width: `${row.percent}%` }}
                                                    />
                                                  ) : null}
                                                </div>
                                              </div>
                                              <div className="reviews-progress__value">
                                                {row.count > 0 ? `(${row.count})` : ''}
                                              </div>
                                            </div>
                                          </div>
                                        ))}
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              <div className="reviews-section__body">
                                <div className="reviews-sorting reviewsListJs">
                                  <div className="row justify-content-between align-items-center">
                                    <div className="col-sm-auto">
                                      <p className="m-0 pagingLblJs">
                                        {(() => {
                                          const start =
                                            reviewsMeta.total > 0
                                              ? (reviewsMeta.current_page - 1) * 12 + 1
                                              : 0;
                                          const end = Math.min(
                                            reviewsMeta.total,
                                            start + 12 - 1,
                                          );
                                          return lbl(
                                            'LBL_DISPLAYING_REVIEWS_{start-count}_TO_{end-count}_OF_{total}',
                                            `Displaying reviews ${start} to ${end} of ${reviewsMeta.total}`,
                                          )
                                            .replace('{start-count}', String(start))
                                            .replace('{end-count}', String(end))
                                            .replace('{total}', String(reviewsMeta.total));
                                        })()}
                                      </p>
                                    </div>
                                    <div className="col-sm-auto">
                                      <div className="reviews-sort">
                                        <select
                                          value={reviewSort}
                                          onChange={(e) => {
                                            const sort = e.target.value as 'ASC' | 'DESC';
                                            setReviewSort(sort);
                                            loadReviews(1, sort);
                                          }}
                                        >
                                          <option value="DESC">
                                            {lbl('LBL_SORT_BY_NEWEST', 'Newest first')}
                                          </option>
                                          <option value="ASC">
                                            {lbl('LBL_SORT_BY_OLDEST', 'Oldest first')}
                                          </option>
                                        </select>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                                <div className="reviews-list reviewSrchListJs">
                                  {reviews.length === 0 ? (
                                    <div className="message-display">
                                      <h4>
                                        {lbl('LBL_NO_REVIEWS_POSTED_YET', 'No reviews posted yet')}
                                      </h4>
                                    </div>
                                  ) : (
                                    reviews.map((review) => (
                                      <div className="review" key={review.id}>
                                        <div className="review__media">
                                          <div
                                            className="avtar--md avtar--round"
                                            data-title={review.first_name?.[0]?.toUpperCase() ?? 'U'}
                                          >
                                            <img
                                              src={imageUrl(
                                                AFILE.USER_PROFILE,
                                                review.user_id,
                                                'SMALL',
                                              )}
                                              alt={`${review.first_name} ${review.last_name}`}
                                            />
                                          </div>
                                        </div>
                                        <div className="review__content">
                                          <span className="review__author">
                                            {review.first_name} {review.last_name}
                                          </span>
                                          <div className="review__meta">
                                            <div className="review__rating">
                                              <div className="rating">
                                                <svg className="rating__media" aria-hidden>
                                                  <use xlinkHref="/dashboard/images/sprite.svg#rating" />
                                                </svg>
                                                <span className="rating__value">{review.rating}</span>
                                              </div>
                                            </div>
                                            <div className="review__date">
                                              {formatReviewDate(review.created_at)}
                                            </div>
                                          </div>
                                          {review.title ? (
                                            <h6 className="review__title mt-4">{review.title}</h6>
                                          ) : null}
                                          <div className="review__message">
                                            <p style={{ whiteSpace: 'pre-wrap' }}>{review.detail}</p>
                                          </div>
                                        </div>
                                      </div>
                                    ))
                                  )}
                                </div>
                                {reviewsMeta.last_page > 1 ? (
                                  <div className="pagination pagination--centered mt-5 reviewSrchListJs">
                                    <ul className="pagination">
                                      <li className={reviewsMeta.current_page <= 1 ? 'disabled' : ''}>
                                        <button
                                          type="button"
                                          className="btn btn--transparent"
                                          disabled={reviewsMeta.current_page <= 1}
                                          onClick={() => loadReviews(reviewsMeta.current_page - 1)}
                                        >
                                          {lbl('LBL_PREV', 'Prev')}
                                        </button>
                                      </li>
                                      <li>
                                        <span>
                                          {reviewsMeta.current_page} / {reviewsMeta.last_page}
                                        </span>
                                      </li>
                                      <li
                                        className={
                                          reviewsMeta.current_page >= reviewsMeta.last_page
                                            ? 'disabled'
                                            : ''
                                        }
                                      >
                                        <button
                                          type="button"
                                          className="btn btn--transparent"
                                          disabled={
                                            reviewsMeta.current_page >= reviewsMeta.last_page
                                          }
                                          onClick={() => loadReviews(reviewsMeta.current_page + 1)}
                                        >
                                          {lbl('LBL_NEXT', 'Next')}
                                        </button>
                                      </li>
                                    </ul>
                                  </div>
                                ) : null}
                              </div>
                            </div>
                          )}
                        </div>
                      </div>
                    ) : null}

                    {activeTab === 'tutor' && teacher ? (
                      <div className="row justify-content-center tutorInfoJs">
                        <div className="col-xl-8 col-lg-10">
                          <div className="author-bio">
                            <div className="author-bio__head">
                              <h5 className="bold-700">
                                {lbl('LBL_ABOUT_TUTOR', 'About tutor')}
                              </h5>
                              <div className="author-box">
                                <div className="author-box__media">
                                  <div
                                    className="avtar avtar--large"
                                    data-title={teacher.first_name?.[0]?.toUpperCase() ?? 'T'}
                                  >
                                    <img
                                      src={imageUrl(AFILE.USER_PROFILE, teacher.id, 'MEDIUM')}
                                      alt={`${teacher.first_name} ${teacher.last_name}`}
                                    />
                                  </div>
                                </div>
                                <div className="author-box__content">
                                  <h4 className="author-name mb-2">
                                    {teacher.first_name} {teacher.last_name}
                                  </h4>
                                  <div className="rating mb-3">
                                    <svg className="rating__media" aria-hidden>
                                      <use xlinkHref="/dashboard/images/sprite.svg#rating" />
                                    </svg>
                                    <span className="rating__value">{teacher.ratings}</span>
                                    <span className="rating__count">
                                      ({teacher.reviews}{' '}
                                      {lbl('LBL_REVIEWS', 'Reviews')})
                                    </span>
                                  </div>
                                  <div className="course-counts mb-2">
                                    <div className="course-counts__item">
                                      <div className="course-info">
                                        <span className="course-info__media">
                                          <SpriteIcon
                                            id="icon-lecture"
                                            className="icon icon--teaches icon--18"
                                          />
                                        </span>
                                        <span className="course-info__title">
                                          <strong>{lbl('LBL_COURSES', 'Courses')}</strong>{' '}
                                          {teacher.courses}
                                        </span>
                                      </div>
                                    </div>
                                  </div>
                                  {teacher.profile_complete ? (
                                    <Link
                                      to={
                                        teacher.username
                                          ? `/teachers/${teacher.username}`
                                          : `/teachers/${teacher.id}`
                                      }
                                      className="btn btn--primary-bordered btn--small"
                                      target="_blank"
                                      rel="noreferrer"
                                    >
                                      {lbl('LBL_VIEW_PROFILE', 'View profile')}
                                    </Link>
                                  ) : null}
                                </div>
                              </div>
                            </div>
                            {teacher.biography ? (
                              <div className="author-bio__body mt-5">
                                <h5 className="bold-700">
                                  {lbl('LBL_BIOGRAPHY', 'Biography')}
                                </h5>
                                <div className="author-box__desc">
                                  <p style={{ whiteSpace: 'pre-wrap' }}>{teacher.biography}</p>
                                </div>
                              </div>
                            ) : null}
                          </div>
                        </div>
                      </div>
                    ) : null}
                  </div>
                </div>
              </div>

              <div className="section-layout__footer">
                <div className="container">
                  <div className="row">
                    <div className="col-md-6">
                      <div className="copyright mb-2 mb-md-0">
                        &copy; {new Date().getFullYear()} W3 Mentors
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </page>
  );
}
