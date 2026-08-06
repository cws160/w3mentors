import { useCallback, useEffect, useState } from 'react';
import { Navigate, useNavigate } from 'react-router-dom';
import { chatsApi, dashboardApi, type DashboardStudent, type Paginated } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useModal } from '../../context/ModalContext';
import { useSite } from '../../context/SiteContext';
import { AFILE, firstChar, imageUrl } from '../../utils/assets';
import { useDashboardRole } from '../DashboardShell';
import { dashboardPath } from '../dashboardPaths';
import { DashboardFlexCell } from '../components/DashboardFlexCell';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardManagePageHead } from '../components/DashboardManagePageHead';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { DashboardOfferIcon } from '../components/DashboardOfferIcon';
import { DashboardSearchToggle } from '../components/DashboardSearchToggle';
import { ContactUserModalContent } from '../students/ContactUserModalContent';
import { StudentOfferModal } from '../students/StudentOfferModal';

type OfferDuration = { duration: number; offer: number };

function parseOffers(json: string | undefined): OfferDuration[] {
  if (!json) return [];
  try {
    const parsed = JSON.parse(json) as unknown;
    return Array.isArray(parsed) ? (parsed as OfferDuration[]) : [];
  } catch {
    return [];
  }
}

function offerLabel(duration: number, offer: number, template: string): string {
  return template
    .replace('{duration}', String(duration))
    .replace('{percentages}', offer.toFixed(2));
}

function OfferItems({
  offers,
  template,
  na,
}: {
  offers: OfferDuration[];
  template: string;
  na: string;
}) {
  if (offers.length === 0) return <>{na}</>;

  return (
    <div className="offers-box__group">
      {offers.map((o) => (
        <span key={`${o.duration}-${o.offer}`} className="offers-box__item">
          <span className="offers-box__item-media me-2">
            <DashboardOfferIcon />
          </span>
          <span className="offers-box__item-label">{offerLabel(o.duration, o.offer, template)}</span>
        </span>
      ))}
    </div>
  );
}

