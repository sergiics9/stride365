import { DatePipe } from '@angular/common';
import {
  ChangeDetectionStrategy,
  Component,
  computed,
  inject,
  OnDestroy,
  signal,
} from '@angular/core';
import { RouterLink } from '@angular/router';

import { AuthService } from '../../core/auth/auth.service';
import { ToastService } from '../../core/toast/toast.service';
import { toApiError } from '../../shared/utils/api-error.util';

const MAX_FOTO_BYTES = 2 * 1024 * 1024;
const ACCEPTED_FOTO_MIME = new Set([
  'image/jpeg',
  'image/jpg',
  'image/png',
  'image/webp',
  'image/gif',
]);

@Component({
  selector: 'app-profile',
  imports: [DatePipe, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './profile.component.html',
  styleUrl: './profile.component.scss',
})
export class ProfileComponent implements OnDestroy {
  protected readonly auth = inject(AuthService);
  private readonly toast = inject(ToastService);

  protected readonly user = this.auth.user;
  protected readonly roles = this.auth.roles;

  protected readonly initials = computed(() => {
    const u = this.user();
    if (!u) return '?';
    const a = (u.nombre ?? '').charAt(0);
    const b = (u.apellido ?? '').charAt(0);
    return (a + b).toUpperCase() || u.email.charAt(0).toUpperCase();
  });

  protected readonly memberships = this.auth.memberships;

  protected readonly uploadingFoto = signal(false);
  protected readonly fotoClientError = signal<string | null>(null);
  protected readonly fotoPreviewUrl = signal<string | null>(null);
  protected readonly pendingFoto = signal<File | null>(null);

  private fotoObjectUrl: string | null = null;

  protected readonly roleLabels: Partial<Record<string, string>> = {
    super_admin: 'Super administrador',
    usuario: 'Usuario',
  };

  ngOnDestroy(): void {
    this.revokeFotoPreview();
  }

  private revokeFotoPreview(): void {
    if (this.fotoObjectUrl) {
      URL.revokeObjectURL(this.fotoObjectUrl);
      this.fotoObjectUrl = null;
    }
    this.fotoPreviewUrl.set(null);
    this.pendingFoto.set(null);
  }

  protected reload(): void {
    void this.auth.me();
  }

  protected onFotoSelected(event: Event): void {
    this.fotoClientError.set(null);
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    this.revokeFotoPreview();

    if (!file) {
      return;
    }

    if (!ACCEPTED_FOTO_MIME.has(file.type)) {
      this.fotoClientError.set('Formato no válido. Usa JPEG, PNG, WebP o GIF.');
      input.value = '';
      return;
    }

    if (file.size > MAX_FOTO_BYTES) {
      this.fotoClientError.set('La imagen no puede superar 2 MB.');
      input.value = '';
      return;
    }

    this.pendingFoto.set(file);
    this.fotoObjectUrl = URL.createObjectURL(file);
    this.fotoPreviewUrl.set(this.fotoObjectUrl);
  }

  protected clearFotoSelection(fotoInput: HTMLInputElement): void {
    fotoInput.value = '';
    this.fotoClientError.set(null);
    this.revokeFotoPreview();
  }

  protected async saveFoto(fotoInput: HTMLInputElement): Promise<void> {
    const file = this.pendingFoto();
    if (!file) {
      this.toast.warning('Selecciona una imagen primero.');
      return;
    }

    this.uploadingFoto.set(true);
    this.fotoClientError.set(null);

    try {
      await this.auth.updateProfile({ foto: file });
      this.toast.success('Foto de perfil actualizada.');
      this.clearFotoSelection(fotoInput);
    } catch (error) {
      const err = toApiError(error);
      this.toast.error(err.message);
    } finally {
      this.uploadingFoto.set(false);
    }
  }
}
