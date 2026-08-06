import { useCallback, useEffect, useState } from 'react';
import { api } from '../../../api/client';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { useSite } from '../../context/SiteContext';
import { useModal } from '../../context/ModalContext';
import { ForumSpriteIcon } from '../components/ForumSpriteIcon';
import { DashboardFlexCell } from '../components/DashboardFlexCell';
import { DashboardNoRecord } from '../components/DashboardNoRecord';
import { ForumTagRequestModal } from '../forum/ForumTagRequestModal';
import { DASHBOARD_MODAL_FORM_OPTS } from '../dashboardModalOptions';

type TagRequestRow = {
  id: number;
  serial: number;
  name: string;
  language_id: number;
  language_label: string;
  status: number;
  status_label: string;
  status_class: string;
  can_edit: boolean;
};

/** Legacy dashboard/views/forum/tag-requests/index.php + search.php */
export function DashboardForumTagRequestsPage() {
  const { lbl } = useSite();
  const { showModal, closeModal } = useModal();
  const [rows, setRows] = useState<TagRequestRow[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    setLoading(true);
    api
      .get<{ data: TagRequestRow[] }>('/dashboard/forum-tag-requests')
      .then((res) => setRows(res.data.data))
      .catch(() => setRows([]))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const openForm = (requestId = 0) => {
    showModal(<ForumTagRequestModal requestId={requestId} onClose={closeModal} onSaved={load} />, DASHBOARD_MODAL_FORM_OPTS);
  };

  const subjectsLabel = lbl('LBL_language', 'Subjects');

  return (
    <div className="container container--fixed">
      <div className="page__head">
        <div className="row align-items-center justify-content-between">
          <div className="col-sm-6">
            <h1>{lbl('LBL_Requested_Tags', 'Requested tags')}</h1>
            <p className="m-0">
              {lbl(
                'LBL_Requested_Tags_subheading',
                'Manage your requested tags listing and status under this section.'
              )}
            </p>
          </div>
          <div className="col-sm-auto">
            <div className="buttons-group d-flex align-items-center gap-2">
              <button
                type="button"
                className="btn btn--bordered color-secondary"
                onClick={() => openForm(0)}
              >
                <ForumSpriteIcon id="icon-add_ftag" className="icon icon--icon-add_ftag icon--small me-2" />
                {lbl('LBL_Request_new_tag', 'Request new tag')}
              </button>
            </div>
          </div>
        </div>
      </div>
      <div className="page__body">
        <div className="page-content">
          <div id="tag-requests-listing" className="table-scroll">
            {loading ? (
              <p className="color-secondary padding-6">{lbl('LBL_LOADING', 'Loading...')}</p>
            ) : rows.length === 0 ? (
              <DashboardNoRecord
                action={
                  <button type="button" className="btn btn--primary mt-3" onClick={() => openForm(0)}>
                    {lbl('LBL_Request_new_tag', 'Request new tag')}
                  </button>
                }
              />
            ) : (
              <table className="table table--styled table--responsive table--aligned-middle">
                <tr className="title-row">
                  <th>{lbl('LBL_Sr_No', 'Sr. No.')}</th>
                  <th>{lbl('LBL_Tag_name', 'Tag name')}</th>
                  <th>{subjectsLabel}</th>
                  <th>{lbl('LBL_Status', 'Status')}</th>
                  <th>{lbl('LBL_Actions', 'Actions')}</th>
                </tr>
                {rows.map((row) => (
                  <tr key={row.id} id={`reqtag_${row.id}`}>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_Sr_No', 'Sr. No.')}>{row.serial}</DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_Tag_name', 'Tag name')}>{row.name}</DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={subjectsLabel}>{row.language_label}</DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_Status', 'Status')}>
                        <span className={`badge ${row.status_class} badge--curve badge--small ms-0`}>
                          {row.status_label}
                        </span>
                      </DashboardFlexCell>
                    </td>
                    <td>
                      <DashboardFlexCell label={lbl('LBL_Actions', 'Actions')}>
                        {row.can_edit ? (
                          <div className="actions-group">
                            <a
                              href="#"
                              className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
                              title={lbl('LBL_Forum_Edit_tag_Request', 'Edit tag request')}
                              data-row_id={row.id}
                              onClick={(e) => {
                                e.preventDefault();
                                openForm(row.id);
                              }}
                            >
                              <DashboardSpriteIcon id="edit" className="icon icon--cancel icon--small" />
                            </a>
                          </div>
                        ) : null}
                      </DashboardFlexCell>
                    </td>
                  </tr>
                ))}
              </table>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