/** Legacy dashboard/views/students/index.php + search.php */
export function DashboardStudentsPage() {
  const role = useDashboardRole();
  const navigate = useNavigate();
  const { lbl } = useSite();
  const { showModal, closeModal } = useModal();
  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [students, setStudents] = useState<DashboardStudent[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    if (role !== 'teacher') return;
    setLoading(true);
    dashboardApi
      .students({ keyword: keyword || undefined, page })
      .then((res) => {
        setStudents(res.data.data);
        setMeta(res.data.meta);
      })
      .catch(() => {
        setStudents([]);
        setMeta(null);
      })
      .finally(() => setLoading(false));
  }, [keyword, page, role]);

  useEffect(() => {
    load();
  }, [load]);

  if (role !== 'teacher') {
    return <Navigate to={dashboardPath('learner')} replace />;
  }

  const onSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setPage(1);
    load();
  };

  const openChats = (threadId: number) => {
    navigate(`${dashboardPath('teacher', 'chats')}?thread_id=${threadId}`);
  };

  const openMessage = async (student: DashboardStudent) => {
    const showCompose = () => {
      showModal(
        <ContactUserModalContent
          receiverId={student.id}
          receiverName={student.full_name}
          title={lbl('LBL_Send_Message', 'Send message')}
          onSent={openChats}
        />,
        { size: 'modal-lg' }
      );
    };

    try {
      const res = await chatsApi.privateThread(student.id);
      const data = res.data.data;
      if (data.thread_id) {
        openChats(data.thread_id);
        return;
      }
      if (data.needs_message) {
        showCompose();
      }
    } catch {
      showCompose();
    }
  };

  const openOffer = (student: DashboardStudent) => {
    showModal(
      <StudentOfferModal learnerId={student.id} onClose={closeModal} onSaved={load} />,
      { size: 'modal-lg' }
    );
  };

  const lessonOfferTpl = lbl(
    'LBL_{percentages}%_OFF_ON_{duration}_MINUTES_SESSION',
    '{percentages}% off on {duration} minutes session'
  );
  const packageOfferTpl = lbl('LBL_{percentages}%_OFF', '{percentages}% off');
  const na = lbl('LBL_NA', 'N/A');

  return (
    <div className="container container--fixed">
      <DashboardManagePageHead
        title={lbl('LBL_MY_STUDENTS', 'My students')}
        searchOpen={searchOpen}
        searchToggle={<DashboardSearchToggle onClick={() => setSearchOpen((v) => !v)} />}
        searchPanel={
          <form className="form form--small" onSubmit={onSearch}>
            <div className="row">
              <div className="col-lg-4 col-sm-6">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_KEYWORD', 'Keyword')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="text"
                        className="form-control"
                        placeholder={lbl('LBL_Search_By_Keyword', 'Search by keyword')}
                        value={keyword}
                        onChange={(e) => setKeyword(e.target.value)}
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-lg-4 col-sm-6">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label" />
                  </div>
                  <div className="field-wraper form-buttons-group">
                    <div className="field_cover">
                      <input type="submit" className="btn btn--primary" value={lbl('LBL_SEARCH', 'Search')} />
                      <input
                        type="button"
                        className="btn btn--secondary ms-2"
                        value={lbl('LBL_CLEAR', 'Clear')}
                        onClick={() => {
                          setKeyword('');
                          setPage(1);
                        }}
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        }
      />
      <div className="page__body">
        <div className="page-content" id="listItems">
          {loading ? (
            <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
          ) : students.length === 0 ? (
            <DashboardNoRecord labelKey="LBL_NO_STUDENTS_FOUND" labelFallback="No students found" />
          ) : (
            <div className="table-scroll">
              <table className="table table--styled table--responsive table--aligned-middle">
                <tr className="title-row">
                  <th>{lbl('LBL_LEARNER', 'Learner')}</th>
                  <th>{lbl('LBL_LESSONS', 'Lessons')}</th>
                  <th>{lbl('LBL_CLASSES', 'Classes')}</th>
                  <th>{lbl('LBL_LESSONS_OFFER', 'Lessons offer')}</th>
                  <th>{lbl('LBL_CLASSES_OFFER', 'Classes offer')}</th>
                  <th>{lbl('LBL_PACKAGE_OFFER', 'Package offer')}</th>
                  <th>{lbl('LBL_ACTIONS', 'Actions')}</th>
                </tr>
                {students.map((s) => {
                  const lessonOffers = parseOffers(s.lesson_price_json);
                  const classOffers = parseOffers(s.class_price_json);

                  return (
                    <tr key={s.id}>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_LEARNER', 'Learner')}>
                          <div className="profile-meta">
                            <div className="profile-meta__media">
                              <span
                                className="avtar avtar--medium avtar--round"
                                data-title={firstChar(s.full_name)}
                              >
                                <img src={imageUrl(AFILE.USER_PROFILE, s.id, 'SMALL')} alt={s.full_name} />
                              </span>
                            </div>
                            <div className="profile-meta__details">
                              <p className="bold-600 color-black m-0">{s.full_name}</p>
                            </div>
                          </div>
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_LESSONS', 'Lessons')}>
                          {s.lessons_offered ?? 0}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_CLASSES', 'Classes')}>
                          {s.classes_offered ?? 0}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_LESSONS_OFFER', 'Lessons offer')}>
                          <OfferItems offers={lessonOffers} template={lessonOfferTpl} na={na} />
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_CLASSES_OFFER', 'Classes offer')}>
                          <OfferItems offers={classOffers} template={lessonOfferTpl} na={na} />
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_PACKAGE_OFFER', 'Package offer')}>
                          {s.package_price != null && s.package_price > 0 ? (
                            <div className="offers-box__group">
                              <span className="offers-box__item">
                                <span className="offers-box__item-media me-2">
                                  <DashboardOfferIcon />
                                </span>
                                <span className="offers-box__item-label">
                                  {offerLabel(0, s.package_price, packageOfferTpl)}
                                </span>
                              </span>
                            </div>
                          ) : (
                            na
                          )}
                        </DashboardFlexCell>
                      </td>
                      <td>
                        {!s.learner_deleted && (
                          <DashboardFlexCell label={lbl('LBL_ACTIONS', 'Actions')}>
                            <div className="actions-group">
                              <button
                                type="button"
                                className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                                onClick={() => openMessage(s)}
                              >
                                <DashboardSpriteIcon id="message" className="icon icon--messaging" />
                                <div className="tooltip tooltip--top bg-black">
                                  {lbl('LBL_Message', 'Message')}
                                </div>
                              </button>
                              <button
                                type="button"
                                className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                                onClick={() => openOffer(s)}
                              >
                                <DashboardSpriteIcon id="offer" className="icon icon--offer" />
                                <div className="tooltip tooltip--top bg-black">
                                  {lbl('LBL_OFFER_PRICE', 'Offer price')}
                                </div>
                              </button>
                            </div>
                          </DashboardFlexCell>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </table>
            </div>
          )}
          <DashboardListingPagination meta={meta} page={page} loading={loading} onPageChange={setPage} />
        </div>
      </div>
    </div>
  );
}
