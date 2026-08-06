import {
  useEffect,
  useMemo,
  useState,
  type ChangeEvent,
  type FormEvent,
  type PointerEvent,
  type WheelEvent,
} from 'react';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { useSite } from '../../w3mentors/context/SiteContext';

type TimezoneOption = {
  id: string;
  label: string;
};

type ProfileForm = {
  username: string;
  email: string;
  fullName: string;
  timezone: string;
};

type CropOffset = {
  x: number;
  y: number;
};

const CROP_PREVIEW_WIDTH = 766;
const CROP_PREVIEW_HEIGHT = 420;
const CROP_FRAME_SIZE = 340;

function cropProfileImage(imageUrl: string, rotation: number, zoom: number, offset: CropOffset): Promise<Blob> {
  return new Promise((resolve, reject) => {
    const image = new Image();
    image.onload = () => {
      const size = 600;
      const canvas = document.createElement('canvas');
      canvas.width = size;
      canvas.height = size;
      const ctx = canvas.getContext('2d');
      if (!ctx) {
        reject(new Error('Canvas is not supported'));
        return;
      }

      const angle = (rotation * Math.PI) / 180;
      const rotated = Math.abs(rotation % 180) === 90;
      const sourceWidth = rotated ? image.naturalHeight : image.naturalWidth;
      const sourceHeight = rotated ? image.naturalWidth : image.naturalHeight;
      const previewScale = Math.max(CROP_PREVIEW_WIDTH / sourceWidth, CROP_PREVIEW_HEIGHT / sourceHeight);
      const outputScale = size / CROP_FRAME_SIZE;
      const drawScale = previewScale * zoom * outputScale;

      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, size, size);
      ctx.translate(size / 2 + offset.x * outputScale, size / 2 + offset.y * outputScale);
      ctx.rotate(angle);
      ctx.drawImage(
        image,
        (-image.naturalWidth * drawScale) / 2,
        (-image.naturalHeight * drawScale) / 2,
        image.naturalWidth * drawScale,
        image.naturalHeight * drawScale,
      );

      canvas.toBlob(
        (blob) => {
          if (blob) {
            resolve(blob);
          } else {
            reject(new Error('Unable to crop image'));
          }
        },
        'image/jpeg',
        0.9,
      );
    };
    image.onerror = () => reject(new Error('Unable to load image'));
    image.src = imageUrl;
  });
}

