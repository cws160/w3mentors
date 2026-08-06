import { useCallback, useEffect, useState } from 'react';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import { useModal } from '../../context/ModalContext';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { TeacherAddressModal } from './TeacherAddressModal';
import type { TeacherAddressMeta, TeacherAddressRecord } from './teacherAddressTypes';
import { addressToForm, emptyAddressForm } from './teacherAddressTypes';

export function TeacherAddressesSection() {
  const { lbl, langId } = useSite();
  const { showModal, closeModal } = useModal();
  const [meta, setMeta] = useState<TeacherAddressMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(() => {
    setLoading(true);
    setError('');
    api
      .get<{ data: TeacherAddressMeta }>('/account/teacher/addresses', { params: { lang_id: langId } })
      .then((res) => setMeta(res.data.data))
      .catch((err: { response?: { status?: number; data?: { message?: string } } }) => {
        if (err.response?.status === 403) {
          setMeta({ module_enabled: false } as TeacherAddressMeta);
          setError(
            err.response.data?.message ??
              lbl('MSG_MODULE_NOT_ENABLED', 'This module is not enabled.')
          );
        } else {
          setError(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
        }
      })
      .finally(() => setLoading(false));
  }, [langId, lbl]);

  useEffect(() => {
    load();
  }, [load]);

  const openForm = (row: TeacherAddressRecord | null) => {
    if (!meta) return;
    showModal(
      <TeacherAddressModal
        meta={meta}
        editing={row}
        initialForm={row ? addressToForm(row) : emptyAddressForm()}
        onClose={closeModal}
        onSaved={(data) => setMeta(data)}
      />,
      { size: 'modal-lg' }
    );
  };

  const remove = async (id: number) => {
    if (!window.confirm(lbl('LBL_DO_YOU_WANT_TO_REMOVE', 'Do you want to remove?'))) return;
    try {
      const res = await api.delete<{ data: TeacherAddressMeta }>(
        `/account/teacher/addresses/${id}`,
        { params: { lang_id: langId } }
      );
      setMeta(res.data.data);
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong');
      window.alert(msg);
    }
  };

  if (loading) {
    return <p className="padding-6 color-secondary">{lbl('LBL_LOADING', 'Loading...')}</p>;
  }

  if (error && !meta?.addresses?.length) {
    return <p className="padding-6 color-secondary">{error}</p>;
  }

  if (!meta || meta.module_enabled === false) {
    return (
      <p className="padding-6 color-secondary">
        {error || lbl('MSG_MODULE_NOT_ENABLED', 'Offline sessions / addresses are not enabled.')}
      </p>
    );
  }

  if (!meta.country_id) {
    return (
      <p className="padding-6 color-secondary">
        {lbl(
          'MSG_PLEASE_SELECT_COUNTRY_FROM_PROFILE_INFO',
          'Please select your country in Personal info first.'
        )}
      </p>
    );
  }

  if (!meta.states.length) {
    return (
      <p className="padding-6 color-secondary">
        {lbl('MSG_NO_STATES_FOUND_FOR_YOUR_COUNTRY', 'No states found for your country.')}
      </p>
    );
  }

  const records = meta.addresses ?? [];
  const lblAddress = lbl('LBL_Address', 'Address');
  const lblAction = lbl('LBL_ACTIONS', 'Actions');

  return (
    <>
      <div className="content-panel__head">
        <div className="d-flex align-items-center justify-content-between">
          <div>
            <h5>{lbl('LBL_MANAGE_ADDRESSES', 'Manage addresses')}</h5>
          </div>
          <div>
            <a
              href="javascript:void(0);"
              className="btn btn--small btn--bordered color-secondary"
              onClick={(e) => {
                e.preventDefault();
                openForm(null);
              }}
            >
              {lbl('LBL_ADD_NEW', 'Add new')}
            </a>
          </div>
        </div>
      </div>
      <div className="content-panel__body">
        <div className="form">
          <div className="form__body p-0">
            <div className="table-scroll">
              <table className="table table--bordered table--responsive">
                <tbody>
                  <tr className="title-row">
                    <th>{lblAddress}</th>
                    <th>{lblAction}</th>
                  </tr>
                  {records.map((address) => (
                    <tr key={address.id} id={`address-${address.id}`}>
                      <td>
                        <div className="address-group">
                          <div className="address-group__icon">
                            <svg
                              className="icon"
                              xmlns="http://www.w3.org/2000/svg"
                              viewBox="0 0 24 24"
                              fill="currentColor"
                            >
                              <path d="M12 20.8995L16.9497 15.9497C19.6834 13.2161 19.6834 8.78392 16.9497 6.05025C14.2161 3.31658 9.78392 3.31658 7.05025 6.05025C4.31658 8.78392 4.31658 13.2161 7.05025 15.9497L12 20.8995ZM12 23.7279L5.63604 17.364C2.12132 13.8492 2.12132 8.15076 5.63604 4.63604C9.15076 1.12132 14.8492 1.12132 18.364 4.63604C21.8787 8.15076 21.8787 13.8492 18.364 17.364L12 23.7279ZM12 13C13.1046 13 14 12.1046 14 11C14 9.89543 13.1046 9 12 9C10.8954 9 10 9.89543 10 11C10 12.1046 10.8954 13 12 13ZM12 15C9.79086 15 8 13.2091 8 11C8 8.79086 9.79086 7 12 7C14.2091 7 16 8.79086 16 11C16 13.2091 14.2091 15 12 15Z" />
                            </svg>
                          </div>
                          <div className="address-group__content">
                            <div className="d-flex mb-1">
                              <h6 className="me-2">{address.type_label}</h6>
                              {address.is_default && (
                                <span className="badge badge--round badge--small m-0 bg-dark">
                                  {lbl('LBL_DEFAULT', 'Default')}
                                </span>
                              )}
                            </div>
                            {address.formatted}
                            <br />
                            {address.phone}
                          </div>
                        </div>
                      </td>
                      <td>
                        <div className="flex-cell">
                          <div className="flex-cell__content">
                            <div className="actions-group actions-group--address">
                              <a
                                href="javascript:void(0);"
                                className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                                onClick={(e) => {
                                  e.preventDefault();
                                  openForm(address);
                                }}
                              >
                                <DashboardSpriteIcon id="edit" className="icon icon--issue icon--small" />
                                <div className="tooltip tooltip--top bg-black">
                                  {lbl('LBL_EDIT', 'Edit')}
                                </div>
                              </a>
                              <a
                                href="javascript:void(0);"
                                className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                                onClick={(e) => {
                                  e.preventDefault();
                                  remove(address.id);
                                }}
                              >
                                <DashboardSpriteIcon id="trash" className="icon icon--issue icon--small" />
                                <div className="tooltip tooltip--top bg-black">
                                  {lbl('LBL_DELETE', 'Delete')}
                                </div>
                              </a>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  ))}
                  {records.length === 0 && (
                    <tr>
                      <td colSpan={2}>{lbl('LBL_NO_RECORD_FOUND', 'No record found')}</td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
