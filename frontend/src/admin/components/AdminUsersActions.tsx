import { AdminSpriteIcon } from './AdminSpriteIcon';

type ActionBtn = {
  title: string;
  icon: string;
  onClick?: () => void;
};

type Props = {
  userId: number;
  canEdit: boolean;
  labels: {
    view: string;
    edit: string;
    login: string;
    transactions: string;
    addresses: string;
    changePassword: string;
    actions: string;
  };
  onView?: (userId: number) => void;
  onEdit?: (userId: number) => void;
  onLogin?: (userId: number) => void;
  onTransactions?: (userId: number) => void;
  onAddresses?: (userId: number) => void;
  onChangePassword?: (userId: number) => void;
};

export function AdminUsersActions({
  userId,
  canEdit,
  labels,
  onView,
  onEdit,
  onLogin,
  onTransactions,
  onAddresses,
  onChangePassword,
}: Props) {
  const buttons: ActionBtn[] = [
    { title: labels.view, icon: 'view', onClick: () => onView?.(userId) },
  ];

  if (canEdit) {
    buttons.push({ title: labels.edit, icon: 'edit', onClick: () => onEdit?.(userId) });
    buttons.push({ title: labels.login, icon: 'login', onClick: () => onLogin?.(userId) });
  }

  buttons.push({
    title: labels.transactions,
    icon: 'sync-currency',
    onClick: () => onTransactions?.(userId),
  });
  buttons.push({
    title: labels.addresses,
    icon: 'pin',
    onClick: () => onAddresses?.(userId),
  });

  if (canEdit) {
    buttons.push({
      title: labels.changePassword,
      icon: 'password',
      onClick: () => onChangePassword?.(userId),
    });
  }

  const useDropdown = buttons.length > 3;
  const inlineButtons = useDropdown ? buttons.slice(0, 2) : buttons;
  const dropdownButtons = useDropdown ? buttons.slice(2) : [];

  const linkProps = (btn: ActionBtn, dropdown = false) => ({
    href: 'javascript:void(0)',
    title: dropdown ? undefined : btn.title,
    'data-bs-toggle': dropdown ? undefined : 'tooltip',
    'data-placement': 'top',
    className: dropdown ? 'dropdown-item' : undefined,
    onClick: (e: React.MouseEvent) => {
      e.preventDefault();
      btn.onClick?.();
    },
  });

  return (
    <ul className="actions">
      {inlineButtons.map((btn) => (
        <li key={btn.title} title={btn.title} data-bs-toggle="tooltip">
          <a {...linkProps(btn)}>
            <AdminSpriteIcon icon={btn.icon} />
          </a>
        </li>
      ))}
      {dropdownButtons.length > 0 ? (
        <li className="dropdown dropdown-static" title={labels.actions} data-bs-toggle="tooltip" data-placement="top">
          <a href="javascript:void(0)" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <AdminSpriteIcon icon="more-dots" />
          </a>
          <div className="dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim">
            {dropdownButtons.map((btn) => (
              <a key={btn.title} {...linkProps(btn, true)}>
                <i className="icn">
                  <AdminSpriteIcon icon={btn.icon} />
                </i>
                {btn.title}
              </a>
            ))}
          </div>
        </li>
      ) : null}
    </ul>
  );
}
