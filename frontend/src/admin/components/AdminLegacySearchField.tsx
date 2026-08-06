import type { ReactNode } from 'react';

type Props = {
  col?: number;
  label: string;
  children: ReactNode;
  fieldCoverRef?: React.Ref<HTMLDivElement>;
};

/** Matches legacy Fat form search field markup (field-set / field_label). */
export function AdminLegacySearchField({ col = 3, label, children, fieldCoverRef }: Props) {
  return (
    <div className={`col-md-${col}`}>
      <div className="field-set">
        <div className="caption-wraper">
          <label className="field_label">{label}</label>
        </div>
        <div className="field-wraper">
          <div className="field_cover" ref={fieldCoverRef}>
            {children}
          </div>
        </div>
      </div>
    </div>
  );
}

type ButtonsProps = {
  col?: number;
  searchLabel: string;
  clearLabel: string;
  onClear: () => void;
};

export function AdminLegacySearchButtons({ col = 3, searchLabel, clearLabel, onClear }: ButtonsProps) {
  return (
    <div className={`col-md-${col}`}>
      <div className="field-set">
        <div className="caption-wraper">
          <label className="field_label" />
        </div>
        <div className="field-wraper">
          <div className="field_cover">
            <button type="submit" name="btn_submit" className="btn btn-brand">
              {searchLabel}
            </button>
            <button type="button" name="btn_clear" className="btn btn--secondary" onClick={onClear}>
              {clearLabel}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
