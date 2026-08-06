import { useEffect, useLayoutEffect } from 'react';
import AOS from 'aos';
import 'aos/dist/aos.css';
import { destroyW3MentorsSliders, initW3MentorsSliders } from '../lib/w3mentors-sliders';
import { bindW3MentorsUiHandlers } from '../lib/w3mentors-ui';

function runAfterPaint(fn: () => void): () => void {
  let id = 0;
  const raf = requestAnimationFrame(() => {
    id = window.setTimeout(fn, 50);
  });
  return () => {
    cancelAnimationFrame(raf);
    window.clearTimeout(id);
  };
}

export function useW3MentorsSliders(deps: unknown[] = []) {
  useLayoutEffect(() => {
    bindW3MentorsUiHandlers();
    return runAfterPaint(() => {
      destroyW3MentorsSliders();
      initW3MentorsSliders();
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, deps);
}

export function useW3MentorsAos(deps: unknown[] = []) {
  useEffect(() => {
    AOS.init({
      duration: 800,
      once: true,
      offset: 40,
    });
    return runAfterPaint(() => {
      AOS.refresh();
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, deps);
}

export function useStatsCounters(deps: unknown[] = []) {
  useEffect(() => {
    const timers: number[] = [];

    const run = () => {
      document.querySelectorAll<HTMLElement>('.stats__number span[data-target]').forEach((counter) => {
        const target = Number(counter.getAttribute('data-target') ?? 0);
        if (!target) return;
        let current = 0;
        const step = Math.max(target / 20, 1);
        const id = window.setInterval(() => {
          current += step;
          if (current >= target) {
            counter.textContent = String(target);
            window.clearInterval(id);
          } else {
            counter.textContent = String(Math.ceil(current));
          }
        }, 100);
        timers.push(id);
      });
    };

    const delayId = window.setTimeout(run, 100);
    return () => {
      window.clearTimeout(delayId);
      timers.forEach((id) => window.clearInterval(id));
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, deps);
}
