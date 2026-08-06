const BASE = '/manager/views/innovas/scripts/';
const EDITOR_STYLE = '/manager/views/innovas/styles/simple.css';

const STYLES = [
  `${BASE}common/nlslightbox/nlslightbox.css`,
  `${BASE}style/istoolbar.css`,
  EDITOR_STYLE,
];

function editorScriptPath(): string {
  const ua = navigator.userAgent.toLowerCase();
  if (ua.includes('msie')) {
    return `${BASE}editor.js`;
  }
  if (ua.includes('safari') && !ua.includes('chrome')) {
    return `${BASE}saf/editor.js`;
  }
  return `${BASE}moz/editor.js`;
}

const SCRIPTS = [
  `${BASE}common/nlslightbox/nlslightbox.js`,
  `${BASE}common/nlslightbox/nlsanimation.js`,
  `${BASE}common/nlslightbox/dialog.js`,
  `${BASE}istoolbar.js`,
  `${BASE}language/en-US/editor_lang.js`,
  editorScriptPath(),
  `${BASE}common/webfont.js`,
];

type InnovaEditorInstance = {
  idTextArea: string;
  css: string;
  width: string | number;
  height: string | number;
  REPLACE: (textareaId: string) => void;
  putHTML: (html: string) => void;
  getXHTMLBody: () => string;
};

type InnovaWindow = Window & {
  InnovaEditor?: new (name: string) => InnovaEditorInstance;
  oUtil?: {
    arrEditor: string[];
    obj: InnovaEditorInstance;
  };
};

function innovaWindow(): InnovaWindow {
  return window as InnovaWindow;
}

function isInnovEditorReady(): boolean {
  return typeof innovaWindow().InnovaEditor === 'function';
}

let safeDocumentWriteInstalled = false;

