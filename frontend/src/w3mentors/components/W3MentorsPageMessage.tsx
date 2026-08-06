type Props = {
  message: string;
  error?: boolean;
};

export function W3MentorsPageMessage({ message, error }: Props) {
  return (
    <section className="section">
      <div className="container container--xl text-center">
        <p className={error ? 'color-red' : undefined}>{message}</p>
      </div>
    </section>
  );
}
