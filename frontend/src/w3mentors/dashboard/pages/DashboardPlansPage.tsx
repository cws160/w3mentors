import { useCallback, useEffect, useState } from 'react';
import { api, dashboardApi, type Paginated } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import { useModal } from '../../context/ModalContext';
import { DashboardAddIcon } from '../components/DashboardAddIcon';
import { DashboardFlexCell } from '../components/DashboardFlexCell';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardManagePageHead } from '../components/DashboardManagePageHead';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { DashboardSearchToggle } from '../components/DashboardSearchToggle';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { LessonPlanModal } from '../plans/LessonPlanModal';

type PlanRow = {
  id: number;
  title: string;
  detail: string;
  level: number;
  level_label: string;
};

const PLAN_LEVELS = [
  { value: '', labelKey: 'LBL_ALL', fallback: 'All' },
  { value: '1', labelKey: 'LBL_BEGINNER', fallback: 'Beginner' },
  { value: '2', labelKey: 'LBL_UPPER_BEGINNER', fallback: 'Upper beginner' },
  { value: '3', labelKey: 'LBL_INTERMEDIATE', fallback: 'Intermediate' },
  { value: '4', labelKey: 'LBL_UPPER_INTERMEDIATE', fallback: 'Upper intermediate' },
  { value: '5', labelKey: 'LBL_ADVANCED', fallback: 'Advanced' },
];

export function DashboardPlansPage() {
  const { lbl } = useSite();
  const { showModal, closeModal } = useModal();
  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [level, setLevel] = useState('');
  const [rows, setRows] = useState<PlanRow[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    setLoading(true);
    dashboardApi
      .list('plans', {
        page,
        keyword: keyword || undefined,
        level: level || undefined,
      })
      .then((res) => {
        setRows(res.data.data as unknown as PlanRow[]);
        setMeta(res.data.meta);
      })
      .catch(() => {
        setRows([]);
        setMeta(null);
      })
      .finally(() => setLoading(false));
  }, [page, keyword, level]);

  useEffect(() => {
    load();
  }, [load]);

  const openPlanForm = (planId: number) => {
    showModal(
      <LessonPlanModal planId={planId} onClose={closeModal} onSaved={load} />,
      { size: 'modal-lg' }
    );
  };

  const removePlan = async (id: number) => {
    if (!window.confirm(lbl('LBL_DELETE_PLAN_CONFIRM', 'Delete this lesson plan?'))) {
      return;
    }
    try {
      await api.delete(`/dashboard/plans/${id}`);
      load();
    } catch {
      window.alert(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    }
  };

  const onSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setPage(1);
    load();
  };

  return (
    <div className="container container--fixed">
      <DashboardManagePageHead
        title={lbl('LBL_Manage_Lessons_Plans', 'Manage lesson plans')}
        searchOpen={searchOpen}
        searchToggle={<DashboardSearchToggle onClick={() => setSearchOpen((v) => !v)} />}
        actions={
          <button
            type="button"
            className="btn color-secondary btn--bordered"
            onClick={() => openPlanForm(0)}
          >
            <DashboardAddIcon />
            {lbl('LBL_ADD_PLAN', 'Add plan')}
          </button>
        }
        searchPanel={
          <form className="form" id="planSearchFrm" onSubmit={onSearch}>
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
                        name="keyword"
                        className="form-control"
                        placeholder={lbl('LBL_KEYWORD', 'Keyword')}
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
                    <label className="field_label">{lbl('LBL_Level', 'Level')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        name="plan_level"
                        className="form-control"
                        value={level}
                        onChange={(e) => setLevel(e.target.value)}
                      >
                        {PLAN_LEVELS.map((opt) => (
                          <option key={opt.value || 'all'} value={opt.value}>
                            {lbl(opt.labelKey, opt.fallback)}
                          </option>
                        ))}
                      </select>
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
                        value={lbl('LBL_RESET', 'Reset')}
                        onClick={() => {
                          setKeyword('');
                          setLevel('');
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
        <div className="page-content plan-listing" id="listing">
          {loading ? (
            <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
          ) : rows.length === 0 ? (
            <DashboardNoRecord />
          ) : (
            <div className="table-scroll">
              <table className="table table--styled table--responsive">
                <tbody>
                  <tr className="title-row">
                    <th>{lbl('LBL_Title', 'Title')}</th>
                    <th>{lbl('LBL_Description', 'Description')}</th>
                    <th>{lbl('LBL_Level', 'Level')}</th>
                    <th>{lbl('LBL_ACTIONS', 'Actions')}</th>
                  </tr>
                  {rows.map((plan) => (
                    <tr key={plan.id}>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_Title', 'Title')}>
                          <div style={{ maxWidth: 250 }}>
                            <span className="bold-600">{plan.title}</span>
                          </div>
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_Description', 'Description')}>
                          <div style={{ maxWidth: 250 }}>{plan.detail}</div>
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_Level', 'Level')}>
                          <span className="badge color-secondary badge--curve">
                            {plan.level_label || plan.level}
                          </span>
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_ACTIONS', 'Actions')}>
                          <div className="actions-group">
                            <button
                              type="button"
                              className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                              title={lbl('LBL_EDIT', 'Edit')}
                              onClick={() => openPlanForm(plan.id)}
                            >
                              <DashboardSpriteIcon id="edit" className="icon icon--issue icon--small" />
                            </button>
                            <button
                              type="button"
                              className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                              title={lbl('LBL_DELETE', 'Delete')}
                              onClick={() => removePlan(plan.id)}
                            >
                              <DashboardSpriteIcon id="trash" className="icon icon--issue icon--small" />
                            </button>
                          </div>
                        </DashboardFlexCell>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
          <DashboardListingPagination meta={meta} page={page} loading={loading} onPageChange={setPage} />
        </div>
      </div>
    </div>
  );
}
