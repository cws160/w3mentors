import { useCallback, useEffect, useState } from 'react';
import { api } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { DashboardAddIcon } from '../components/DashboardAddIcon';
import { DashboardNoRecord } from '../components/DashboardNoRecord';

type QuizRow = {
  id: number;
  title: string;
  type: number;
  type_label: string;
};

type SearchResponse = {
  data: QuizRow[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  quiz_types: Record<string, string>;
};

type Props = {
  recordId: number;
  recordType: number;
  onClose: () => void;
  onAttached: () => void;
};

export function AttachQuizzesModal({ recordId, recordType, onClose, onAttached }: Props) {
  const { lbl } = useSite();
  const [searchOpen, setSearchOpen] = useState(false);
  const [keyword, setKeyword] = useState('');
  const [quizType, setQuizType] = useState('');
  const [quizzes, setQuizzes] = useState<QuizRow[]>([]);
  const [quizTypes, setQuizTypes] = useState<Record<string, string>>({});
  const [selected, setSelected] = useState<Set<number>>(new Set());
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [attaching, setAttaching] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(
    (nextPage: number, resetList: boolean) => {
      setLoading(true);
      setError(null);
      const params: Record<string, string | number> = {
        record_id: recordId,
        record_type: recordType,
        page: nextPage,
        per_page: 10,
      };
      if (keyword.trim()) params.keyword = keyword.trim();
      if (quizType !== '') params.quiz_type = Number(quizType);

      api
        .get<SearchResponse>('/dashboard/attach-quizzes/search', { params })
        .then((res) => {
          const items = res.data.data ?? [];
          setQuizzes((prev) => (resetList ? items : [...prev, ...items]));
          setQuizTypes(res.data.quiz_types ?? {});
          setPage(res.data.meta.current_page);
          setLastPage(res.data.meta.last_page);
        })
        .catch((err: { response?: { data?: { message?: string } } }) => {
          setError(err.response?.data?.message ?? lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
          if (resetList) setQuizzes([]);
        })
        .finally(() => setLoading(false));
    },
    [recordId, recordType, keyword, quizType, lbl]
  );

  useEffect(() => {
    load(1, true);
  }, [load]);

  const toggleAll = (checked: boolean) => {
    if (checked) {
      setSelected(new Set(quizzes.map((q) => q.id)));
    } else {
      setSelected(new Set());
    }
  };

  const toggleOne = (id: number, checked: boolean) => {
    setSelected((prev) => {
      const next = new Set(prev);
      if (checked) next.add(id);
      else next.delete(id);
      return next;
    });
  };

  const onSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setSelected(new Set());
    load(1, true);
  };

  const clearSearch = () => {
    setKeyword('');
    setQuizType('');
    setSelected(new Set());
    setTimeout(() => load(1, true), 0);
  };

  const attachSelected = async () => {
    if (selected.size === 0) {
      setError(lbl('LBL_PLEASE_SELECT_QUIZ', 'Please select a quiz'));
      return;
    }
    setAttaching(true);
    setError(null);
    try {
      await api.post('/dashboard/attach-quizzes', {
        record_id: recordId,
        record_type: recordType,
        quiz_ids: Array.from(selected),
      });
      onAttached();
      onClose();
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(msg);
    } finally {
      setAttaching(false);
    }
  };

  const allChecked = quizzes.length > 0 && selected.size === quizzes.length;

  return (
    <>
      <div className="modal-header">
        <h5 className="flex-1">{lbl('LBL_ATTACH_QUIZZES', 'Attach Quizzes')}</h5>
        <div className="d-flex align-items-center justify-content-between">
          <div>
            <div className="buttons-group d-flex align-items-center gap-2">
              <button
                type="button"
                className="btn btn--secondary qsearch-toggle-js"
                onClick={() => setSearchOpen((v) => !v)}
              >
                <DashboardSpriteIcon id="search" className="icon icon--search icon--small me-2" />
                {lbl('LBL_SEARCH', 'Search')}
              </button>
              <button
                type="button"
                className="btn btn--bordered color-secondary"
                onClick={attachSelected}
                disabled={attaching}
              >
                <DashboardAddIcon />
                {lbl('LBL_ATTACH', 'Attach')}
              </button>
            </div>
          </div>
        </div>
        <button
          type="button"
          className="btn-close w3mentorsmodalJs"
          data-bs-dismiss="modal"
          aria-label=""
          onClick={onClose}
        />
      </div>
      <div className="modal-body p-0">
        <div className="form-edit-head border-0">
          <div className="qsearch-target-js" style={searchOpen ? undefined : { display: 'none' }}>
            <div className="form-search mt-4">
              <form className="form form--small" id="frmQuizSearch" onSubmit={onSearch}>
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
                        <label className="field_label">{lbl('LBL_TYPE', 'Type')}</label>
                      </div>
                      <div className="field-wraper">
                        <div className="field_cover">
                          <select
                            name="quiz_type"
                            className="form-control"
                            value={quizType}
                            onChange={(e) => setQuizType(e.target.value)}
                          >
                            <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                            {Object.entries(quizTypes).map(([value, label]) => (
                              <option key={value} value={value}>
                                {label}
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
                          <input
                            type="submit"
                            className="btn btn--primary"
                            value={lbl('LBL_SEARCH', 'Search')}
                          />
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
              </form>
            </div>
          </div>
        </div>
        <div className="form-edit-body">
          {error && (
            <p className="color-red padding-6 margin-0">{error}</p>
          )}
          <div className="table-scroll">
            <table
              className="table table--responsive table--aligned-middle table--bordered"
              id="quiz-listing"
            >
              <thead>
                <tr className="title-row">
                  <th>
                    <label className="checkbox">
                      <input
                        type="checkbox"
                        name="all"
                        id="selectAllJs"
                        checked={allChecked}
                        onChange={(e) => toggleAll(e.target.checked)}
                      />
                      <i className="input-helper" />
                    </label>
                  </th>
                  <th>{lbl('LBL_TITLE', 'Title')}</th>
                  <th>{lbl('LBL_TYPE', 'Type')}</th>
                </tr>
              </thead>
              <tbody>
                {loading && quizzes.length === 0 ? (
                  <tr>
                    <td colSpan={3}>
                      <p className="color-secondary padding-6 margin-0">
                        {lbl('LBL_LOADING', 'Loading...')}
                      </p>
                    </td>
                  </tr>
                ) : quizzes.length === 0 ? (
                  <tr>
                    <td colSpan={3}>
                      <DashboardNoRecord
                        headingKey="LBL_NO_RECORD_FOUND"
                        headingFallback="No record found"
                      />
                    </td>
                  </tr>
                ) : (
                  quizzes.map((quiz) => (
                    <tr key={quiz.id}>
                      <td>
                        <label className="checkbox">
                          <input
                            type="checkbox"
                            name="quilin_quiz_id[]"
                            value={quiz.id}
                            checked={selected.has(quiz.id)}
                            onChange={(e) => toggleOne(quiz.id, e.target.checked)}
                          />
                          <i className="input-helper" />
                        </label>
                      </td>
                      <td>
                        <div className="flex-cell">
                          <div className="flex-cell__label">{lbl('LBL_TITLE', 'Title')}</div>
                          <div className="flex-cell__content">{quiz.title}</div>
                        </div>
                      </td>
                      <td>
                        <div className="flex-cell">
                          <div className="flex-cell__label">{lbl('LBL_TYPE', 'Type')}</div>
                          <div className="flex-cell__content">
                            {quiz.type_label ||
                              quizTypes[String(quiz.type)] ||
                              String(quiz.type)}
                          </div>
                        </div>
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
                    onClick={() => load(page + 1, false)}
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
