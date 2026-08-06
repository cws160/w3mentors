import { FormEvent, useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';

type PermissionOption = {
  value: number;
  label_key: string;
};

type ModuleRow = {
  section_id: number;
  label_key: string;
  permission: number;
};

type PageData = {
  admin: {
    id: number;
    username: string;
    full_name: string;
    email: string;
  };
  modules: ModuleRow[];
  permission_options: PermissionOption[];
};

export function AdminAdminPermissionsPage() {
  const { adminId: adminIdParam } = useParams<{ adminId: string }>();
  const adminId = Number(adminIdParam);
  const { lbl } = useSite();
  const { privileges } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [data, setData] = useState<PageData | null>(null);
  const [applyAllValue, setApplyAllValue] = useState('');
  const [savingSection, setSavingSection] = useState<number | null>(null);

  const canEdit = Boolean(privileges.canEditAdminPermissions);

  const load = useCallback(() => {
    if (!adminId || Number.isNaN(adminId)) {
      setError('Invalid request');
      setLoading(false);
      return;
    }
    setLoading(true);
    setError('');
    adminApi
      .adminPermissionsShow(adminId)
      .then((res) => {
        setData(res.data.data as unknown as PageData);
      })
      .catch((e: unknown) => {
        setData(null);
        setError(
          (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
            'Failed to load permissions',
        );
      })
      .finally(() => setLoading(false));
  }, [adminId]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    setMeta({
      title: lbl('LBL_Admin_Permissions', 'Admin permissions'),
    });
    return () => clearMeta();
  }, [clearMeta, lbl, setMeta]);

  const permissionLabel = (option: PermissionOption) => {
    const fallbacks: Record<string, string> = {
      MSG_None: 'None',
      MSG_Read_Only: 'Read only',
      MSG_Read_and_Write: 'Read and write',
    };
    return lbl(option.label_key, fallbacks[option.label_key] ?? option.label_key);
  };

  const moduleLabel = (row: ModuleRow) => {
    return lbl(row.label_key, row.label_key.replace(/^MSG_/, '').replace(/_/g, ' '));
  };

  const updatePermission = async (sectionId: number, permission: number) => {
    if (!canEdit || !adminId) return;
    setSavingSection(sectionId);
    try {
      await adminApi.updateAdminPermission(adminId, sectionId, permission);
      if (sectionId === 0) {
        load();
      } else {
        setData((prev) => {
          if (!prev) return prev;
          return {
            ...prev,
            modules: prev.modules.map((row) =>
              row.section_id === sectionId ? { ...row, permission } : row,
            ),
          };
        });
      }
    } catch (e: unknown) {
      window.alert(
        (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Update failed',
      );
    } finally {
      setSavingSection(null);
    }
  };

  const applyToAll = async (e: FormEvent) => {
    e.preventDefault();
    if (!applyAllValue) return;
    await updatePermission(0, Number(applyAllValue));
    setApplyAllValue('');
  };

  if (!privileges.canViewAdminPermissions) {
    return (
      <main className="main">
        <div className="container">
          <div className="alert alert-danger">{lbl('LBL_UNAUTHORIZED', 'Unauthorized')}</div>
        </div>
      </main>
    );
  }

  return (
    <main className="main">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">
              <Link to="/admin/admin-users">{lbl('LBL_Manage_Admins', 'Manage Admins')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_Admin_Permissions', 'Admin permissions')}</li>
          </ul>
        </div>

        {error ? <div className="alert alert-danger">{error}</div> : null}

        {data ? (
          <>
            <div className="card">
              <div className="card-head">
                <div className="card-head-label">
                  <h3 className="card-head-title">
                    {lbl('LBL_Admin_User_Listing', 'Admin user listing')} : {data.admin.username}
                  </h3>
                </div>
              </div>
              {canEdit ? (
                <div className="card-body">
                  <form className="form form_horizontal" onSubmit={applyToAll}>
                    <div className="row">
                      <div className="col-md-12">
                        <div className="field-set">
                          <div className="caption-wraper">
                            <label className="field_label">
                              {lbl(
                                'LBL_Select_permission_for_all_modules',
                                'Select permission for all modules',
                              )}
                            </label>
                          </div>
                          <div className="field-wraper">
                            <div className="field_cover">
                              <select
                                className="form-control permissionForAll"
                                value={applyAllValue}
                                onChange={(e) => setApplyAllValue(e.target.value)}
                              >
                                <option value="">{lbl('LBL_SELECT', 'Select')}</option>
                                {data.permission_options.map((option) => (
                                  <option key={option.value} value={option.value}>
                                    {permissionLabel(option)}
                                  </option>
                                ))}
                              </select>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="col-md-12">
                        <div className="field-set">
                          <div className="caption-wraper" />
                          <div className="field-wraper">
                            <div className="field_cover">
                              <button
                                type="submit"
                                className="btn btn-brand"
                                disabled={!applyAllValue || savingSection === 0}
                              >
                                {lbl('LBL_Apply_to_All', 'Apply to all')}
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </form>
                </div>
              ) : null}
            </div>

            <div className="card">
              <div className="card-table">
                <div className="table-responsive" id="listing">
                  {loading ? (
                    <div className="table-processing loaderJs">
                      <div className="spinner spinner--sm spinner--brand" />
                    </div>
                  ) : (
                    <form className="form">
                      <table className="table" width="100%">
                        <thead>
                          <tr>
                            <th>{lbl('LBL_Sr._No', 'Sr. No')}</th>
                            <th>{lbl('LBL_Module', 'Module')}</th>
                            <th>{lbl('LBL_Permissions', 'Permissions')}</th>
                          </tr>
                        </thead>
                        <tbody>
                          {data.modules.length === 0 ? (
                            <tr>
                              <td colSpan={3} className="text-center">
                                {lbl('LBL_NO_RECORDS_FOUND', 'No records found')}
                              </td>
                            </tr>
                          ) : (
                            data.modules.map((row, index) => (
                              <tr key={row.section_id}>
                                <td>{index + 1}</td>
                                <td>{moduleLabel(row)}</td>
                                <td>
                                  <select
                                    className="form-control"
                                    value={String(row.permission)}
                                    disabled={!canEdit || savingSection === row.section_id}
                                    onChange={(e) => {
                                      void updatePermission(row.section_id, Number(e.target.value));
                                    }}
                                  >
                                    {data.permission_options.map((option) => (
                                      <option key={option.value} value={option.value}>
                                        {permissionLabel(option)}
                                      </option>
                                    ))}
                                  </select>
                                </td>
                              </tr>
                            ))
                          )}
                        </tbody>
                      </table>
                    </form>
                  )}
                </div>
              </div>
            </div>
          </>
        ) : loading ? (
          <div className="card">
            <div className="card-table">
              <div className="table-processing loaderJs p-5">
                <div className="spinner spinner--sm spinner--brand" />
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </main>
  );
}
