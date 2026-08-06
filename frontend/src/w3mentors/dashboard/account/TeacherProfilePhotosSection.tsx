import { useCallback, useEffect, useRef, useState } from 'react';
import { api } from '../../../api/client';
import { useAuth } from '../../../context/AuthContext';
import { useSite } from '../../context/SiteContext';
import type { ProfilePhotosForm, ProfilePhotosResponse } from './teacherProfileTypes';

type Props = {
  onNextTab?: () => void;
};

function imageSrc(base: string, version: number) {
  return `${base}?t=${version}`;
}

export function TeacherProfilePhotosSection({ onNextTab }: Props) {
  const { lbl } = useSite();
  const { user, reloadProfile } = useAuth();
  const userId = user?.id ?? 0;
  const fileRef = useRef<HTMLInputElement>(null);

  const [data, setData] = useState<ProfilePhotosForm | null>(null);
  const [videoLink, setVideoLink] = useState('');
  const [imageVersion, setImageVersion] = useState(Date.now());
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const res = await api.get<ProfilePhotosResponse>('/users/me/profile/photos');
      setData(res.data.data);
      setVideoLink(res.data.data.video_link ?? '');
      setImageVersion(Date.now());
    } catch {
      setError(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
    } finally {
      setLoading(false);
    }
  }, [lbl]);

  useEffect(() => {
    load();
  }, [load]);

  const onPickImage = async (file: File | undefined) => {
    if (!file) return;
    setUploading(true);
    setError('');
    setMessage('');
    const formData = new FormData();
    formData.append('user_profile_image', file);
    try {
      const res = await api.post<{ message: string; data: ProfilePhotosForm }>(
        '/users/me/profile/photos',
        formData,
        { headers: { 'Content-Type': 'multipart/form-data' } }
      );
      setData(res.data.data);
      setImageVersion(Date.now());
      setMessage(res.data.message);
      await reloadProfile();
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(msg);
    } finally {
      setUploading(false);
      if (fileRef.current) {
        fileRef.current.value = '';
      }
    }
  };

  const onRemoveImage = async () => {
    if (!window.confirm(lbl('LBL_DO_YOU_WANT_TO_REMOVE', 'Do you want to remove?'))) {
      return;
    }
    setUploading(true);
    setError('');
    try {
      const res = await api.delete<{ message: string; data: ProfilePhotosForm }>(
        '/users/me/profile/photos'
      );
      setData(res.data.data);
      setImageVersion(Date.now());
      setMessage(res.data.message);
      await reloadProfile();
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(msg);
    } finally {
      setUploading(false);
    }
  };

  const onSave = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!data || !data.is_teacher) return;
    setSaving(true);
    setMessage('');
    setError('');
    try {
      const res = await api.put<{ message: string; data: ProfilePhotosForm }>(
        '/users/me/profile/photos',
        { video_link: videoLink }
      );
      setData(res.data.data);
      setVideoLink(res.data.data.video_link ?? '');
      setMessage(res.data.message);
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(msg);
    } finally {
      setSaving(false);
    }
  };

  if (loading || !data || userId < 1) {
    return (
      <div className="p-4 p-md-5">
        <p className="color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>
      </div>
    );
  }

  const extLabel = data.allowed_extensions.join(', ');
  const infoText = lbl(
    'LBL_PROFILE_PICTURE_INFO_TEXT_{size}_{ext}',
    `Profile picture info text ${data.max_upload_mb} MB ${extLabel}`
  )
    .replace('{size}', String(data.max_upload_mb))
    .replace('{ext}', extLabel);

  const urls = data.image_urls;

  return (
    <div className="p-4 p-md-5">
      <div className="max-width-80">
        <form
          id="frmProfile"
          className="form form--horizontal"
          onSubmit={data.is_teacher ? onSave : (e) => e.preventDefault()}
          autoComplete="off"
        >
          <div className="row">
            <div className="col-md-12">
              <div className="field-set">
                <div className="caption-wraper">
                  <label className="field_label">
                    {lbl('LBL_PROFILE_PICTURE', 'Profile picture')}
                  </label>
                  <small className="p-0">{infoText}</small>
                </div>
                <div className="field-wraper">
                  <div className="field_cover">
                    <div className="avtar-views">
                      <ul className="avtar-views-list">
                        <li className="avtar-views-item">
                          <div className="avtar avtar--xlarge" data-title="">
                            <span>
                              <img
                                src={imageSrc(urls.xlarge, imageVersion)}
                                alt=""
                              />
                            </span>
                          </div>
                        </li>
                      </ul>
                      <div className="buttons-group mt-3">
                        <span className="btn btn--bordered color-primary btn--small btn--fileupload btn--wide me-2">
                          <input
                            ref={fileRef}
                            type="file"
                            name="user_profile_image"
                            accept="image/*"
                            disabled={uploading}
                            onChange={(e) => onPickImage(e.target.files?.[0])}
                          />
                          {uploading
                            ? lbl('LBL_SAVING', 'Saving...')
                            : data.has_image
                              ? lbl('LBL_EDIT', 'Edit')
                              : lbl('LBL_ADD', 'Add')}
                        </span>
                        {data.has_image && (
                          <a
                            className="btn btn--bordered color-red btn--small btn--wide"
                            href="javascript:void(0);"
                            onClick={(e) => {
                              e.preventDefault();
                              onRemoveImage();
                            }}
                          >
                            {lbl('LBL_REMOVE', 'Remove')}
                          </a>
                        )}
                      </div>
                    </div>
                    <div className="profile-media" />
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="row">
            <div className="col-md-12">
              <div className="field-set">
                <div className="caption-wraper">
                  <label className="field_label">{lbl('LBL_OTHER_DIMENSIONS', 'Other dimensions')}</label>
                </div>
                <div className="field-wraper">
                  <div className="field_cover">
                    <div className="avtar-views">
                      <ul className="avtar-views-list">
                        <li className="avtar-views-item">
                          <figure className="avtar avtar--round avtar--xl">
                            <img src={imageSrc(urls.large, imageVersion)} alt="" />
                          </figure>
                        </li>
                        <li className="avtar-views-item">
                          <figure className="avtar avtar--round avtar--md">
                            <img src={imageSrc(urls.medium, imageVersion)} alt="" />
                          </figure>
                        </li>
                        <li className="avtar-views-item">
                          <figure className="avtar avtar--round avtar--sm">
                            <img src={imageSrc(urls.small, imageVersion)} alt="" />
                          </figure>
                        </li>
                      </ul>
                    </div>
                    <div className="profile-media" />
                  </div>
                </div>
              </div>
            </div>
          </div>
          {(error || message) && !data.is_teacher && (
            <div className="row">
              <div className="col-md-12">
                {error && <p className="color-danger m-0">{error}</p>}
                {message && <p className="color-primary m-0">{message}</p>}
              </div>
            </div>
          )}
          {data.is_teacher && (
            <div className="row my-4">
              <div className="col-md-12">
                <div className="field-set">
                  <div className="caption-wraper">
                    <label className="field_label">
                      {lbl('LBL_INTRODUCTION_VIDEO_LINK', 'Introduction video link')}
                    </label>
                    <small className="p-0">
                      {lbl(
                        'LBL_PROFILE_VIDEO_FIELD_INFO',
                        'Add a YouTube link to introduce yourself to students.'
                      )}
                    </small>
                  </div>
                  <div className="field-wraper">
                    <div className="field_cover">
                      <input
                        type="text"
                        className="form-control"
                        name="user_video_link"
                        value={videoLink}
                        placeholder={lbl('LBL_VIDEO_LINK_PLACEHOLDER', 'Video link')}
                        onChange={(e) => setVideoLink(e.target.value)}
                        onBlur={() => undefined}
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}
          {data.is_teacher && (
            <div className="row submit-row">
              <div className="col-sm-12">
                <div className="field-set">
                  <div className="field-wraper">
                    <div className="field_cover d-flex align-items-center flex-wrap gap-2">
                      {error && <p className="color-danger m-0 me-3">{error}</p>}
                      {message && <p className="color-primary m-0 me-3">{message}</p>}
                      <button type="submit" className="btn btn--primary" disabled={saving}>
                        {saving ? lbl('LBL_SAVING', 'Saving...') : lbl('LBL_SAVE', 'Save')}
                      </button>
                      {onNextTab && (
                        <button
                          type="button"
                          className="btn btn--secondary"
                          disabled={saving}
                          onClick={onNextTab}
                        >
                          {lbl('LBL_Next', 'Next')}
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}
        </form>
      </div>
    </div>
  );
}
