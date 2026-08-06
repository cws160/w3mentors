type Props = {
  id: number | string;
  active: boolean;
  disabled?: boolean;
  activeLabel?: string;
  inactiveLabel?: string;
  confirmMessage?: string;
  onToggle: (next: boolean) => Promise<void>;
};

export function AdminStatusSwitch({
  id,
  active,
  disabled,
  activeLabel = 'Active',
  inactiveLabel = 'Inactive',
  confirmMessage,
  onToggle,
}: Props) {
  return (
    <label
      id={String(id)}
      className={`statustab status_${id} ${active ? 'active' : ''}`}
      onClick={(e) => {
        e.preventDefault();
        if (disabled) return;
        if (confirmMessage && !window.confirm(confirmMessage)) return;
        void onToggle(!active);
      }}
      onKeyDown={() => {}}
    >
      <span data-off={activeLabel} data-on={inactiveLabel} className="switch-labels" />
      <span className={`switch-handles ${disabled ? 'disabled' : ''}`} />
    </label>
  );
}
