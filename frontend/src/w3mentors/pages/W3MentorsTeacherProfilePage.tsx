import { useEffect, useMemo, useState } from 'react';
import { useParams } from 'react-router-dom';
import { teachersApi, type TeacherProfile, type TeacherQualification } from '../../api/client';
import { useSite } from '../context/SiteContext';
import { W3MentorsPageMessage } from '../components/W3MentorsPageMessage';
import { ShortCourseCard } from '../components/ShortCourseCard';
import { SpriteIcon } from '../components/SpriteIcon';
import { TeacherAvailabilityPanel } from '../components/teacher/TeacherAvailabilityPanel';
import { useTeacherProfileActions } from '../hooks/useTeacherProfileActions';
import { useW3MentorsSliders } from '../hooks/useW3MentorsSliders';
import { bindTeacherDetailPage, changeTeacherPricingSlot } from '../lib/w3mentors-ui';
import { AFILE, firstChar, formatMoney, imageUrl } from '../utils/assets';

const QUAL_TYPE_LABELS: Record<number, { key: string; fallback: string }> = {
  1: { key: 'LBL_Education', fallback: 'Education' },
  2: { key: 'LBL_Certification', fallback: 'Certification' },
  3: { key: 'LBL_Work_Experience', fallback: 'Work experience' },
};

function youtubeEmbedUrl(link: string): string | null {
  const match = link.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([\w-]+)/i);
  return match ? `https://www.youtube.com/embed/${match[1]}` : null;
}

function groupQualifications(
  items: TeacherQualification[] | undefined,
  lbl: (key: string, fallback?: string) => string
) {
  const groups = new Map<number, TeacherQualification[]>();
  for (const item of items ?? []) {
    const list = groups.get(item.type) ?? [];
    list.push(item);
    groups.set(item.type, list);
  }
  return [...groups.entries()]
    .sort(([a], [b]) => a - b)
    .map(([type, qualifications]) => ({
      type,
      label: lbl(
        QUAL_TYPE_LABELS[type]?.key ?? 'LBL_QUALIFICATION',
        QUAL_TYPE_LABELS[type]?.fallback ?? 'Qualification'
      ),
      qualifications,
    }));
}

