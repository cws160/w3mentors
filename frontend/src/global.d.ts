import type { JQuery } from 'jquery';

declare module 'jquery' {
  interface JQuery {
    slick(options?: unknown): JQuery;
  }
}

export {};
