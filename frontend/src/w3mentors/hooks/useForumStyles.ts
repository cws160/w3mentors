import { useEffect } from 'react';

/** Load legacy forum stylesheet (application/views/css/forum.css). */
export function useForumStyles(): void {
  useEffect(() => {
    const href = '/w3mentors/css/forum.css';
    let link = document.querySelector<HTMLLinkElement>(`link[data-w3mentors-forum="1"]`);
    if (!link) {
      link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = href;
      link.dataset.w3mentorsForum = '1';
      document.head.appendChild(link);
    }
    return () => {
      link?.remove();
    };
  }, []);
}
