import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnDestroy, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';

import { ToastService } from '../../../core/toast/toast.service';
import { toApiError } from '../../../shared/utils/api-error.util';
import { FeedService } from '../feed.service';

@Component({
  selector: 'app-feed-record-live',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './feed-record-live.component.html',
  styleUrl: './feed-record-live.component.scss',
})
export class FeedRecordLiveComponent implements OnDestroy {
  private readonly feed = inject(FeedService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);

  protected tituloInicio = '';
  protected readonly phase = signal<'idle' | 'recording' | 'saving'>('idle');
  protected readonly error = signal<string | null>(null);
  protected readonly totalPoints = signal(0);

  private recordingId: number | null = null;
  private watchId: number | null = null;
  private flushTimer: ReturnType<typeof setInterval> | null = null;
  private buffer: number[][] = [];

  ngOnDestroy(): void {
    this.clearGeo();
    if (this.flushTimer !== null) {
      clearInterval(this.flushTimer);
      this.flushTimer = null;
    }
  }

  protected async empezar(): Promise<void> {
    this.error.set(null);
    try {
      const a = await this.feed.startRecording({
        titulo: this.tituloInicio.trim() || undefined,
      });
      this.recordingId = a.id;
      this.phase.set('recording');
      this.buffer = [];
      this.flushTimer = setInterval(() => void this.flush(), 4000);
      this.watchId = navigator.geolocation.watchPosition(
        (pos) => {
          const lng = pos.coords.longitude;
          const lat = pos.coords.latitude;
          const ts = Math.floor(Date.now() / 1000);
          const alt = pos.coords.altitude;
          if (alt != null && Number.isFinite(alt)) {
            this.buffer.push([lng, lat, alt, ts]);
          } else {
            this.buffer.push([lng, lat, ts]);
          }
        },
        (err) => this.error.set(err.message || 'No se pudo acceder al GPS.'),
        { enableHighAccuracy: true, maximumAge: 2000, timeout: 15000 },
      );
    } catch (e) {
      this.toast.error(toApiError(e).message);
    }
  }

  private async flush(): Promise<void> {
    if (this.recordingId === null || this.buffer.length === 0) return;
    const batch = [...this.buffer];
    this.buffer = [];
    try {
      const a = await this.feed.appendRecordingCoords(this.recordingId, batch);
      const n = (a.track_geojson as { coordinates?: [number, number][] } | null)?.coordinates
        ?.length;
      if (typeof n === 'number') this.totalPoints.set(n);
    } catch {
      this.buffer.unshift(...batch);
    }
  }

  protected async detenerYPublicar(): Promise<void> {
    if (this.recordingId === null) return;
    this.clearGeo();
    if (this.flushTimer !== null) {
      clearInterval(this.flushTimer);
      this.flushTimer = null;
    }
    await this.flush();
    this.phase.set('saving');
    try {
      const titulo = prompt('Título de la actividad (opcional):')?.trim();
      const desc = prompt('Descripción (opcional):')?.trim();
      await this.feed.finishRecording(this.recordingId, {
        titulo: titulo || undefined,
        descripcion: desc || undefined,
      });
      this.toast.success('Actividad publicada en el feed.');
      await this.router.navigate(['/feed']);
    } catch (e) {
      this.phase.set('recording');
      this.toast.error(toApiError(e).message);
    }
  }

  protected cancelar(): void {
    this.clearGeo();
    if (this.flushTimer !== null) {
      clearInterval(this.flushTimer);
      this.flushTimer = null;
    }
    void this.router.navigate(['/feed']);
  }

  private clearGeo(): void {
    if (this.watchId !== null) {
      navigator.geolocation.clearWatch(this.watchId);
      this.watchId = null;
    }
  }
}
