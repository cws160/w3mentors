import type { ReactNode } from 'react';

export type AdminTabbedModalTab = 'general' | 'media' | `lang-${number}`;

export type AdminTabLanguage = { id: number; name: string };

type Props = {
  activeTab: AdminTabbedModalTab;
  recordId: number;
  siteLanguages: AdminTabLanguage[];
  generalBodyClass?: 'card-body' | 'form-edit-body';
  langBodyClass?: 'card-body' | 'form-edit-body';
  mediaBodyClass?: 'card-body' | 'form-edit-body';
  showMediaTab?: boolean;
  onSelectGeneral: () => void;
  onSelectLang: (langId: number) => void;
  onSelectMedia?: () => void;
  loading?: boolean;
  error?: string;
  extraTabs?: ReactNode;
  children: ReactNode;
  lbl: (key: string, fallback?: string) => string;
};

export function AdminTabbedModalLayout({
  activeTab,
  recordId,
  siteLanguages,
  generalBodyClass = 'form-edit-body',
  langBodyClass = 'form-edit-body',
  mediaBodyClass = 'form-edit-body',
  showMediaTab = false,
  onSelectGeneral,
  onSelectLang,
  onSelectMedia,
  loading = false,
  error = '',
  extraTabs,
  children,
  lbl,
}: Props) {
  const bodyClass =
    activeTab === 'general'
      ? generalBodyClass
      : activeTab === 'media'
        ? mediaBodyClass
        : langBodyClass;

  return (
    <>
      <div className="form-edit-head">
        <nav className="tab tab-inline">
          <ul>
            <li>
              <a
                href="javascript:void(0)"
                className={activeTab === 'general' ? 'active' : ''}
                onClick={(e) => {
                  e.preventDefault();
                  onSelectGeneral();
                }}
              >
                {lbl('LBL_GENERAL', 'General')}
              </a>
            </li>
            {siteLanguages.map((lang) => (
              <li key={lang.id} className={recordId < 1 ? 'is-inactive' : ''}>
                <a
                  href="javascript:void(0)"
                  className={activeTab === `lang-${lang.id}` ? 'active' : ''}
                  onClick={(e) => {
                    e.preventDefault();
                    onSelectLang(lang.id);
                  }}
                >
                  {lang.name}
                </a>
              </li>
            ))}
            {showMediaTab ? (
              <li className={`mediaTab${recordId < 1 ? ' is-inactive' : ''}`}>
                <a
                  href="javascript:void(0)"
                  className={`media-js${activeTab === 'media' ? ' active' : ''}`}
                  onClick={(e) => {
                    e.preventDefault();
                    if (recordId > 0) {
                      onSelectMedia?.();
                    }
                  }}
                >
                  {lbl('LBL_MEDIA', 'Media')}
                </a>
              </li>
            ) : null}
            {extraTabs}
          </ul>
        </nav>
      </div>
      <div className={bodyClass}>
        {error ? <div className="alert alert-danger m-4">{error}</div> : null}
        {loading ? (
          <div className="table-processing loaderJs">
            <div className="spinner spinner--sm spinner--brand" />
          </div>
        ) : (
          children
        )}
      </div>
    </>
  );
}