export function AdminProfilePage() {
  const { lbl } = useSite();
  const { admin, refresh } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [timezones, setTimezones] = useState<TimezoneOption[]>([]);
  const [form, setForm] = useState<ProfileForm>({
    username: '',
    email: '',
    fullName: '',
    timezone: 'UTC',
  });
  const [imageTick, setImageTick] = useState(Date.now());
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [uploadingImage, setUploadingImage] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [cropImageUrl, setCropImageUrl] = useState('');
  const [cropRotation, setCropRotation] = useState(0);
  const [cropZoom, setCropZoom] = useState(1);
  const [cropOffset, setCropOffset] = useState<CropOffset>({ x: 0, y: 0 });
  const [cropDragStart, setCropDragStart] = useState<{ pointerId: number; x: number; y: number; offset: CropOffset } | null>(null);

  const adminId = admin?.id ?? 0;
  const imageSrc = useMemo(() => `/api/v1/image/show/15/${adminId}/LARGE?t=${imageTick}`, [adminId, imageTick]);

  useEffect(() => {
    setMeta({ title: lbl('LBL_PROFILE', 'Profile') });
    return () => clearMeta();
  }, [clearMeta, lbl, setMeta]);

  useEffect(() => {
    if (!adminId) return;
    let cancelled = false;
    setLoading(true);
    setError('');
    adminApi
      .profile()
      .then((profileRes) => {
        if (cancelled) return;
        const data = profileRes.data.data;
        const timezone = String(data.timezone ?? profileRes.data.default_timezone ?? 'UTC');
        setForm({
          username: String(data.username ?? admin?.username ?? ''),
          email: String(data.email ?? admin?.email ?? ''),
          fullName: String(data.full_name ?? admin?.name ?? ''),
          timezone,
        });
        setTimezones(profileRes.data.timezones ?? []);
      })
      .catch(() => {
        if (!cancelled) setError(lbl('LBL_UNABLE_TO_LOAD_PROFILE', 'Unable to load profile'));
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [admin?.email, admin?.name, admin?.username, adminId, lbl]);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!adminId) return;
    setSaving(true);
    setMessage('');
    setError('');
    try {
      await adminApi.updateProfile({
        full_name: form.fullName,
        timezone: form.timezone,
      });
      await refresh();
      setMessage(lbl('MSG_SETUP_SUCCESSFUL', 'Setup successful'));
    } catch (err) {
      const apiError = err as { response?: { data?: { message?: string } } };
      setError(apiError.response?.data?.message ?? lbl('LBL_UNABLE_TO_SAVE_PROFILE', 'Unable to save profile'));
    } finally {
      setSaving(false);
    }
  };

  const handleImageSelect = (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) return;

    if (!/\.(png|jpe?g|gif|bmp)$/i.test(file.name)) {
      setError(lbl('LBL_INVALID_FILE_EXTENSION', 'Invalid file extension.'));
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      setCropRotation(0);
      setCropZoom(1);
      setCropOffset({ x: 0, y: 0 });
      setCropImageUrl(String(reader.result ?? ''));
      setError('');
    };
    reader.onerror = () => setError(lbl('LBL_UNABLE_TO_LOAD_IMAGE', 'Unable to load image'));
    reader.readAsDataURL(file);
  };

  const closeCropModal = () => {
    if (uploadingImage) return;
    setCropImageUrl('');
    setCropRotation(0);
    setCropZoom(1);
    setCropOffset({ x: 0, y: 0 });
    setCropDragStart(null);
  };

  const uploadCroppedImage = async () => {
    if (!cropImageUrl) return;
    setUploadingImage(true);
    setError('');
    setMessage('');
    try {
      const blob = await cropProfileImage(cropImageUrl, cropRotation, cropZoom, cropOffset);
      await adminApi.uploadProfileImage(blob);
      setImageTick(Date.now());
      window.dispatchEvent(new Event('admin:profile-image-updated'));
      setCropImageUrl('');
      setMessage(lbl('MSG_FILE_UPLOADED_SUCCESSFULLY', 'File uploaded successfully'));
    } catch (err) {
      const apiError = err as { response?: { data?: { message?: string } }; message?: string };
      setError(apiError.response?.data?.message ?? apiError.message ?? lbl('LBL_UNABLE_TO_UPLOAD_IMAGE', 'Unable to upload image'));
    } finally {
      setUploadingImage(false);
    }
  };

  const startCropDrag = (event: PointerEvent<HTMLDivElement>) => {
    if (uploadingImage) return;
    event.currentTarget.setPointerCapture(event.pointerId);
    setCropDragStart({
      pointerId: event.pointerId,
      x: event.clientX,
      y: event.clientY,
      offset: cropOffset,
    });
  };

  const moveCropDrag = (event: PointerEvent<HTMLDivElement>) => {
    if (!cropDragStart || cropDragStart.pointerId !== event.pointerId) return;
    setCropOffset({
      x: cropDragStart.offset.x + event.clientX - cropDragStart.x,
      y: cropDragStart.offset.y + event.clientY - cropDragStart.y,
    });
  };

  const endCropDrag = (event: PointerEvent<HTMLDivElement>) => {
    if (cropDragStart?.pointerId === event.pointerId) {
      setCropDragStart(null);
    }
  };

  const zoomCropWithWheel = (event: WheelEvent<HTMLDivElement>) => {
    event.preventDefault();
    const direction = event.deltaY > 0 ? -0.05 : 0.05;
    setCropZoom((current) => Math.min(3, Math.max(0.5, Number((current + direction).toFixed(2)))));
  };

  const removeProfileImage = async () => {
    setError('');
    setMessage('');
    try {
      await adminApi.removeProfileImage();
      setImageTick(Date.now());
      window.dispatchEvent(new Event('admin:profile-image-updated'));
      setMessage(lbl('MSG_FILE_DELETED_SUCCESSFULLY', 'File deleted successfully'));
    } catch (err) {
      const apiError = err as { response?: { data?: { message?: string } } };
      setError(apiError.response?.data?.message ?? lbl('LBL_UNABLE_TO_REMOVE_IMAGE', 'Unable to remove image'));
    }
  };

  return (
    <main className="main">
      <div className="container">
        <div className="row justify-content-center" id="profileInfoFrmBlock">
          <div className="col-lg-6">
            <div className="card">
              <div className="card-head">
                <div className="card-head-label">
                  <h3 className="card-head-title">{lbl('LBL_MY_PROFILE', 'My profile')}</h3>
                </div>
              </div>
              <div className="card-body">
                {message ? <div className="alert alert-success">{message}</div> : null}
                {error ? <div className="alert alert-danger">{error}</div> : null}
                <div className="profile-details">
                  <div className="text-center">
                    <div className="avatar avatar-outline avatar-circle">
                      <div className="avatar__holder">
                        <img
                          src={imageSrc}
                          alt=""
                          onError={(event) => {
                            event.currentTarget.onerror = null;
                            event.currentTarget.src = '/images/no-image-user.png';
                          }}
                        />
                      </div>
                      <span
                        className="avatar__upload"
                        aria-label={lbl('LBL_UPDATE_PROFILE_PICTURE', 'Update profile picture')}
                      >
                        <svg className="svg" width="12" height="12" viewBox="0 0 24 24" aria-hidden="true">
                          <path d="M4 20H8L18.5 9.5L14.5 5.5L4 16V20Z" fill="currentColor" />
                          <path d="M15.5 4.5L17 3L21 7L19.5 8.5L15.5 4.5Z" fill="currentColor" />
                        </svg>
                        <input
                          type="file"
                          accept=".png,.jpg,.jpeg,.gif,.bmp"
                          onChange={handleImageSelect}
                        />
                      </span>
                      <button
                        className="avatar__cancel"
                        type="button"
                        aria-label={lbl('LBL_REMOVE_PROFILE_IMAGE', 'Remove profile image')}
                        onClick={removeProfileImage}
                      >
                        <svg className="svg" width="12" height="12" viewBox="0 0 24 24" aria-hidden="true">
                          <path
                            d="M7 21C5.9 21 5 20.1 5 19V8H19V19C19 20.1 18.1 21 17 21H7Z"
                            fill="currentColor"
                          />
                          <path d="M9 4H15L16 6H21V8H3V6H8L9 4Z" fill="currentColor" />
                        </svg>
                      </button>
                    </div>
                  </div>
                  <div className="text-center">
                    <form className="ggg admin-profile-image-form" onSubmit={(event) => event.preventDefault()}>
                      <span>{lbl('LBL_MAX_SIZE_{size}', 'Max size 4.00 MB').replace('{size}', '4.00')}</span>
                      <br />
                      <span>
                        {lbl('LBL_ALLOWED_FILE_EXTS_{ext}', 'Allowed file Exts png,jpg,jpeg,gif,bmp').replace(
                          '{ext}',
                          'png,jpg,jpeg,gif,bmp',
                        )}
                      </span>
                      <input type="hidden" name="rotate_left" />
                      <input type="hidden" name="rotate_right" />
                      <input type="hidden" name="remove_profile_img" />
                      <input type="hidden" name="action" />
                      <input type="hidden" name="img_data" />
                    </form>
                    <div id="dispMessage" />
                  </div>
                  <div className="mt-5">
                    <form id="profileInfoFrm" className="form" onSubmit={handleSubmit}>
                      <div className="row">
                        <div className="col-md-6">
                          <label className="field_label" htmlFor="admin_username">
                            {lbl('LBL_USERNAME', 'Username')}<span className="spn_must_field">*</span>
                          </label>
                          <input
                            id="admin_username"
                            className="form-control"
                            name="admin_username"
                            value={form.username}
                            disabled
                            readOnly
                          />
                        </div>
                        <div className="col-md-6">
                          <label className="field_label" htmlFor="admin_email">
                            {lbl('LBL_EMAIL_USERNAME', 'Email/Username')}
                            <span className="spn_must_field">*</span>
                          </label>
                          <input
                            id="admin_email"
                            className="form-control"
                            name="admin_email"
                            value={form.email}
                            disabled
                            readOnly
                          />
                        </div>
                        <div className="col-md-6">
                          <label className="field_label" htmlFor="admin_name">
                            {lbl('LBL_FULL_NAME', 'Full name')}<span className="spn_must_field">*</span>
                          </label>
                          <input
                            id="admin_name"
                            className="form-control"
                            name="admin_name"
                            value={form.fullName}
                            disabled={loading}
                            onChange={(event) => setForm((current) => ({ ...current, fullName: event.target.value }))}
                          />
                        </div>
                        <div className="col-md-6">
                          <label className="field_label" htmlFor="admin_timezone">
                            {lbl('LBL_TIMEZONE', 'Timezone')}<span className="spn_must_field">*</span>
                          </label>
                          <select
                            id="admin_timezone"
                            className="form-control form-select"
                            name="admin_timezone"
                            value={form.timezone}
                            disabled={loading}
                            onChange={(event) =>
                              setForm((current) => ({ ...current, timezone: event.target.value }))
                            }
                          >
                            {timezones.map((timezone) => (
                              <option key={timezone.id} value={timezone.id}>
                                {timezone.label}
                              </option>
                            ))}
                          </select>
                        </div>
                      </div>
                      <button className="btn btn-primary mt-4" type="submit" disabled={saving || loading}>
                        {saving ? lbl('LBL_SAVING', 'Saving...') : lbl('LBL_SAVE_CHANGES', 'Save changes')}
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      {cropImageUrl ? (
        <div className="modal fade show admin-profile-crop-modal" role="dialog" aria-modal="true">
          <div className="modal-dialog modal-lg modal-dialog-centered">
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title">{lbl('LBL_PROFILE_IMAGE', 'Profile image')}</h5>
                <button
                  type="button"
                  className="btn-close"
                  aria-label={lbl('LBL_CLOSE', 'Close')}
                  onClick={closeCropModal}
                />
              </div>
              <div className="modal-body">
                <div
                  className="admin-profile-crop-preview"
                  onPointerDown={startCropDrag}
                  onPointerMove={moveCropDrag}
                  onPointerUp={endCropDrag}
                  onPointerCancel={endCropDrag}
                  onWheel={zoomCropWithWheel}
                >
                  <img
                    src={cropImageUrl}
                    alt=""
                    draggable={false}
                    style={{
                      transform: `translate(${cropOffset.x}px, ${cropOffset.y}px) rotate(${cropRotation}deg) scale(${cropZoom})`,
                    }}
                  />
                  <span className="admin-profile-crop-mask" />
                </div>
                <div className="admin-profile-crop-actions">
                  <button
                    type="button"
                    className="admin-profile-crop-action admin-profile-crop-action--secondary"
                    onClick={() => setCropRotation((current) => current - 90)}
                    disabled={uploadingImage}
                  >
                    <svg className="svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                      <path
                        d="M7.11 8.53A6 6 0 1 1 6 12H4a8 8 0 1 0 2.34-5.66L4 4v6h6L7.11 8.53Z"
                        fill="currentColor"
                      />
                    </svg>
                    <span>{lbl('LBL_ROTATE_LEFT', 'Rotate left')}</span>
                  </button>
                  <button
                    type="button"
                    className="admin-profile-crop-action admin-profile-crop-action--primary"
                    onClick={uploadCroppedImage}
                    disabled={uploadingImage}
                  >
                    <svg className="svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                      <path
                        d="M5 20H19V18H5V20ZM13 4H11V12.17L7.41 8.59L6 10L12 16L18 10L16.59 8.59L13 12.17V4Z"
                        fill="currentColor"
                      />
                    </svg>
                    {uploadingImage
                      ? lbl('LBL_UPLOADING', 'Uploading...')
                      : lbl('LBL_UPDATE_PROFILE_PICTURE', 'Update profile picture')}
                  </button>
                  <button
                    type="button"
                    className="admin-profile-crop-action admin-profile-crop-action--secondary"
                    onClick={() => setCropRotation((current) => current + 90)}
                    disabled={uploadingImage}
                  >
                    <svg className="svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                      <path
                        d="M16.89 8.53A6 6 0 1 0 18 12h2a8 8 0 1 1-2.34-5.66L20 4v6h-6l2.89-1.47Z"
                        fill="currentColor"
                      />
                    </svg>
                    <span>{lbl('LBL_ROTATE_RIGHT', 'Rotate right')}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div className="modal-backdrop fade show" onClick={closeCropModal} />
        </div>
      ) : null}
    </main>
  );
}
