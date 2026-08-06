import { useRef, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { AdminSpriteIcon } from './AdminSpriteIcon';

export type ConfigurationMediaSlot = {
  file_type: number;
  label_key: string;
  label_fallback: string;
  disclaimer_type?: 'size' | 'dimensions';
  disclaimer_width?: number;
  disclaimer_height?: number;
  disclaimer_dimensions?: string;
  preview_size?: string;
  file_id: number | null;
  has_custom_file: boolean;
  preview_url: string;
};

type AdminConfigurationMediaFormProps = {
  langId: number;
  slots: ConfigurationMediaSlot[];
  canEdit: boolean;
  onUpdated: (slots: ConfigurationMediaSlot[]) => void;
};

function disclaimerText(slot: ConfigurationMediaSlot, lbl: (key: string, fallback?: string) => string): string {
  if (slot.disclaimer_type === 'size') {
    return lbl(
      'LBL_For_best_view_width_{width}px_and_height_{height}px',
      `For best view width ${slot.disclaimer_width ?? 200}px and height ${slot.disclaimer_height ?? 100}px`,
    )
      .replace('{width}', String(slot.disclaimer_width ?? 200))
      .replace('{height}', String(slot.disclaimer_height ?? 100));
  }

  return lbl('LBL_Dimensions_%s', 'Dimensions %s').replace('%s', slot.disclaimer_dimensions ?? '');
}

export function AdminConfigurationMediaForm({
  langId,
  slots,
  canEdit,
  onUpdated,
}: AdminConfigurationMediaFormProps) {
  const { lbl } = useSite();
  const [uploadingType, setUploadingType] = useState<number | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [cacheBust, setCacheBust] = useState(() => Date.now());
  const fileInputs = useRef<Record<number, HTMLInputElement | null>>({});

  const reload = async () => {
    const res = await adminApi.configurationMedia(langId);
    onUpdated((res.data.slots as ConfigurationMediaSlot[]) ?? []);
    setCacheBust(Date.now());
  };

  const onPickFile = (fileType: number) => {
    fileInputs.current[fileType]?.click();
  };

  const onUpload = async (fileType: number, file: File) => {
    setUploadingType(fileType);
    setMessage(null);
    setError(null);
    try {
      const res = await adminApi.uploadConfigurationMedia(fileType, langId, file);
      setMessage(res.data.message ?? lbl('MSG_UPLOADED_SUCCESSFULLY', 'Uploaded successfully'));
      await reload();
    } catch (err: unknown) {
      const apiMessage =
        typeof err === 'object' &&
        err !== null &&
        'response' in err &&
        typeof (err as { response?: { data?: { message?: string } } }).response?.data?.message === 'string'
          ? (err as { response: { data: { message: string } } }).response.data.message
          : lbl('MSG_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(apiMessage);
    } finally {
      setUploadingType(null);
    }
  };

  const onRemove = async (fileType: number) => {
    setMessage(null);
    setError(null);
    try {
      const res = await adminApi.removeConfigurationMedia(fileType, langId);
      setMessage(res.data.message ?? lbl('MSG_Deleted_Successfully', 'Deleted successfully'));
      await reload();
    } catch (err: unknown) {
      const apiMessage =
        typeof err === 'object' &&
        err !== null &&
        'response' in err &&
        typeof (err as { response?: { data?: { message?: string } } }).response?.data?.message === 'string'
          ? (err as { response: { data: { message: string } } }).response.data.message
          : lbl('MSG_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(apiMessage);
    }
  };

  return (
    <div className="card-body">
      <div className="form form_horizontal">
        <div className="row">
          {slots.map((slot, index) => {
            const title = lbl(slot.label_key, slot.label_fallback);
            const previewUrl = `${slot.preview_url}${slot.preview_url.includes('?') ? '&' : '?'}t=${cacheBust}`;

            return (
              <div key={slot.file_type} className="col-md-12">
                <div className="row">
                  <div className="col-md-6">
                    <div className="form-group">
                      <h6>{title}</h6>
                      <span className="form-text text-muted">
                        <strong>{lbl('LBL_IMAGE_DISCLAIMER', 'Image disclaimer')}: </strong>
                        {disclaimerText(slot, lbl)}
                      </span>
                    </div>
                  </div>
                  <div className="col-md-6">
                    <div className="form-group">
                      <div className="dropzone mt-3 dropzoneContainerJs">
                        <div className="dropzone-uploaded dropzoneUploadedJs">
                          <img src={previewUrl} alt={title} title={title} />
                          {canEdit ? (
                            <div className="dropzone-uploaded-action">
                              <ul className="actions">
                                <li>
                                  <a
                                    href="javascript:void(0)"
                                    className="logoFiles-Js"
                                    title={lbl('LBL_CLICK_HERE_TO_EDIT', 'Click here to edit')}
                                    onClick={(event) => {
                                      event.preventDefault();
                                      onPickFile(slot.file_type);
                                    }}
                                  >
                                    <AdminSpriteIcon icon="edit" />
                                  </a>
                                </li>
                                {slot.has_custom_file ? (
                                  <li>
                                    <a
                                      href="javascript:void(0)"
                                      title={lbl('LBL_CLICK_HERE_TO_REMOVE', 'Click here to remove')}
                                      onClick={(event) => {
                                        event.preventDefault();
                                        void onRemove(slot.file_type);
                                      }}
                                    >
                                      <AdminSpriteIcon icon="delete" />
                                    </a>
                                  </li>
                                ) : null}
                              </ul>
                            </div>
                          ) : null}
                        </div>
                      </div>
                      {canEdit ? (
                        <button
                          type="button"
                          className="btn btn-brand btn-sm logoFiles-Js mt-2"
                          disabled={uploadingType === slot.file_type}
                          onClick={() => onPickFile(slot.file_type)}
                        >
                          {uploadingType === slot.file_type
                            ? lbl('LBL_Saving', 'Uploading...')
                            : lbl('LBL_UPLOAD_FILE', 'Upload file')}
                        </button>
                      ) : null}
                      <input
                        ref={(node) => {
                          fileInputs.current[slot.file_type] = node;
                        }}
                        type="file"
                        className="hide"
                        accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,image/bmp,image/x-icon"
                        onChange={(event) => {
                          const file = event.target.files?.[0];
                          if (file) {
                            void onUpload(slot.file_type, file);
                          }
                          event.currentTarget.value = '';
                        }}
                      />
                    </div>
                  </div>
                </div>
                {index < slots.length - 1 ? (
                  <div className="form-group">
                    <div className="separator my-3" />
                  </div>
                ) : null}
              </div>
            );
          })}
        </div>
      </div>

      {message ? <div className="alert alert-success mt-3">{message}</div> : null}
      {error ? <div className="alert alert-danger mt-3">{error}</div> : null}
    </div>
  );
}