function injectDocumentWriteMarkup(markup: string): void {
  const normalized = markup.replace(/language\/en-cy\//g, 'language/en-US/');
  const container = document.createElement('div');
  container.innerHTML = normalized;
  container.querySelectorAll('script').forEach((node) => {
    const script = node as HTMLScriptElement;
    if (!script.src) {
      return;
    }
    if (document.querySelector(`script[src="${script.src}"]`)) {
      return;
    }
    const next = document.createElement('script');
    next.src = script.src;
    next.async = false;
    document.head.appendChild(next);
  });
  container.querySelectorAll('link').forEach((node) => {
    const link = node as HTMLLinkElement;
    if (!link.href || document.querySelector(`link[href="${link.href}"]`)) {
      return;
    }
    const next = document.createElement('link');
    next.rel = link.rel || 'stylesheet';
    next.href = link.href;
    document.head.appendChild(next);
  });
}

function installSafeDocumentWrite(): void {
  if (safeDocumentWriteInstalled) {
    return;
  }
  document.write = (markup: string) => {
    injectDocumentWriteMarkup(markup);
  };
  safeDocumentWriteInstalled = true;
}

function loadStyle(href: string): void {
  if (document.querySelector(`link[href="${href}"]`)) {
    return;
  }
  const link = document.createElement('link');
  link.rel = 'stylesheet';
  link.href = href;
  document.head.appendChild(link);
}

function loadScript(src: string): Promise<void> {
  return new Promise((resolve, reject) => {
    const existing = document.querySelector(`script[src="${src}"]`) as HTMLScriptElement | null;
    if (existing) {
      if (existing.dataset.loaded === 'true') {
        resolve();
        return;
      }
      existing.addEventListener('load', () => resolve(), { once: true });
      existing.addEventListener('error', () => reject(new Error(`Failed to load ${src}`)), { once: true });
      return;
    }
    const script = document.createElement('script');
    script.src = src;
    script.async = false;
    script.dataset.loaded = 'false';
    script.onload = () => {
      script.dataset.loaded = 'true';
      resolve();
    };
    script.onerror = () => reject(new Error(`Failed to load ${src}`));
    document.head.appendChild(script);
  });
}

let loadPromise: Promise<void> | null = null;

function waitForInnovEditor(timeoutMs = 10000): Promise<void> {
  return new Promise((resolve, reject) => {
    const started = Date.now();
    const tick = () => {
      if (isInnovEditorReady()) {
        resolve();
        return;
      }
      if (Date.now() - started >= timeoutMs) {
        reject(new Error('InnovaEditor did not become available'));
        return;
      }
      window.setTimeout(tick, 50);
    };
    tick();
  });
}

export function ensureAdminInnovEditor(): Promise<void> {
  if (isInnovEditorReady()) {
    return Promise.resolve();
  }
  if (loadPromise) {
    return loadPromise;
  }
  loadPromise = (async () => {
    installSafeDocumentWrite();
    STYLES.forEach(loadStyle);
    for (const src of SCRIPTS) {
      await loadScript(src);
    }
    await waitForInnovEditor();
  })().catch((error) => {
    loadPromise = null;
    throw error;
  });
  return loadPromise;
}

export function purgeOrphanInnovEditorNodes(): void {
  document.querySelectorAll('body > table').forEach((table) => {
    if (
      table.querySelector('.istoolbar_container, [id^="idContentoEdit_"], [id^="idEditoroEdit_"]') &&
      !table.closest('.admin-innov-editor-host, .modal')
    ) {
      table.remove();
    }
  });
}

export function resetAdminInnovEditors(host?: HTMLElement | null): void {
  const w = innovaWindow();
  if (typeof w.oUtil !== 'undefined' && w.oUtil.arrEditor) {
    const editors = [...w.oUtil.arrEditor];
    for (const name of editors) {
      delete (w as Record<string, unknown>)[name];
    }
    w.oUtil.arrEditor = [];
  }
  if (host) {
    host.innerHTML = '';
  }
  purgeOrphanInnovEditorNodes();
}

function getEditorInstance(editorId: string): InnovaEditorInstance | null {
  const w = innovaWindow();
  const instanceName = editorInstanceName(editorId);
  const editor = (w as Record<string, InnovaEditorInstance | undefined>)[instanceName];
  if (editor?.getXHTMLBody) {
    return editor;
  }
  if (w.oUtil?.obj?.idTextArea === editorId) {
    return w.oUtil.obj;
  }
  return null;
}

export function editorInstanceName(editorId: string): string {
  return `oEdit_${editorId}`;
}

export function getInnovEditorHtml(editorId: string): string {
  const editor = getEditorInstance(editorId);
  if (editor?.getXHTMLBody) {
    return editor.getXHTMLBody();
  }
  const textarea = document.getElementById(editorId) as HTMLTextAreaElement | null;
  return textarea?.value ?? '';
}

export function setInnovEditorHtml(editorId: string, html: string): void {
  const editor = getEditorInstance(editorId);
  if (editor?.putHTML) {
    editor.putHTML(html);
    return;
  }
  const textarea = document.getElementById(editorId) as HTMLTextAreaElement | null;
  if (textarea) {
    textarea.value = html;
  }
}

function setInnovEditorDirection(editorId: string, direction: string): void {
  const instanceName = editorInstanceName(editorId);
  const frame = document.getElementById(`idContent${instanceName}`) as HTMLIFrameElement | null;
  const body = frame?.contentDocument?.body;
  if (body) {
    body.style.direction = direction;
  }
  document
    .querySelectorAll(`[id^="idEditor${instanceName}"] .istoolbar_container, [id^="idEditor${instanceName}"]`)
    .forEach((node) => {
      node.setAttribute('dir', direction);
    });
}

function moveEditorIntoHost(host: HTMLElement, instanceName: string): void {
  const editorRoot =
    document.getElementById(`idEditor${instanceName}`)?.closest('table') ??
    document.getElementById(`idContent${instanceName}`)?.closest('table');
  if (editorRoot && !host.contains(editorRoot)) {
    host.appendChild(editorRoot);
  }
}

function assertEditorRendered(editorId: string): void {
  const instanceName = editorInstanceName(editorId);
  const hasToolbar = Boolean(document.getElementById(`idEditor${instanceName}`));
  const hasFrame = Boolean(document.getElementById(`idContent${instanceName}`));
  if (!hasToolbar && !hasFrame) {
    throw new Error(`Innova editor did not render for ${editorId}`);
  }
}

export function initInnovEditorInHost(
  host: HTMLElement,
  editorId: string,
  html: string,
  layoutDirection: string,
): void {
  const w = innovaWindow();
  if (!w.InnovaEditor) {
    throw new Error('InnovaEditor is not available');
  }

  resetAdminInnovEditors(host);

  const textarea = document.createElement('textarea');
  textarea.id = editorId;
  textarea.name = editorId;
  textarea.value = html;
  textarea.style.width = '100%';
  host.appendChild(textarea);

  const instanceName = editorInstanceName(editorId);
  const editor = new w.InnovaEditor(instanceName);
  (w as Record<string, InnovaEditorInstance>)[instanceName] = editor;
  editor.css = EDITOR_STYLE;
  editor.width = '100%';
  editor.height = 320;
  editor.REPLACE(editorId);

  moveEditorIntoHost(host, instanceName);
  assertEditorRendered(editorId);

  window.setTimeout(() => {
    moveEditorIntoHost(host, instanceName);
    if (html) {
      editor.putHTML(html);
    }
    setInnovEditorDirection(editorId, layoutDirection);
  }, 120);
}

export function renderInnovEditorFallback(host: HTMLElement, editorId: string, html: string): void {
  host.innerHTML = `<textarea id="${editorId}" name="${editorId}" class="form-control" rows="12" style="width:100%;min-height:320px"></textarea>`;
  const textarea = host.querySelector('textarea');
  if (textarea) {
    textarea.value = html;
  }
}
