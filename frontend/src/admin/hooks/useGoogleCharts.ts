const LOADER_SRC = 'https://www.gstatic.com/charts/loader.js';

let loaderPromise: Promise<void> | null = null;

function ensureGoogleCharts(): Promise<void> {
  if (loaderPromise) {
    return loaderPromise;
  }
  loaderPromise = new Promise((resolve, reject) => {
    if (window.google?.charts) {
      window.google.charts.load('current', { packages: ['corechart'] });
      window.google.charts.setOnLoadCallback(() => resolve());
      return;
    }
    const script = document.createElement('script');
    script.src = LOADER_SRC;
    script.async = true;
    script.onload = () => {
      window.google.charts.load('current', { packages: ['corechart'] });
      window.google.charts.setOnLoadCallback(() => resolve());
    };
    script.onerror = () => reject(new Error('Failed to load Google Charts'));
    document.body.appendChild(script);
  });
  return loaderPromise;
}

export async function renderAnalyticsPieChart(
  elementId: string,
  data: Record<string, number>
): Promise<void> {
  const el = document.getElementById(elementId);
  if (!el) {
    return;
  }

  const entries = Object.entries(data).filter(([, value]) => Number(value) > 0);
  if (entries.length === 0) {
    el.innerHTML = '';
    return;
  }

  await ensureGoogleCharts();
  const table: (string | number)[][] = [['Task', 'Value']];
  for (const [key, value] of entries) {
    table.push([key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()), Number(value)]);
  }

  const chartData = window.google.visualization.arrayToDataTable(table);
  const chart = new window.google.visualization.PieChart(el);
  chart.draw(chartData, {
    title: '',
    width: el.clientWidth || 480,
    height: 360,
    pieHole: 0.4,
    pieStartAngle: 100,
    legend: { position: 'bottom', textStyle: { fontSize: 12, alignment: 'center' } },
  });
}

declare global {
  interface Window {
    google: {
      charts: {
        load: (version: string, options: { packages: string[] }) => void;
        setOnLoadCallback: (cb: () => void) => void;
      };
      visualization: {
        arrayToDataTable: (rows: (string | number)[][]) => unknown;
        PieChart: new (el: HTMLElement) => { draw: (data: unknown, options: Record<string, unknown>) => void };
      };
    };
  }
}
