import { Routes } from '@angular/router';

import { feedDetailResolver } from './feed-detail.resolver';

export const FEED_ROUTES: Routes = [
  {
    path: 'grabar',
    loadComponent: () =>
      import('./feed-record-live/feed-record-live.component').then(
        (m) => m.FeedRecordLiveComponent,
      ),
  },
  {
    path: '',
    loadComponent: () => import('./feed-list/feed-list.component').then((m) => m.FeedListComponent),
  },
  {
    path: ':id',
    resolve: { publicacion: feedDetailResolver },
    loadComponent: () =>
      import('./feed-detail/feed-detail.component').then((m) => m.FeedDetailComponent),
  },
];
