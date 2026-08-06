import { type FormEvent, useCallback, useEffect, useRef, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminModal } from './AdminModal';

type TeacherSuggestion = {
  id: number;
  full_name: string;
  email: string;
};

type Props = {
  open: boolean;
  commissionId: number;
  onClose: () => void;
  onSaved: () => void;
};

export function AdminCommissionModal({ open, commissionId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [recordId, setRecordId] = useState(0);
  const [userId, setUserId] = useState(0);
  const [userName, setUserName] = useState('');
  const [isGlobal, setIsGlobal] = useState(false);
  const [userLocked, setUserLocked] = useState(false);
  const [lessons, setLessons] = useState('');
  const [classes, setClasses] = useState('');
  const [courses, setCourses] = useState('');
  const [classesEnabled, setClassesEnabled] = useState(true);
  const [coursesEnabled, setCoursesEnabled] = useState(true);
  const [suggestions, setSuggestions] = useState<TeacherSuggestion[]>([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const coverRef = useRef<HTMLDivElement>(null);

  const reset = useCallback(() => {
    setError('');
    setRecordId(0);
    setUserId(0);
    setUserName('');
    setIsGlobal(false);
    setUserLocked(false);
    setLessons('');
    setClasses('');
    setCourses('');
    setSuggestions([]);
    setShowSuggestions(false);
  }, []);

  useEffect(() => {
    if (!open) {
      reset();
      return;
    }

    setLoading(true);
    setError('');
    void adminApi
      .commissionShow(commissionId)
      .then((res) => {
        const data = res.data.data ?? {};
        const global = Boolean(data.is_global);
        setRecordId(Number(data.comm_id ?? 0));
        setUserId(Number(data.comm_user_id ?? 0));
        setIsGlobal(global);
        setUserLocked((data.comm_id ?? 0) > 0);
        setUserName(
          global
            ? lbl('LBL_GLOBAL_COMMISSION', 'Global commission')
            : String(data.user_name ?? ''),
        );
        setLessons(data.comm_lessons !== '' && data.comm_lessons !== undefined ? String(data.comm_lessons) : '');
        setClasses(data.comm_classes !== '' && data.comm_classes !== undefined ? String(data.comm_classes) : '');
        setCourses(data.comm_courses !== '' && data.comm_courses !== undefined ? String(data.comm_courses) : '');
        setClassesEnabled(Boolean(data.classes_enabled ?? true));
        setCoursesEnabled(Boolean(data.courses_enabled ?? true));
      })
      .catch(() => setError(lbl('LBL_INVALID_REQUEST', 'Invalid request')))
      .finally(() => setLoading(false));
  }, [commissionId, lbl, open, reset]);

  useEffect(() => {
    if (!open || userLocked || isGlobal) {
      return;
    }
    const keyword = userName.trim();
    if (keyword.length < 1) {
      setSuggestions([]);
      return;
    }
    const timer = window.setTimeout(() => {
      void adminApi.commissionAutocomplete(keyword).then((res) => {
        setSuggestions((res.data.data ?? []) as TeacherSuggestion[]);
        setShowSuggestions(true);
      });
    }, 250);
    return () => window.clearTimeout(timer);
  }, [isGlobal, open, userLocked, userName]);

  useEffect(() => {
    const onDocClick = (e: MouseEvent) => {
      if (coverRef.current && !coverRef.current.contains(e.target as Node)) {
        setShowSuggestions(false);
      }
    };
    document.addEventListener('mousedown', onDocClick);
    return () => document.removeEventListener('mousedown', onDocClick);
  }, []);

  const pickTeacher = (item: TeacherSuggestion) => {
    setUserId(item.id);
    setUserName(item.full_name);
    setShowSuggestions(false);
  };

  const validateRate = (value: string) => {
    const rate = Number(value);
    return !Number.isNaN(rate) && rate >= 1 && rate <= 100;
  };

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!validateRate(lessons)) {
      setError(lbl('LBL_INVALID_REQUEST', 'Lesson commission must be between 1 and 100'));
      return;
    }
    if (classesEnabled && !validateRate(classes)) {
      setError(lbl('LBL_INVALID_REQUEST', 'Class commission must be between 1 and 100'));
      return;
    }
    if (coursesEnabled && !validateRate(courses)) {
      setError(lbl('LBL_INVALID_REQUEST', 'Course commission must be between 1 and 100'));
      return;
    }
    if (recordId < 1 && userId < 1) {
      setError(lbl('LBL_INVALID_REQUEST', 'Please select a teacher'));
      return;
    }

    setSaving(true);
    setError('');
    try {
      await adminApi.commissionSetup({
        comm_id: recordId,
        comm_user_id: isGlobal ? 0 : userId,
        comm_lessons: Number(lessons),
        comm_classes: classesEnabled ? Number(classes) : 0,
        comm_courses: coursesEnabled ? Number(courses) : 0,
      });
      onSaved();
      onClose();
    } catch (err: unknown) {
      setError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Unable to save commission',
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <AdminModal
      open={open}
      title={lbl('LBL_COMMISSION_SETUP', 'Commission setup')}
      size="sm"
      onClose={onClose}
    >
      <div className="form-edit-body">
        {loading ? (
          <div className="table-processing loaderJs p-5">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : (
          <form className="form form_horizontal" onSubmit={onSubmit}>
            {error ? <div className="alert alert-danger">{error}</div> : null}
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBL_USER_NAME', 'User name')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover" ref={coverRef}>
                      <input
                        className="form-control"
                        type="text"
                        name="user_name"
                        autoComplete="off"
                        value={userName}
                        disabled={userLocked}
                        onChange={(e) => {
                          setUserName(e.target.value);
                          setUserId(0);
                        }}
                        onFocus={() => !userLocked && suggestions.length > 0 && setShowSuggestions(true)}
                      />
                      {!userLocked && !isGlobal && showSuggestions && suggestions.length > 0 ? (
                        <ul
                          className="ui-menu ui-widget ui-widget-content ui-autocomplete custom-ui-autocomplete"
                          role="listbox"
                        >
                          {suggestions.map((item) => (
                            <li key={item.id} className="ui-menu-item">
                              <div
                                className="ui-menu-item-wrapper"
                                role="option"
                                tabIndex={-1}
                                onMouseDown={(ev) => {
                                  ev.preventDefault();
                                  pickTeacher(item);
                                }}
                              >
                                {item.full_name} ({item.email})
                              </div>
                            </li>
                          ))}
                        </ul>
                      ) : null}
                    </div>
                  </div>
                </div>
              </div>

              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_LESSON_COMMISSION_FEES_[%]', 'Lesson commission fees [%]')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        className="form-control"
                        type="text"
                        inputMode="decimal"
                        value={lessons}
                        onChange={(e) => setLessons(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>

              {classesEnabled ? (
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_CLASS_COMMISSION_FEES_[%]', 'Class commission fees [%]')}
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <input
                          className="form-control"
                          type="text"
                          inputMode="decimal"
                          value={classes}
                          onChange={(e) => setClasses(e.target.value)}
                          required
                        />
                      </div>
                    </div>
                  </div>
                </div>
              ) : null}

              {coursesEnabled ? (
                <div className="col-md-12">
                  <div className="field-set">
                    <div className="caption-wraper">
                      <label className="field_label">
                        {lbl('LBL_COURSE_COMMISSION_FEES_[%]', 'Course commission fees [%]')}
                        <span className="spn_must_field">*</span>
                      </label>
                    </div>
                    <div className="field-wraper">
                      <div className="field_cover">
                        <input
                          className="form-control"
                          type="text"
                          inputMode="decimal"
                          value={courses}
                          onChange={(e) => setCourses(e.target.value)}
                          required
                        />
                      </div>
                    </div>
                  </div>
                </div>
              ) : null}
            </div>

            <div className="form-actions">
              <button type="submit" className="btn btn-brand" disabled={saving}>
                {saving
                  ? lbl('LBL_PROCESSING_PLEASE_WAIT', 'Processing please wait')
                  : lbl('LBL_SAVE_CHANGES', 'Save changes')}
              </button>
            </div>
          </form>
        )}
      </div>
    </AdminModal>
  );
}
