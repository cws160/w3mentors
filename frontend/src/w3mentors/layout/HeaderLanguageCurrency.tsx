import { useSite } from '../context/SiteContext';

export function HeaderLanguageCurrency() {
  const { languages, currencies, lbl, langId, currencyId, setLangId, setCurrencyId, site } = useSite();

  if (languages.length === 0 && currencies.length === 0) {
    return <div className="header-controls__item header-dropdown header-dropdown--arrow" />;
  }

  const currentLang = languages.find((l) => l.id === langId) ?? languages[0];
  const currentCurrency =
    currencies.find((c) => c.id === currencyId) ??
    currencies.find((c) => c.code === site?.currency_code) ??
    currencies[0];

  return (
    <div className="header-controls__item header-dropdown header-dropdown--arrow">
      <a
        className="header-controls__action header-dropdown__trigger trigger-js mobile-action"
        href="#languages-nav"
      >
        <svg className="icon icon--globe">
          <use xlinkHref="/images/sprite.svg#globe-icon" />
        </svg>
        <span className="lang mobile-action-label">
          {(currentLang?.code ?? 'EN').toUpperCase()} - {currentCurrency?.code ?? site?.currency_code ?? 'USD'}
        </span>
        <svg className="icon icon--arrow">
          <use xlinkHref="/images/sprite.svg#arrow-black" />
        </svg>
      </a>
      <div id="languages-nav" className="header-dropdown__target">
        <div className="dropdown__cover">
          <div className="settings-group">
            {languages.length > 0 && (
              <div className="settings toggle-group">
                <div className="dropdaown__title">{lbl('LBL_SITE_LANGUAGE', 'Site language')}</div>
                <select
                  name="lang_name"
                  id="lang_name"
                  value={langId}
                  onChange={(e) => setLangId(Number(e.target.value))}
                >
                  {languages.map((language) => (
                    <option key={language.id} value={language.id}>
                      {language.name}
                    </option>
                  ))}
                </select>
              </div>
            )}
            {currencies.length > 0 && (
              <div className="settings toggle-group">
                <div className="dropdaown__title">{lbl('LBL_SITE_CURRENCY', 'Site currency')}</div>
                <select
                  name="currency_name"
                  id="currency_name"
                  value={currencyId ?? currentCurrency?.id ?? ''}
                  onChange={(e) => setCurrencyId(Number(e.target.value))}
                >
                  {currencies.map((currency) => (
                    <option key={currency.id} value={currency.id}>
                      {currency.code}
                    </option>
                  ))}
                </select>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
