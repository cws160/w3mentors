export type SitemapLink = { label: string; url: string };

export type SitemapGroup = { title: string; links: SitemapLink[] };

export type SitemapSection = {
  language: string | null;
  groups: SitemapGroup[];
};

export type SitemapHtmlData = {
  sections: SitemapSection[];
  public_url: string;
};

type Props = {
  data: SitemapHtmlData;
  lbl: (key: string, fallback: string) => string;
};

export function SitemapHtmlContent({ data, lbl }: Props) {
  return (
    <section className="section section--page">
      <div className="container container--fixed">
        {data.sections.map((section, sectionIndex) => (
          <div key={section.language ?? `section-${sectionIndex}`}>
            {section.language ? (
              <div className="row">
                <div className="col-xl-12 col-lg-12 col-md-12">
                  <h2>
                    {lbl('LBL_{language}_URLS', '{language} URLs').replace(
                      '{language}',
                      section.language,
                    )}
                  </h2>
                </div>
              </div>
            ) : null}
            <div className="row">
              {section.groups.map((group) => (
                <div className="col-xl-3 col-lg-3 col-md-3" key={group.title}>
                  <h5 style={{ fontSize: '1.6em' }}>{group.title}</h5>
                  <ol style={{ margin: '0 0 30px 0', padding: 0, listStyle: 'inside decimal' }}>
                    {group.links.map((link) => (
                      <li key={`${group.title}-${link.url}`}>
                        <a href={link.url}>{link.label}</a>
                      </li>
                    ))}
                  </ol>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}
