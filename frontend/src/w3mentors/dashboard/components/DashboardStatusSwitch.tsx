type Props = {
  checked: boolean;
  value: number;
  onChange: () => void;
  disabled?: boolean;
};

/** Legacy `switch switch--small` toggle from question/quiz listings. */
export function DashboardStatusSwitch({ checked, value, onChange, disabled }: Props) {
  return (
    <label className="switch switch--small">
      <input
        className="switch__label"
        type="checkbox"
        name="status"
        value={value}
        checked={checked}
        disabled={disabled}
        onChange={onChange}
      />
      <i className="switch__handle bg-green" />
    </label>
  );
}
