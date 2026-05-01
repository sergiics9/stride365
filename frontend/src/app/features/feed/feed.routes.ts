import { Routes } from '@angular/router';

export const FEED_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./feed-placeholder.component').then((m) => m.FeedPlaceholderComponent),
  },
];
