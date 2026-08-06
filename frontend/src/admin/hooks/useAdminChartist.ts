const CHARTIST_SRC = '/manager/views/js/chartist.min.js';
const CHARTIST_TOOLTIP_SRC = '/manager/views/js/chartist-plugin-tooltip.min.js';

let chartistPromise: Promise<void> | null = null;

function loadScript(src: string): Promise<void> {
  return new Promise((resolve, reject) => {
    if (document.querySelector(`script[src="${src}"]`)) {
      resolve();
      return;
    }
    const script = document.createElement('script');
    script.src = src;
    script.async = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error(`Failed to load ${src}`));
    document.body.appendChild(script);
  });
}

function ensureChartist(): Promise<void> {
  if (chartistPromise) {
    return chartistPromise;
  }
  chartistPromise = loadScript(CHARTIST_SRC).then(() => loadScript(CHARTIST_TOOLTIP_SRC));
  return chartistPromise;
}

type ChartistBar = new (
  selector: string,
  data: { labels: string[]; series: number[][] },
  options: Record<string, unknown>
) => { on: (event: string, cb: (data: { type: string; element: { attr: (attrs: Record<string, string>) => void } }) => void) => void };

type ChartistGlobal = {
  Bar: ChartistBar;
  plugins: { tooltip: () => unknown };
};

declare global {
  interface Window {
    Chartist?: ChartistGlobal;
  }
}

export async function renderAdminBarChart(
  elementId: string,
  seriesData: Record<string, number>
): Promise<void> {
  await ensureChartist();
  const Chartist = window.Chartist;
  if (!Chartist) {
    return;
  }

  const labels = Object.keys(seriesData);
  const values = Object.values(seriesData).map((v) => Number(v) || 0);
  const el = document.getElementById(elementId);
  if (!el) {
    return;
  }
  el.innerHTML = '';

  const chart = new Chartist.Bar(
    `#${elementId}`,
    { labels, series: [values] },
    {
      stackBars: true,
      axisY: {
        position: 'start',
        labelInterpolationFnc: (value: number) => String(value),
      },
      plugins: Chartist.plugins?.tooltip ? [Chartist.plugins.tooltip()] : [],
    }
  );

  chart.on('draw', (data) => {
    if (data.type === 'bar') {
      data.element.attr({ style: 'stroke-width: 25px' });
    }
  });
}
