import { useCallback, useEffect, useState } from 'react';
import { Navigate } from 'react-router-dom';
import { api, type Paginated } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { useDashboardRole } from '../DashboardShell';
import { dashboardPath } from '../dashboardPaths';
import { DashboardAddIcon } from '../components/DashboardAddIcon';
import { DashboardFlexCell } from '../components/DashboardFlexCell';
import { DashboardListingPagination } from '../components/DashboardListingPagination';
import { DashboardManagePageHead } from '../components/DashboardManagePageHead';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { DashboardSearchToggle } from '../components/DashboardSearchToggle';
import { DashboardStatusSwitch } from '../components/DashboardStatusSwitch';
import { formatLegacyDateTime } from '../quiz/quizFormat';

type CategoryOption = { id: number; name: string };

type QuestionRow = {
  id: number;
  title: string;
  type: number;
  type_label: string;
  category_name: string;
  subcategory_name: string;
  status: number;
  is_active: boolean;
  created_at: string | null;
};

const QUESTION_TYPES = [
  { value: '', labelKey: 'LBL_SELECT', fallback: 'Select' },
  { value: '1', labelKey: 'LBL_SINGLE_CHOICE', fallback: 'Single choice' },
  { value: '2', labelKey: 'LBL_MULTIPLE_CHOICE', fallback: 'Multiple choice' },
  { value: '3', labelKey: 'LBL_TEXT', fallback: 'Text' },
] as const;

