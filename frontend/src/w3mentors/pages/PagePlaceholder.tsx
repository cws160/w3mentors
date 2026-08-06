import { Link } from 'react-router-dom';

export function PagePlaceholder({ title, legacyPath }: { title: string; legacyPath?: string }) {
  return (
    <section className="section">
      <div className="container container--md">
        <div className="section__header">
          <h1>{title}</h1>
        </div>
        <div className="section__body">
          <div className="card p-4">
            <p>This page is being migrated from the legacy PHP app to React + Laravel API.</p>
            {legacyPath && <p className="muted">Legacy reference: {legacyPath}</p>}
            <Link to="/" className="btn btn--primary mt-3">
              Back to home
            </Link>
          </div>
        </div>
      </div>
    </section>
  );
}
