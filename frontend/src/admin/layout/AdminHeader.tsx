import { useEffect, useState, type MouseEvent } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { useAdminAuth } from '../context/AdminAuthContext';
import { applyBrandText } from '../../utils/branding';
import { adminApi } from '../api/adminClient';

type Props = {
  title?: string;
};

function alertDismissed(name: string) {
  return document.cookie.split(';').some((c) => c.trim().startsWith(`${name}=`));
}

export function AdminHeader({ title }: Props) {
  const { lbl, languages, langId, setLangId } = useSite();
  const { admin, logout, privileges, reportGeneratedAt, setReportGeneratedAt } = useAdminAuth();
  const { meta } = useAdminPageMeta();
  const location = useLocation();
  const [warningDismissed, setWarningDismissed] = useState(false);
  const [regeneratingStats, setRegeneratingStats] = useState(false);
  const [imageTick, setImageTick] = useState(Date.now());

  const pageTitle = meta.title || title || lbl('NAV_DASHBOARD', 'Dashboard');
  const alertName = meta.plangId ? `alert_${meta.plangId}` : '';
  const adminName = applyBrandText(admin?.name ?? '');
  const adminImageSrc = `/api/v1/image/show/15/${admin?.id ?? 0}/SMALL?t=${imageTick}`;
  const showRegenerateStats = location.pathname === '/admin' && Boolean(privileges.canViewSalesReportRegenerate);
  const regenerateStatsTitle = `${lbl('LBL_REGENERATE_STATS', 'Regenerate stats')}${
    reportGeneratedAt ? ` (${reportGeneratedAt})` : ''
  }`;

  useEffect(() => {
    if (alertName) {
      setWarningDismissed(alertDismissed(alertName));
    }
  }, [alertName]);

  useEffect(() => {
    const refreshProfileImage = () => setImageTick(Date.now());
    window.addEventListener('admin:profile-image-updated', refreshProfileImage);
    return () => window.removeEventListener('admin:profile-image-updated', refreshProfileImage);
  }, []);

  const dismissWarning = () => {
    if (alertName) {
      document.cookie = `${alertName}=1; path=/; max-age=31536000`;
      setWarningDismissed(true);
    }
  };

  const regenerateStats = (event: MouseEvent<HTMLAnchorElement>) => {
    event.preventDefault();
    if (regeneratingStats) {
      return;
    }

    setRegeneratingStats(true);
    void adminApi
      .regenerateSalesReport()
      .then((res) => {
        const generatedAt = res.data.report_generated_at ?? res.data.regendatedtime ?? null;
        if (generatedAt) {
          setReportGeneratedAt(generatedAt);
        }
        window.setTimeout(() => window.location.reload(), 1000);
      })
      .catch(() => {
        window.alert(lbl('LBL_SOMETHING_WENT_WRONG', 'Something went wrong'));
      })
      .finally(() => setRegeneratingStats(false));
  };

  return (
    <header className="main-header mainHeaderJs">
      <div className="container-fluid">
        <div className="main-header-inner">
          <div className="page-title">
            <h1>{pageTitle}</h1>
            {meta.summary ? (
              <span className="page-title-sub">
                <span dangerouslySetInnerHTML={{ __html: meta.summary }} />
                {meta.warning ? (
                  <a href="javascript:void(0)" className="openAlertJs" title={lbl('LBL_ALERT', 'Alert')}>
                    <svg className="svg" width="20" height="20">
                      <use xlinkHref="/manager/views/images/retina/sprite.svg#alert" />
                    </svg>
                  </a>
                ) : null}
              </span>
            ) : null}
          </div>
          <div className="main-header-toolbar">
            <div className="header-action">
              {showRegenerateStats ? (
                <div className="header-action__item">
                  <a
                    className="header-action__trigger"
                    title={regenerateStatsTitle}
                    href="#regenerate-stats"
                    onClick={regenerateStats}
                    aria-disabled={regeneratingStats}
                  >
                    <span className="icon">
                      <svg className="svg" width="20" height="20">
                        <use xlinkHref="/manager/views/images/retina/sprite.svg#icon-stats" />
                      </svg>
                    </span>
                  </a>
                </div>
              ) : null}
              <div className="header-action__item">
                <a
                  className="header-action__trigger"
                  title={lbl('LBL_View_Portal', 'View Portal')}
                  href="/"
                  target="_blank"
                  rel="noreferrer"
                >
                  <span className="icon">
                    <svg className="svg" width="20" height="20">
                      <use xlinkHref="/manager/views/images/retina/sprite.svg#icon-store" />
                    </svg>
                  </span>
                </a>
              </div>
              <div className="header-action__item">
                <a className="header-action__trigger" title={lbl('LBL_Clear_Cache', 'Clear cache')} href="#cache">
                  <span className="icon">
                    <svg className="svg" width="20" height="20">
                      <use xlinkHref="/manager/views/images/retina/sprite.svg#icon-cache" />
                    </svg>
                  </span>
                </a>
              </div>
              {languages.length > 0 ? (
                <div className="header-action__item">
                  <div className="dropdown">
                    <a
                      className="dropdown-toggle header-action__trigger no-after"
                      data-bs-toggle="dropdown"
                      href="#language"
                    >
                      <span className="icon">
                        <svg className="svg" width="20" height="20">
                          <use xlinkHref="/manager/views/images/retina/sprite.svg#icon-lang" />
                        </svg>
                      </span>
                    </a>
                    <div className="header-action__target dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim dropDownMenuBlockClose">
                      <div className="pt-3 pb-0 px-4">
                        <h6 className="mb-0">{lbl('LBL_Admin_Select_Language', 'Select Language')}</h6>
                      </div>
                      <nav className="nav nav--header-account">
                        {languages.map((language) => (
                          <div key={language.id} className={langId === language.id ? 'is--active' : undefined}>
                            <a
                              href="#language"
                              onClick={(event) => {
                                event.preventDefault();
                                setLangId(language.id);
                              }}
                            >
                              {language.name}
                            </a>
                          </div>
                        ))}
                      </nav>
                    </div>
                  </div>
                </div>
              ) : null}
              <div className="header-action__item">
                <div className="dropdown header-account">
                  <a
                    className="dropdown-toggle header-action__trigger no-before no-after"
                    data-bs-toggle="dropdown"
                    href="#account"
                  >
                    <span className="header-account__img">
                      <img
                        alt=""
                        src={adminImageSrc}
                        onError={(e) => {
                          e.currentTarget.onerror = null;
                          e.currentTarget.src = '/images/no-image-user.png';
                        }}
                      />
                    </span>
                  </a>
                  <div className="header-action__target dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim dropDownMenuBlockClose">
                    <div className="header-account__avtar">
                      <div className="profile">
                        <div className="profile__img">
                          <img
                            alt=""
                            src={adminImageSrc}
                            onError={(e) => {
                              e.currentTarget.onerror = null;
                              e.currentTarget.src = '/images/no-image-user.png';
                            }}
                          />
                        </div>
                        <div className="profile__detail">
                          <h6>
                            {lbl('LBL_HI', 'Hi')}, {adminName}
                          </h6>
                        </div>
                      </div>
                    </div>
                    <div className="separator m-0" />
                    <nav className="nav nav--header-account">
                      <Link to="/admin/profile">{lbl('LBL_View_Profile', 'View Profile')}</Link>
                      <Link to="/admin/profile/change-password">
                        {lbl('LBL_Change_Password', 'Change Password')}
                      </Link>
                      <a
                        href="#logout"
                        onClick={(e) => {
                          e.preventDefault();
                          void logout();
                        }}
                      >
                        {lbl('LBL_Logout', 'Logout')}
                      </a>
                    </nav>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      {meta.warning && !warningDismissed ? (
        <div className="alert alert-solid-warning fade alertWarningJs show rounded-0" role="alert">
          <div className="alert-text">{meta.warning}</div>
          <div className="alert-close">
            <button type="button" className="btn-close" aria-label="Close" onClick={dismissWarning} />
          </div>
        </div>
      ) : null}
      {meta.recommendations && !warningDismissed ? (
        <div className="alert alert-solid-info fade show rounded-0" role="alert">
          <div className="alert-text">{meta.recommendations}</div>
        </div>
      ) : null}
    </header>
  );
}