/** Legacy dashboard/views/questions/index.php + search.php */
export function DashboardQuestionsPage() {
  const role = useDashboardRole();
  const { lbl } = useSite();
  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [subcategoryId, setSubcategoryId] = useState('');
  const [type, setType] = useState('');
  const [categories, setCategories] = useState<CategoryOption[]>([]);
  const [subcategories, setSubcategories] = useState<CategoryOption[]>([]);
  const [rows, setRows] = useState<QuestionRow[]>([]);
  const [meta, setMeta] = useState<Paginated<unknown>['meta'] | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  const loadCategories = useCallback((parentId = 0) => {
    return api
      .get<{ data: CategoryOption[] }>('/dashboard/questions/categories', {
        params: { parent_id: parentId },
      })
      .then((res) => res.data.data);
  }, []);

  useEffect(() => {
    loadCategories(0).then(setCategories).catch(() => setCategories([]));
  }, [loadCategories]);

  useEffect(() => {
    if (!categoryId) {
      setSubcategories([]);
      setSubcategoryId('');
      return;
    }
    loadCategories(Number(categoryId))
      .then(setSubcategories)
      .catch(() => setSubcategories([]));
  }, [categoryId, loadCategories]);

  const load = useCallback(() => {
    if (role !== 'teacher') return;
    setLoading(true);
    api
      .get<{ data: QuestionRow[]; meta: Paginated<unknown>['meta'] }>('/dashboard/questions', {
        params: {
          page,
          keyword: keyword || undefined,
          category_id: categoryId || undefined,
          subcategory_id: subcategoryId || undefined,
          type: type || undefined,
        },
      })
      .then((res) => {
        setRows(res.data.data);
        setMeta(res.data.meta);
      })
      .catch(() => {
        setRows([]);
        setMeta(null);
      })
      .finally(() => setLoading(false));
  }, [role, page, keyword, categoryId, subcategoryId, type]);

  useEffect(() => {
    load();
  }, [load]);

  if (role !== 'teacher') {
    return <Navigate to={dashboardPath('learner')} replace />;
  }

  const toggleStatus = async (row: QuestionRow) => {
    if (!window.confirm(lbl('LBL_CONFIRM_UPDATE_STATUS', 'Update status?'))) return;
    try {
      await api.patch(`/dashboard/questions/${row.id}/status`, { status: row.status });
      load();
    } catch {
      window.alert(lbl('LBL_Something_went_wrong', 'Something went wrong.'));
    }
  };

  const removeQuestion = async (id: number) => {
    if (!window.confirm(lbl('LBL_DO_YOU_WANT_TO_REMOVE', 'Do you want to remove?'))) return;
    try {
      await api.delete(`/dashboard/questions/${id}`);
      load();
    } catch {
      window.alert(lbl('LBL_Something_went_wrong', 'Something went wrong.'));
    }
  };

  return (
    <div className="container container--fixed">
      <DashboardManagePageHead
        title={lbl('LBL_QUESTION_BANK', 'Question bank')}
        searchOpen={searchOpen}
        searchToggle={<DashboardSearchToggle onClick={() => setSearchOpen((v) => !v)} />}
        actions={
          <button type="button" className="btn color-secondary btn--bordered" disabled>
            <DashboardAddIcon />
            {lbl('LBL_ADD_QUESTION', 'Add question')}
          </button>
        }
        searchPanel={
          <form
            className="form form--small"
            onSubmit={(e) => {
              e.preventDefault();
              setPage(1);
              load();
            }}
          >
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
                    <label className="field_label">{lbl('LBL_CATEGORY', 'Category')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={categoryId}
                        onChange={(e) => {
                          setCategoryId(e.target.value);
                          setSubcategoryId('');
                        }}
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        {categories.map((c) => (
                          <option key={c.id} value={c.id}>
                            {c.name}
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
                    <label className="field_label">{lbl('LBL_SUBCATEGORY', 'Subcategory')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        className="form-control"
                        value={subcategoryId}
                        onChange={(e) => setSubcategoryId(e.target.value)}
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        {subcategories.map((c) => (
                          <option key={c.id} value={c.id}>
                            {c.name}
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
                    <label className="field_label">{lbl('LBL_TYPE', 'Type')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select className="form-control" value={type} onChange={(e) => setType(e.target.value)}>
                        {QUESTION_TYPES.map((opt) => (
                          <option key={opt.value || 'all'} value={opt.value}>
                            {lbl(opt.labelKey, opt.fallback)}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-lg-4 col-sm-6 form-buttons-group">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label" />
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input type="submit" className="btn btn--primary" value={lbl('LBL_SEARCH', 'Search')} />
                      <input
                        type="button"
                        className="btn btn--secondary ms-2"
                        value={lbl('LBL_CLEAR', 'Clear')}
                        onClick={() => {
                          setKeyword('');
                          setCategoryId('');
                          setSubcategoryId('');
                          setType('');
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
        <div className="page-content" id="listing">
          {loading ? (
            <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
          ) : rows.length === 0 ? (
            <DashboardNoRecord />
          ) : (
            <div className="table-scroll">
              <table className="table table--styled table--responsive table--aligned-middle">
                <tr className="title-row">
                  <th>{lbl('LBL_TITLE', 'Title')}</th>
                  <th>{lbl('LBL_TYPE', 'Type')}</th>
                  <th>{lbl('LBL_CATEGORY', 'Category')}</th>
                  <th>{lbl('LBL_SUBCATEGORY', 'Subcategory')}</th>
                  <th>{lbl('LBL_STATUS', 'Status')}</th>
                  <th>{lbl('LBL_ADDED_ON', 'Added on')}</th>
                  <th>{lbl('LBL_ACTIONS', 'Actions')}</th>
                </tr>
                {rows.map((row) => (
                  <tr key={row.id}>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_TITLE', 'Title')}>{row.title}</DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_TYPE', 'Type')}>
                        {row.type_label || row.type}
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_CATEGORY', 'Category')}>
                        {row.category_name || lbl('LBL_N/A', 'N/A')}
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_SUBCATEGORY', 'Subcategory')}>
                        {row.subcategory_name || '—'}
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_STATUS', 'Status')}>
                        <DashboardStatusSwitch
                          checked={row.is_active}
                          value={row.status}
                          onChange={() => toggleStatus(row)}
                        />
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_ADDED_ON', 'Added on')}>
                        {formatLegacyDateTime(row.created_at)}
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_ACTIONS', 'Actions')}>
                        <div className="actions-group">
                          <button
                            type="button"
                            className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                            disabled
                            title={lbl('LBL_EDIT', 'Edit')}
                          >
                            <DashboardSpriteIcon id="edit" className="icon icon--edit icon--small" />
                            <div className="tooltip tooltip--top bg-black">{lbl('LBL_EDIT', 'Edit')}</div>
                          </button>
                          <button
                            type="button"
                            className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                            onClick={() => removeQuestion(row.id)}
                          >
                            <DashboardSpriteIcon id="trash" className="icon icon--issue icon--small" />
                            <div className="tooltip tooltip--top bg-black">{lbl('LBL_DELETE', 'Delete')}</div>
                          </button>
                        </div>
                      </DashboardFlexCell>
                    </td>
                  </tr>
                ))}
              </table>
            </div>
          )}
          <DashboardListingPagination meta={meta} page={page} loading={loading} onPageChange={setPage} />
        </div>
      </div>
    </div>
  );
}
