const ACTION_SPRITE_ID = 'admin-actions-sprite';
const ACTION_SPRITE_URL = '/manager/views/images/sprite-actions.svg';
const ASIDE_SPRITE_ID = 'admin-aside-sprite';
const ASIDE_SPRITE_URL = '/manager/views/images/retina/sprite-aside-menu.svg';

let actionSpritePromise: Promise<void> | null = null;
let asideSpritePromise: Promise<void> | null = null;

function injectSprite(id: string, svg: string): void {
  if (document.getElementById(id)) {
    return;
  }
  const host = document.createElement('div');
  host.id = id;
  host.setAttribute('aria-hidden', 'true');
  host.style.cssText = 'position:absolute;width:0;height:0;overflow:hidden;pointer-events:none';
  host.innerHTML = svg;
  document.body.appendChild(host);
}

async function loadSprite(url: string, id: string): Promise<void> {
  if (typeof document === 'undefined' || document.getElementById(id)) {
    return;
  }
  const res = await fetch(url);
  if (!res.ok) {
    throw new Error(`Failed to load ${url}`);
  }
  injectSprite(id, await res.text());
}

/** Inline legacy sprite-actions.svg so `<use href="#icon">` works in React. */
export function ensureAdminActionSprite(): Promise<void> {
  if (typeof document === 'undefined' || document.getElementById(ACTION_SPRITE_ID)) {
    return Promise.resolve();
  }
  if (!actionSpritePromise) {
    actionSpritePromise = loadSprite(ACTION_SPRITE_URL, ACTION_SPRITE_ID).catch(() => {
      actionSpritePromise = null;
    });
  }
  return actionSpritePromise;
}

export function ensureAdminAsideSprite(): Promise<void> {
  if (typeof document === 'undefined' || document.getElementById(ASIDE_SPRITE_ID)) {
    return Promise.resolve();
  }
  if (!asideSpritePromise) {
    asideSpritePromise = loadSprite(ASIDE_SPRITE_URL, ASIDE_SPRITE_ID).catch(() => {
      asideSpritePromise = null;
    });
  }
  return asideSpritePromise;
}

export function ensureAdminSprites(): Promise<void> {
  return Promise.all([ensureAdminActionSprite(), ensureAdminAsideSprite()]).then(() => undefined);
}