export function W3MentorsTeacherProfilePage() {
  const { slugOrId } = useParams();
  const { lbl } = useSite();
  const [teacher, setTeacher] = useState<TeacherProfile | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!slugOrId) return;
    setLoading(true);
    setError('');
    teachersApi
      .get(slugOrId)
      .then((res) => setTeacher(res.data.data))
      .catch(() => setError(lbl('LBL_Something_went_wrong', 'Teacher not found.')))
      .finally(() => setLoading(false));
  }, [slugOrId, lbl]);

  useEffect(() => {
    if (!teacher) return;
    return bindTeacherDetailPage();
  }, [teacher?.id]);

  useW3MentorsSliders([teacher?.id, teacher?.more_courses?.length ?? 0]);

  const qualificationGroups = useMemo(
    () => groupQualifications(teacher?.qualifications, lbl),
    [teacher?.qualifications, lbl]
  );

  const slots = teacher?.user_slots ?? [];
  const [pricingSlot, setPricingSlot] = useState<number>(0);

  useEffect(() => {
    if (slots.length) setPricingSlot(slots[0]);
  }, [teacher?.id, slots.join(',')]);

  const actions = useTeacherProfileActions(teacher, slugOrId ?? '');

  if (loading) return <W3MentorsPageMessage message={lbl('LBL_Loading', 'Loading...')} />;
  if (error || !teacher) return <W3MentorsPageMessage message={error || 'Not found'} error />;

  const totalSessions = teacher.lessons + teacher.classes;
  const videoSrc = teacher.video_link ? youtubeEmbedUrl(teacher.video_link) : null;
  const preSelectedSlot = slots[0] ?? 15;
  const activePricingSlot = pricingSlot || preSelectedSlot;
  const btnDisabled = actions.isSelf ? 'btn--disabled' : '';

  return (
    <section className="section section--profile">
      <div className="container container--fixed">
        <div className="profile-cover">
          <div className="profile-head">
            <div className="detail-wrapper">
              <div className="profile__media">
                <div
                  className="ratio ratio--3by4"
                  data-title={firstChar(teacher.first_name ?? teacher.full_name)}
                >
                  <span>
                    <img
                      src={imageUrl(AFILE.USER_PROFILE, teacher.id, 'MEDIUM')}
                      alt={teacher.full_name}
                    />
                  </span>
                </div>
              </div>
              <div className="profile-detail">
                <div className="profile-detail__head">
                  <div className="profile-detail__head-large">
                    <div className="tutor-name">
                      <h1>{teacher.full_name}</h1>
                      {teacher.is_featured && (
                        <div className="badge-secure is-hover">
                          <svg
                            className="icon icon--small icon--featured"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            width="12"
                            height="12"
                          >
                            <path
                              fillRule="evenodd"
                              d="M15.291 4.055 12 2 8.709 4.055l-3.78.874-.874 3.78L2 12l2.055 3.291.874 3.78 3.78.874L12 22l3.291-2.055 3.78-.874.874-3.78L22 12l-2.055-3.291-.874-3.78zM9.793 15.707l.707.707.707-.707 6-6-1.414-1.414-5.293 5.293-2.293-2.293-1.414 1.414z"
                              clipRule="evenodd"
                            />
                          </svg>
                          <div className="tooltip tooltip--top tooltip--round bg-black no-wrap">
                            {lbl('LBL_FEATURED', 'Featured')}
                          </div>
                        </div>
                      )}
                      <div className="ratings">
                        <SpriteIcon id="rating" className="icon icon--rating" />
                        <span className="value">{teacher.ratings}</span>
                        <span className="count">({teacher.reviews})</span>
                      </div>
                    </div>
                    <div className="info-wrapper mt-3">
                      {teacher.country && (
                        <div className="meta-info">
                          <SpriteIcon id="location" className="icon icon--xsmall" />
                          <span>{teacher.country}</span>
                        </div>
                      )}
                      <div className="meta-info">
                        <span className="value">{teacher.students}</span>
                        <span> {lbl('LBL_Students', 'Students')}</span>
                      </div>
                      <div className="meta-info">
                        <span className="value">{totalSessions}</span>
                        <span> {lbl('LBL_SESSIONS', 'Sessions')}</span>
                      </div>
                      {teacher.courses > 0 && (
                        <div className="meta-info">
                          <span className="value">{teacher.courses}</span>
                          <span> {lbl('LBL_COURSES', 'Courses')}</span>
                        </div>
                      )}
                    </div>
                  </div>
                  <div className="profile-detail__head-small">
                    <div className="detail-actions">
                      <button type="button" className="btn btn--bordered color-black" disabled>
                        <SpriteIcon id="heart" className="icon icon--heart icon--xsmall" />
                      </button>
                      <div className="social-share dropdown">
                        <button
                          type="button"
                          className="btn btn-equal dropdown-toggle"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <SpriteIcon id="share" className="icon icon--share icon--xsmall" />
                        </button>
                        <div className="dropdown-menu mt-1">
                          <h6>{lbl('LBL_SHARE_ON', 'Share on')}</h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="profile-detail__body">
                  <div className="har-rate mb-3">
                    {lbl('LBL_TEACHER_PRICING', 'Teacher pricing')}
                    <b>
                      {' '}
                      {formatMoney(teacher.min_price)} - {formatMoney(teacher.max_price)}
                    </b>
                  </div>
                  <div className="tutor-info">
                    {teacher.teach_languages && (
                      <div className="info-group">
                        <div className="info-group__head">
                          <h5>{lbl('LBL_TEACHES:', 'Teaches:')}</h5>
                        </div>
                        <div className="info-group__body">{teacher.teach_languages}</div>
                      </div>
                    )}
                    {teacher.speak_languages && (
                      <div className="info-group">
                        <div className="info-group__head">
                          <h5>{lbl('LBL_Speaks:', 'Speaks:')}</h5>
                        </div>
                        <div className="info-group__body">{teacher.speak_languages}</div>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="profile-primary">
            {teacher.biography && (
              <div className="panel-cover">
                <div className="panel-cover__head panel__head-trigger panel__head-trigger-js is-active">
                  <h3>
                    {lbl('LBL_About', 'About')} {teacher.full_name}
                  </h3>
                </div>
                <div
                  className="panel-cover__body panel__body-target panel__body-target-js"
                  style={{ display: 'block' }}
                >
                  <div className="content__row">
                    <p style={{ whiteSpace: 'pre-wrap' }}>{teacher.biography}</p>
                  </div>
                </div>
              </div>
            )}

            {teacher.pricing_languages && teacher.pricing_languages.length > 0 && slots.length > 0 && (
              <div className="panel-cover ">
                <div className="panel-cover__head panel__head-trigger panel__head-trigger-js">
                  <h3>{lbl('LBL_Pricing', 'Pricing')}</h3>
                </div>
                <div className="panel-cover__body panel__body-target panel__body-target-js">
                  <div className="table-md-scroll">
                    <div className="table--pricing">
                      <table className="table">
                        <thead>
                          <tr>
                            <th>{lbl('LBL_TEACHING_LANGUAGES', 'Teaching languages')}</th>
                            <th>
                              <select
                                key={`${teacher.id}-${slots.join(',')}`}
                                name="selected_slot"
                                id="selected_slot"
                                defaultValue={String(preSelectedSlot)}
                                onChange={(e) => {
                                  setPricingSlot(Number(e.target.value));
                                  changeTeacherPricingSlot();
                                }}
                              >
                                {slots.map((slot) => (
                                  <option key={slot} value={slot}>
                                    {slot}
                                  </option>
                                ))}
                              </select>
                            </th>
                          </tr>
                        </thead>
                        <tbody>
                          {teacher.pricing_languages.map((row) => (
                            <tr key={row.id}>
                              <td>{row.name}</td>
                              <td>
                                {slots.map((slot) => (
                                  <span
                                    key={slot}
                                    className={`cursor-pointer trigger-checkout slot-change-${slot}`}
                                    style={{
                                      display: preSelectedSlot === slot ? 'block' : 'none',
                                    }}
                                    onClick={() =>
                                      !actions.isSelf &&
                                      actions.openBookModal(row.id, slot)
                                    }
                                  >
                                    {formatMoney(row.prices[slot] ?? 0)}
                                  </span>
                                ))}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {qualificationGroups.length > 0 && (
              <div className="panel-cover">
                <div className="panel-cover__head panel__head-trigger panel__head-trigger-js">
                  <h3>{lbl('LBL_TEACHING_QUALIFICATIONS', 'Teaching qualifications')}</h3>
                </div>
                <div className="panel-cover__body panel__body-target panel__body-target-js" id="qualificationsList">
                  <div className="box-panel">
                    <div className="box-panel__head d-none d-md-block">
                      <div className="tabs js--tabs">
                        <ul>
                          {qualificationGroups.map((group, index) => (
                            <li key={group.type}>
                              <a
                                href={`#tab-0${index + 1}`}
                                className={index === 0 ? 'current' : ''}
                              >
                                {group.label}
                              </a>
                            </li>
                          ))}
                        </ul>
                      </div>
                    </div>
                    <div className="box-panel__body">
                      {qualificationGroups.map((group, index) => (
                        <div
                          key={group.type}
                          className={`row--resume${index === 0 ? ' visible' : ''}`}
                          id={`tab-0${index + 1}`}
                        >
                          <h6 className="d-md-none mb-2 text-uppercase">{group.label}</h6>
                          <div className="resume-wrapper">
                            {group.qualifications.map((q) => (
                              <div className="resume" key={q.id}>
                                <div className="resume__primary">
                                  {q.start_year} - {q.end_year}
                                </div>
                                <div className="resume__secondary">
                                  <b>{q.title}</b>
                                  {q.institute_name && <p className="m-0">{q.institute_name}</p>}
                                  {q.institute_address && <p className="m-0">{q.institute_address}</p>}
                                </div>
                              </div>
                            ))}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
            )}

            <div className="panel-cover" id="teacherAvailability">
              <div className="panel-cover__head panel__head-trigger panel__head-trigger-js">
                <h3>{lbl('LBL_TEACHER_AVAILABILITY', 'Teacher availability')}</h3>
              </div>
              <div className="panel-cover__body panel__body-target panel__body-target-js">
                <div className="box-panel">
                  <div className="box-panel__body">
                    <TeacherAvailabilityPanel
                      slugOrId={slugOrId ?? String(teacher.id)}
                      defaultDuration={activePricingSlot}
                    />
                  </div>
                </div>
              </div>
            </div>

            {teacher.more_courses && teacher.more_courses.length > 0 && (
              <div className="panel-cover">
                <div className="panel-cover__head panel__head-trigger panel__head-trigger-js">
                  <h3>{lbl('LBL_COURSES', 'Courses')}</h3>
                </div>
                <div className="panel-cover__body mt-xl-0 panel__body-target panel__body-target-js">
                  <div className="slider slider--onehalf slider-onehalf-js slick-slider">
                    {teacher.more_courses.map((c) => (
                      <ShortCourseCard
                        key={c.id}
                        course={{
                          id: c.id,
                          slug: c.slug,
                          title: c.title,
                          price: c.price,
                          ratings: c.ratings,
                          reviews: c.reviews,
                        }}
                        lbl={lbl}
                      />
                    ))}
                  </div>
                </div>
              </div>
            )}
          </div>

          <div className="profile-secondary">
            <div className="right-panel">
              <div className="box box--book">
                {videoSrc && (
                  <div className="dummy-video mb-4">
                    <div className="video-media ratio ratio--16by9">
                      <iframe
                        width="100%"
                        height="100%"
                        src={videoSrc}
                        title={teacher.full_name}
                        frameBorder={0}
                        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                        allowFullScreen
                      />
                    </div>
                  </div>
                )}
                <div className="book__actions pt-2">
                  <button
                    type="button"
                    className={`btn btn--primary btn--xlarge btn--block color-white ${btnDisabled}`}
                    disabled={actions.isSelf}
                    onClick={() => actions.openBookModal()}
                  >
                    {lbl('LBL_Book_Now', 'Book now')}
                  </button>
                  <button
                    type="button"
                    className="btn btn--primary btn--xlarge btn--block"
                    onClick={actions.scrollToAvailability}
                  >
                    {lbl('LBL_VIEW_FULL_AVAILBILITY', 'View full availability')}
                  </button>
                  <button
                    type="button"
                    className={`btn btn--primary-bordered btn--xlarge btn--block ${btnDisabled}`}
                    disabled={actions.isSelf}
                    onClick={actions.openContact}
                  >
                    <SpriteIcon id="envelope" className="icon icon--envelope" />
                    {lbl('LBL_CONTACT', 'Contact')}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
