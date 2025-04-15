import type { route as routeFn } from 'ziggy-js';
import { Page } from '@inertiajs/core';

declare global {
    const route: typeof routeFn;
    interface Window {
        $page: Page;
      }
}
