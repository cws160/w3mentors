import { Link } from 'react-router-dom';
import { AdminSpriteIcon } from './AdminSpriteIcon';

type Props = {
  classId: number;
  classType: number;
  module: 'group-classes' | 'package-classes';
  labels: {
    viewDetails: string;
    classes: string;
  };
  onViewDetails: (classId: number) => void;
};

export function AdminGroupClassesActions({ classId, classType, module, labels, onViewDetails }: Props) {
  if (!classId || Number.isNaN(classId)) {
    return null;
  }

  return (
    <div className="align-right">
      <ul className="actions">
        {module === 'group-classes' ? (
          <li title={labels.viewDetails} data-bs-toggle="tooltip" data-placement="top">
            <a
              href="javascript:void(0)"
              onClick={(e) => {
                e.preventDefault();
                onViewDetails(classId);
              }}
            >
              <AdminSpriteIcon icon="view" />
            </a>
          </li>
        ) : null}
        {module === 'package-classes' ? (
          <li title={labels.classes} data-bs-toggle="tooltip" data-placement="top">
            <Link to={`/admin/group-classes?grpcls_parent=${classId}`}>
              <AdminSpriteIcon icon="view" />
            </Link>
          </li>
        ) : null}
        {module === 'group-classes' && classType === 2 ? (
          <li title={labels.classes} data-bs-toggle="tooltip" data-placement="top">
            <Link to={`/admin/package-classes?grpcls_parent=${classId}`}>
              <AdminSpriteIcon icon="classes" />
            </Link>
          </li>
        ) : null}
      </ul>
    </div>
  );
}
