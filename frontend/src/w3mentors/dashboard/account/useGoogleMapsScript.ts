import { useEffect, useState } from 'react';

declare global {
  interface Window {
    google?: { maps?: unknown };
    initW3MentorsAddressMap?: () => void;
  }
}

let loadPromise: Promise<void> | null = null;

function loadGoogleMaps(apiKey: string): Promise<void> {
  if (window.google?.maps) {
    return Promise.resolve();
  }
  if (loadPromise) {
    return loadPromise;
  }
  loadPromise = new Promise((resolve, reject) => {
    const id = 'w3mentors-google-maps';
    if (document.getElementById(id)) {
      const check = () => {
        if (window.google?.maps) {
          resolve();
        } else {
          setTimeout(check, 50);
        }
      };
      check();
      return;
    }
    window.initW3MentorsAddressMap = () => resolve();
    const script = document.createElement('script');
    script.id = id;
    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places&callback=initW3MentorsAddressMap`;
    script.async = true;
    script.defer = true;
    script.onerror = () => reject(new Error('Failed to load Google Maps'));
    document.head.appendChild(script);
  });
  return loadPromise;
}

export function useGoogleMapsScript(apiKey: string | undefined) {
  const [ready, setReady] = useState(Boolean(window.google?.maps));

  useEffect(() => {
    if (!apiKey) {
      setReady(false);
      return;
    }
    let cancelled = false;
    loadGoogleMaps(apiKey)
      .then(() => {
        if (!cancelled) setReady(true);
      })
      .catch(() => {
        if (!cancelled) setReady(false);
      });
    return () => {
      cancelled = true;
    };
  }, [apiKey]);

  return ready;
}
