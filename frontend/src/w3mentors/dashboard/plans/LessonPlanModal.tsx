import { useEffect, useState } from 'react';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';

type PlanFile = { id: number; name: string; download_url: string };

type FormMeta = {
  levels: Record<string, string>;
  max_upload_mb: number;
  allowed_extensions: string[];
};

type PlanData = {
  id: number;
  title: string;
  detail: string;
  level: number;
  files: PlanFile[];
};

type Props = {
  planId: number;
  onClose: () => void;
  onSaved: () => void;
};

const EMPTY = { title: '', detail: '', level: '' };

export function LessonPlanModal({ planId, onClose, onSaved }: Props) {
  const { lbl } = useSite();
  const [meta, setMeta] = useState<FormMeta | null>(null);
  const [files, setFiles] = useState<PlanFile[]>([]);
  const [form, setForm] = useState(EMPTY);
  const [pickedFiles, setPickedFiles] = useState<FileList | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    api
      .get<{ data: PlanData | null; meta: FormMeta }>(`/dashboard/plans/${planId}`)
      .then((res) => {
        setMeta(res.data.meta);
        const plan = res.data.data;
        if (plan) {
          setForm({
            title: plan.title,
            detail: plan.detail,
            level: String(plan.level),
          });
          setFiles(plan.files ?? []);
        } else {
          setForm(EMPTY);
          setFiles([]);
        }
      })
      .catch(() => setError(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong')))
      .finally(() => setLoading(false));
  }, [planId, lbl]);

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);

    const fd = new FormData();
    fd.append('plan_id', String(planId));
    fd.append('plan_title', form.title.trim());
    fd.append('plan_detail', form.detail.trim());
    fd.append('plan_level', form.level);

    if (pickedFiles) {
      Array.from(pickedFiles).forEach((file) => fd.append('plan_file[]', file));
    }

    try {
      await api.post('/dashboard/plans', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      onSaved();
      onClose();
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(msg);
    } finally {
      setSaving(false);
    }
  };

  const removeFile = async (fileId: number) => {
    if (planId < 1) return;
    try {
      await api.delete(`/dashboard/plans/${planId}/files/${fileId}`);
      setFiles((prev) => prev.filter((f) => f.id !== fileId));
    } catch {
      setError(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    }
  };

  const fileHint =
    meta &&
    lbl(
      'LBL_FILE_MAX_SIZE_{size}_AND_ALLOWED_EXT_{ext}',
      'File max size {size} MB and allowed ext {ext}'
    )
      .replace('{size}', String(meta.max_upload_mb))
      .replace('{ext}', meta.allowed_extensions.join(', '));

  return (
    <>
      <div className="modal-header">
        <h5>{lbl('LBL_SETUP_LESSON_PLAN', 'Setup lesson plan')}</h5>
        <button type="button" className="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label="" onClick={onClose} />
      </div>
      <div className="modal-body">
        {loading ? (
          <p className="color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>
        ) : (
          <form className="form" id="lessonPlanFrm" encType="multipart/form-data" onSubmit={onSubmit}>
            <div className="row">
              <div className="col-md-8">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_TITLE', 'Title')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="text"
                        name="plan_title"
                        className="form-control"
                        value={form.title}
                        onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_LEVEL', 'Level')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <select
                        name="plan_level"
                        className="form-control"
                        value={form.level}
                        onChange={(e) => setForm((f) => ({ ...f, level: e.target.value }))}
                        required
                      >
                        <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                        {meta &&
                          Object.entries(meta.levels).map(([value, label]) => (
                            <option key={value} value={value}>
                              {label}
                            </option>
                          ))}
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_DETAIL', 'Detail')}
                      <span className="spn_must_field">*</span>
                    </label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <textarea
                        name="plan_detail"
                        className="form-control"
                        rows={5}
                        maxLength={500}
                        value={form.detail}
                        onChange={(e) => setForm((f) => ({ ...f, detail: e.target.value }))}
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="row">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">{lbl('LBl_Plan_Files', 'Plan files')}</label>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="file"
                        name="plan_file[]"
                        id="plan_file"
                        className="form-control"
                        multiple
                        accept={meta?.allowed_extensions.map((e) => `.${e}`).join(',')}
                        onChange={(e) => setPickedFiles(e.target.files)}
                      />
                      {files.length > 0 && (
                        <div className="field-set filelink mt-2">
                          {files.map((file) => (
                            <span className="tag" key={file.id}>
                              <span>
                                <a target="_blank" rel="noreferrer" href={file.download_url}>
                                  {file.name}&nbsp;
                                </a>
                              </span>
                              <button
                                type="button"
                                className="btn btn-link p-0 border-0"
                                onClick={() => removeFile(file.id)}
                              >
                                x
                              </button>
                            </span>
                          ))}
                        </div>
                      )}
                      {fileHint && <small className="d-block mt-1 color-secondary">{fileHint}</small>}
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {error && <p className="color-red small">{error}</p>}
            <div className="row">
              <div className="col-sm-12">
                <div className="field-set mb-0">
                  <div className="field-wraper form-buttons-group">
                    <div className="field_cover">
                      <input
                        type="submit"
                        className="btn btn--primary"
                        value={lbl('LBL_Submit', 'Submit')}
                        disabled={saving}
                      />
                      <input
                        type="button"
                        className="btn btn--secondary ms-2"
                        value={lbl('LBL_Cancel', 'Cancel')}
                        onClick={onClose}
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        )}
      </div>
    </>
  );
}
