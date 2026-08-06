import { useEffect, useMemo } from 'react';
import { BRAND_NAME, siteLogoSrc } from '../../utils/branding';
import { readStoredUser } from '../../utils/authUser';
import { bindW3MentorsUiHandlers } from '../lib/w3mentors-ui';
import { Link, NavLink, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { isLegacyLoginHref, isLegacySignupHref, useAuthModals } from '../hooks/useAuthModals';
import { useSite } from '../context/SiteContext';
import { FOOTER_PAYMENT_ICONS } from '../utils/paymentIcons';
import { SpriteIcon } from '../components/SpriteIcon';
import { HeaderGuestControls, HeaderUserControls } from './HeaderUserControls';
import { HeaderLanguageCurrency } from './HeaderLanguageCurrency';
import { HeaderSearchPanel } from './HeaderSearchPanel';
import { filterNavLinksForUser } from '../utils/navigation';
import { legacyBodyClass } from '../utils/legacyBodyClass';
import { useMarketingStyles } from '../hooks/useMarketingStyles';
import '../w3mentors-overrides.css';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';

export function W3MentorsLayout() {
  const { user: authUser, logout, loading: authLoading } = useAuth();
  const user = useMemo(() => {
    if (authUser?.id) {
      return authUser;
    }
    return readStoredUser();
  }, [authUser]);

  const hasAuthToken =
    typeof window !== 'undefined' && !!localStorage.getItem('auth_token');
  const showLoggedInHeader = !!user?.id || (authLoading && hasAuthToken);
  const { navigation, site, lbl, social, modules } = useSite();
  const { openLoginModal, openSignupModal } = useAuthModals();
  const location = useLocation();

  useMarketingStyles(true);

  const legacyBody = legacyBodyClass(location.pathname);

  useEffect(() => {
    document.body.dir = 'ltr';
    bindW3MentorsUiHandlers();

    const preserve = ['modal-open', 'is-dashboard-route'].filter((cls) =>
      document.body.classList.contains(cls)
    );
    document.body.className = [legacyBody, ...preserve].filter(Boolean).join(' ');

    return () => {
      document.body.className = preserve.join(' ');
    };
  }, [legacyBody]);

  useEffect(() => {
    document.documentElement.classList.toggle('user-logged-in', showLoggedInHeader);
    return () => document.documentElement.classList.remove('user-logged-in');
  }, [showLoggedInHeader]);

  useEffect(() => {
    const body = document.getElementById('body');
    if (!body) return;

    const onAuthLinkClick = (event: MouseEvent) => {
      const target = event.target as HTMLElement | null;
      const anchor = target?.closest('a');
      if (!anchor) return;

      const onclick = (anchor.getAttribute('onclick') ?? '').toLowerCase();
      if (onclick.includes('signupform')) {
        event.preventDefault();
        openSignupModal();
        return;
      }
      if (onclick.includes('signinform')) {
        event.preventDefault();
        openLoginModal();
        return;
      }

      const href = anchor.getAttribute('href') ?? '';
      if (isLegacySignupHref(href)) {
        event.preventDefault();
        openSignupModal();
        return;
      }
      if (isLegacyLoginHref(href)) {
        event.preventDefault();
        openLoginModal();
      }
    };

    body.addEventListener('click', onAuthLinkClick);
    return () => body.removeEventListener('click', onAuthLinkClick);
  }, [openLoginModal, openSignupModal]);

  const headerLinks = filterNavLinksForUser(
    (navigation?.header ?? []).flatMap((group) => group.pages ?? []),
    showLoggedInHeader
  );
  const footerOne = navigation?.footer?.one ?? [];
  const footerTwo = navigation?.footer?.two ?? [];

  return (
    <>
      <div id="app-alert" className="alert-position alert-position--top-right fadeInDown animated" />
      <>
          <header className="header">
        <div className="header-primary">
          <div className="container">
            <div className="header-flex d-flex justify-content-between align-items-center">
              <div className="header__left">
                {headerLinks.length > 0 ? (
                  <span className="cursor-pointer toggle toggle--nav toggle--nav-js">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M3 4H21V6H3V4ZM3 11H21V13H3V11ZM3 18H21V20H3V18Z" />
                    </svg>
                  </span>
                ) : (
                  <span className="toggle toggle--nav" />
                )}
                <div className="header__logo">
                  <Link to="/">
                    <img src={siteLogoSrc()} alt={site?.name ?? BRAND_NAME} />
                  </Link>
                </div>
              </div>
              <div className="header__middle">
                {headerLinks.length > 0 && (
                  <>
                    <span className="overlay overlay--nav toggle--nav-js is-active" />
                    <nav className="menu nav--primary-offset">
                      <ul>
                        {headerLinks.map((link) => (
                          <li key={link.id} className="menu__item">
                            <NavLink to={link.url} target={link.target}>
                              {link.caption}
                            </NavLink>
                          </li>
                        ))}
                      </ul>
                    </nav>
                  </>
                )}
              </div>
              <div className="header__right">
                <div className="header-controls">
                  <div className="header-controls__item">
                    <Link to="/" className="header-controls__action mobile-action">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                        <path d="M13 19h6V9.978l-7-5.444-7 5.444V19h6v-6h2v6zm8 1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.49a1 1 0 0 1 .386-.79l8-6.222a1 1 0 0 1 1.228 0l8 6.222a1 1 0 0 1 .386.79V20z" />
                      </svg>
                      <span className="mobile-action-label">{lbl('LBL_HOME', 'Home')}</span>
                    </Link>
                  </div>
                  <HeaderLanguageCurrency />
                  {showLoggedInHeader && user ? (
                    <HeaderUserControls
                      user={user}
                      lbl={lbl}
                      onLogout={() => logout()}
                      coursesEnabled={modules?.courses !== false}
                    />
                  ) : showLoggedInHeader ? (
                    <div className="header-controls__item header-action" aria-hidden="true" />
                  ) : (
                    <HeaderGuestControls
                      lbl={lbl}
                      openLoginModal={openLoginModal}
                      openSignupModal={openSignupModal}
                    />
                  )}
                </div>
              </div>
            </div>
            <HeaderSearchPanel />
          </div>
        </div>
          </header>

          <div id="body" className="body">
            <Outlet />
          </div>

          <footer id="footer" className="footer">
        <div className="container container--md">
          <div className="footer-middle">
            <div className="footer-row">
              {footerOne.map((group, idx) => (
                <div key={`f1-${idx}`} className="footer-colum toggle-group">
                  <div className="footer-colum__trigger toggle-trigger-js">
                    <h5>{group.parent}</h5>
                  </div>
                  <div className="footer-colum__target toggle-target-js">
                    <div className="footer-list">
                      <ul>
                        {(group.pages ?? []).map((link) => (
                          <li key={link.id}>
                            <Link to={link.url} target={link.target}>
                              {link.caption}
                            </Link>
                          </li>
                        ))}
                      </ul>
                    </div>
                  </div>
                </div>
              ))}
              {footerTwo.map((group, idx) => (
                <div key={`f2-${idx}`} className="footer-colum toggle-group">
                  <div className="footer-colum__trigger toggle-trigger-js">
                    <h5>{group.parent}</h5>
                  </div>
                  <div className="footer-colum__target toggle-target-js">
                    <div className="footer-list">
                      <ul>
                        {(group.pages ?? []).map((link) => (
                          <li key={link.id}>
                            <Link to={link.url} target={link.target} className="bullet-list__action">
                              {link.caption}
                            </Link>
                          </li>
                        ))}
                      </ul>
                    </div>
                  </div>
                </div>
              ))}
              {(site?.address || site?.email || site?.phone) && (
                <div className="footer-colum toggle-group footer-colum--address">
                  <div className="footer-colum__trigger toggle-trigger-js">
                    <h5>{lbl('LBL_GET_IN_TOUCH', 'Get in touch')}</h5>
                  </div>
                  <div className="footer-colum__target toggle-target-js">
                    <div className="footer-list">
                      <ul>
                        {site?.address && (
                          <li>
                            <div className="contact-meta-info">
                              <span className="value">{site.address}</span>
                            </div>
                          </li>
                        )}
                        {site?.email && (
                          <li>
                            <div className="contact-meta-info">
                              <span className="value">
                                <a href={`mailto:${site.email}`}>{site.email}</a>
                              </span>
                            </div>
                          </li>
                        )}
                        {site?.phone && (
                          <li>
                            <div className="contact-meta-info">
                              <span className="value">
                                <a href={`tel:${site.phone}`}>{site.phone}</a>
                              </span>
                            </div>
                          </li>
                        )}
                      </ul>
                    </div>
                  </div>
                </div>
              )}
              <div className="footer-colum toggle-group">
                <div className="footer-group py-md-0 py-4">
                  {Object.entries(social).some(([, url]) => url) && (
                    <div className="footer-list">
                      <ul className="socials-list">
                        {Object.entries(social)
                          .filter(([, url]) => url)
                          .map(([name, link]) => {
                            const iconId = name === 'x' ? 'twitter' : name;
                            return (
                              <li key={name}>
                                <a
                                  className="social-link"
                                  href={link}
                                  target="_blank"
                                  rel="noreferrer"
                                  aria-label={name === 'x' ? 'twitter' : name}
                                >
                                  <SpriteIcon id={iconId} className={`icon icon--${iconId}`} />
                                </a>
                              </li>
                            );
                          })}
                      </ul>
                    </div>
                  )}
                  <div className="payment-cards">
                    {FOOTER_PAYMENT_ICONS.map((icon) => (
                      <img key={icon.src} src={icon.src} width={36} height={23} alt={icon.alt} loading="lazy" />
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="footer-lower">
            <div className="row justify-content-between align-items-center">
              <div className="col-md-auto">
                <p>
                  © {new Date().getFullYear()} <span className="bold-600">{site?.name ?? BRAND_NAME}</span>
                </p>
              </div>
            </div>
          </div>
        </div>
          </footer>
          <a href="#top" className="gototop" title="Back to Top" />
      </>
    </>
  );
}
