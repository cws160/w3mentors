import { Link } from 'react-router-dom';
import { AdminSpriteIcon } from './AdminSpriteIcon';

type Props = {
  adminId: number;
  loggedInAdminId: number;
  canEdit: boolean;
  canViewPermissions: boolean;
  labels: {
    edit: string;
    changePassword: string;
    permissions: string;
  };
  onEdit: (adminId: number) => void;
  onChangePassword: (adminId: number) => void;
};

export function AdminManageAdminsActions({
  adminId,
  loggedInAdminId,
  canEdit,
  canViewPermissions,
  labels,
  onEdit,
  onChangePassword,
}: Props) {
  if (adminId === 1 || adminId === loggedInAdminId) {
    return null;
  }

  return (
    <ul className="actions">
      {canEdit ? (
        <>
          <li>
            <a
              href="javascript:void(0)"
              title={labels.edit}
              data-bs-toggle="tooltip"
              data-placement="top"
              onClick={(e) => {
                e.preventDefault();
                onEdit(adminId);
              }}
            >
              <AdminSpriteIcon icon="edit" />
            </a>
          </li>
          <li>
            <a
              href="javascript:void(0)"
              title={labels.changePassword}
              data-bs-toggle="tooltip"
              data-placement="top"
              onClick={(e) => {
                e.preventDefault();
                onChangePassword(adminId);
              }}
            >
              <AdminSpriteIcon icon="password" />
            </a>
          </li>
        </>
      ) : null}
      {canViewPermissions && adminId > 1 && adminId !== loggedInAdminId ? (
        <li>
          <Link
            to={`/admin/admin-users/${adminId}/permissions`}
            title={labels.permissions}
            data-bs-toggle="tooltip"
            data-placement="top"
          >
            <AdminSpriteIcon icon="user-permission" />
          </Link>
        </li>
      ) : null}
    </ul>
  );
}
