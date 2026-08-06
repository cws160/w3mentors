import { useCallback, useEffect, useState } from 'react';
import { api } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { DashboardFlexCell } from '../components/DashboardFlexCell';
type QuestionOption = { id: number; title: string; type_label: string; category_name: string; subcategory_name: string };
type CategoryOption = { id: number; name: string };

type Props = {
  quizId: number;
  onClose: () => void;
  onAttached: () => void;
};

/** Legacy dashboard/views/quiz-questions/index.php + search.php */
export function QuizAttachQuestionsModal({ quizId, onClose, onAttached }: Props) {
  const { lbl } = useSite();
  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [subcategoryId, setSubcategoryId] = useState('');
  const [categories, setCategories] = useState<CategoryOption[]>([]);
  const [subcategories, setSubcategories] = useState<CategoryOption[]>([]);
  const [rows, setRows] = useState<QuestionOption[]>([]);
  const [selected, setSelected] = useState<Set<number>>(new Set());
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const loadCategories = useCallback((parentId = 0) => {
    api
      .get<{ data: CategoryOption[] }>('/dashboard/questions/categories', { params: { parent_id: parentId } })
      .then((res) => {
        if (parentId === 0) setCategories(res.data.data);
        else setSubcategories(res.data.data);
      })
      .catch(() => {
        if (parentId === 0) setCategories([]);
        else setSubcategories([]);
      });
  }, []);

  const search = useCallback(
    (nextPage = 1, append = false) => {
      setLoading(true);
      api
        .get<{ data: QuestionOption[]; meta: { last_page: number } }>(
          `/dashboard/quizzes/${quizId}/questions/search`,
          {
            params: {
              page: nextPage,
              per_page: 10,
              keyword: keyword || undefined,
              category_id: categoryId || undefined,
              subcategory_id: subcategoryId || undefined,
            },
          }
        )
        .then((res) => {
          setRows((prev) => (append ? [...prev, ...res.data.data] : res.data.data));
          setLastPage(res.data.meta.last_page);
          setPage(nextPage);
        })
        .catch(() => {
          if (!append) setRows([]);
        })
        .finally(() => setLoading(false));
    },
    [quizId, keyword, categoryId, subcategoryId]
  );

  useEffect(() => {
    loadCategories(0);
    search(1, false);
  }, [loadCategories, search]);

  useEffect(() => {
    if (categoryId) loadCategories(Number(categoryId));
    else setSubcategories([]);
  }, [categoryId, loadCategories]);

  const toggleAll = (checked: boolean) => {
    if (checked) setSelected(new Set(rows.map((r) => r.id)));
    else setSelected(new Set());
  };

  const toggleOne = (id: number, checked: boolean) => {
    setSelected((prev) => {
      const next = new Set(prev);
      if (checked) next.add(id);
      else next.delete(id);
      return next;
    });
  };

  const attach = async () => {
    if (selected.size === 0) {
      setError(lbl('LBL_PLEASE_SELECT_QUESTION(S)', 'Please select question(s)'));
      return;
    }
    setSaving(true);
    setError('');
    try {
      await api.post(`/dashboard/quizzes/${quizId}/questions`, {
        question_ids: Array.from(selected),
      });
      onAttached();
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_Something_went_wrong', 'Something went wrong.');
      setError(msg);
    } finally {
      setSaving(false);
    }
  };

  const clearSearch = () => {
    setKeyword('');
    setCategoryId('');
    setSubcategoryId('');
    search(1, false);
  };

  return (
    <>
      <div className="modal-header">
        <h5 className="flex-1">{lbl('LBL_ATTACH_QUESTIONS', 'Attach questions')}</h5>
        <div>
          <button
            type="button"
            className="btn btn--secondary qsearch-toggle-js m-1"
            onClick={() => setSearchOpen((v) => !v)}
          >
            <DashboardSpriteIcon id="search" className="icon icon--clock icon--small me-2" />
            {lbl('LBL_SEARCH', 'Search')}
          </button>
          <button type="button" className="btn btn--bordered color-secondary m-1" disabled={saving} onClick={attach}>
            <svg className="icon icon--add icon--small me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
              <path d="M11 11V7h2v4h4v2h-4v4h-2v-4H7v-2h4zm1 11C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16z" />
            </svg>
            {lbl('LBL_ATTACH', 'Attach')}
          </button>
        </div>
        <button type="button" className="btn-close w3mentorsmodalJs" aria-label="Close" onClick={onClose} />
      </div>
      <div className="modal-body p-0">
        <div className="form-edit-head">
          <div className="qsearch-target-js" style={{ display: searchOpen ? 'block' : 'none' }}>
            <div className="form-search mt-4">
              <form
                className="form form--small"
                onSubmit={(e) => {
                  e.preventDefault();
                  search(1, false);
                }}
              >
                <div className="form-search__field">
                  <div className="row">
                    <div className="col-lg-4 col-sm-6">
                      <div className="field-set">
                        <div className="caption-wraper">
                          <label className="field_label">{lbl('LBL_KEYWORD', 'Keyword')}</label>
                        </div>
                        <div className="field-wraper">
                          <div className="field_cover">
                            <input
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
                          <label className="field_label">{lbl('LBL_SUB_CATEGORY', 'Sub category')}</label>
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
                          <label className="field_label" />
                        </div>
                        <div className="field-wraper form-buttons-group">
                          <div className="field_cover">
                            <input type="submit" className="btn btn--primary" value={lbl('LBL_SEARCH', 'Search')} />
                            <input
                              type="button"
                              className="btn btn--secondary ms-2"
                              value={lbl('LBL_CLEAR', 'Clear')}
                              onClick={clearSearch}
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
        {error && <p className="text-danger small px-4 pt-3 mb-0">{error}</p>}
        <div className="form-edit-body">
          <div className="table-scroll">
            <table className="table table--responsive table--bordered">
              <thead>
                <tr className="title-row">
                  <th>
                    <label className="checkbox">
                      <input
                        type="checkbox"
                        checked={rows.length > 0 && selected.size === rows.length}
                        onChange={(e) => toggleAll(e.target.checked)}
                      />
                      <i className="input-helper" />
                    </label>
                  </th>
                  <th>{lbl('LBL_TITLE', 'Title')}</th>
                  <th>{lbl('LBL_TYPE', 'Type')}</th>
                  <th>{lbl('LBL_CATEGORY', 'Category')}</th>
                  <th>{lbl('LBL_SUB_CATEGORY', 'Sub category')}</th>
                </tr>
              </thead>
              <tbody id="listingJs">
                {loading && rows.length === 0 ? (
                  <tr>
                    <td colSpan={5}>{lbl('LBL_LOADING', 'Loading...')}</td>
                  </tr>
                ) : rows.length === 0 ? (
                  <tr>
                    <td colSpan={5}>{lbl('LBL_NO_RECORD_FOUND', 'No record found')}</td>
                  </tr>
                ) : (
                  rows.map((row) => (
                    <tr key={row.id}>
                      <td>
                        <label className="checkbox">
                          <input
                            type="checkbox"
                            checked={selected.has(row.id)}
                            onChange={(e) => toggleOne(row.id, e.target.checked)}
                          />
                          <i className="input-helper" />
                        </label>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_TITLE', 'Title')}>
                          <p className="mb-1 bold-600 color-black">{row.title}</p>
                        </DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_TYPE', 'Type')}>{row.type_label}</DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_CATEGORY', 'Category')}>{row.category_name}</DashboardFlexCell>
                      </td>
                      <td>
                        <DashboardFlexCell label={lbl('LBL_SUB_CATEGORY', 'Sub category')}>
                          {row.subcategory_name}
                        </DashboardFlexCell>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
            {page < lastPage && (
              <div className="show-more-container loadMoreJs padding-6">
                <div className="show-more d-flex justify-content-center">
                  <button
                    type="button"
                    className="btn btn--primary-bordered"
                    disabled={loading}
                    onClick={() => search(page + 1, true)}
                  >
                    {lbl('LBL_SHOW_MORE', 'Show more')}
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
