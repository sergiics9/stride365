import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../environments/environment';
import {
  AuthResponse,
  LoginRequest,
  MeResponse,
  Membership,
  RegisterRequest,
  RoleName,
  UpdateProfilePayload,
  User,
} from '../../shared/models';
import { TokenStorageService } from './token-storage.service';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly tokenStorage = inject(TokenStorageService);

  private readonly _user = signal<User | null>(null);
  private readonly _roles = signal<RoleName[]>([]);
  private readonly _memberships = signal<Membership[]>([]);
  private readonly _loading = signal<boolean>(false);

  readonly user = this._user.asReadonly();
  readonly roles = this._roles.asReadonly();
  readonly memberships = this._memberships.asReadonly();
  readonly loading = this._loading.asReadonly();

  readonly isAuthenticated = computed(() => this.tokenStorage.token() !== null);

  readonly isSuperAdmin = computed(() => this._roles().includes('super_admin'));

  readonly activeMemberships = computed(() =>
    this._memberships().filter((m) => m.status === 'active' || m.status === 'grace'),
  );

  readonly adminMembership = computed<Membership | null>(
    () => this._memberships().find((m) => m.role === 'admin_club') ?? null,
  );

  readonly socioMemberships = computed<Membership[]>(() =>
    this._memberships().filter((m) => m.role === 'socio'),
  );

  readonly hasAnyMembership = computed(() => this.activeMemberships().length > 0);

  /**
   * Listado de clubes, ficha pública y solicitud de club están disponibles
   * para cualquier usuario autenticado. Las rutas internas (socios, actividades…)
   * siguen protegidas por membresía en el router y en la API.
   */
  readonly canAccessClubes = computed(() => this.isAuthenticated());

  readonly fullName = computed(() => {
    const u = this._user();
    if (!u) return '';
    return `${u.nombre ?? ''} ${u.apellido ?? ''}`.trim() || u.email;
  });

  hasRole(role: RoleName): boolean {
    return this._roles().includes(role);
  }

  isAdminOf(clubId: number): boolean {
    return this._memberships().some(
      (m) =>
        m.club_id === clubId &&
        m.role === 'admin_club' &&
        (m.status === 'active' || m.status === 'grace'),
    );
  }

  isSocioOf(clubId: number): boolean {
    return this._memberships().some(
      (m) =>
        m.club_id === clubId &&
        (m.role === 'socio' || m.role === 'admin_club') &&
        (m.status === 'active' || m.status === 'grace'),
    );
  }

  isGuideOf(clubId: number): boolean {
    if (this.isAdminOf(clubId)) {
      return true;
    }
    return this._memberships().some(
      (m) =>
        m.club_id === clubId &&
        m.role === 'socio' &&
        m.is_guide &&
        (m.status === 'active' || m.status === 'grace'),
    );
  }

  membershipFor(kind: 'club' | 'socio', clubId: number): Membership | null {
    const role = kind === 'club' ? 'admin_club' : 'socio';
    return (
      this._memberships().find((m) => m.role === role && m.club_id === clubId) ?? null
    );
  }

  async login(payload: LoginRequest): Promise<AuthResponse> {
    this._loading.set(true);
    try {
      const response = await firstValueFrom(
        this.http.post<AuthResponse>(`${environment.apiUrl}/auth/login`, payload),
      );
      this.handleAuthResponse(response);
      await this.me();
      return response;
    } finally {
      this._loading.set(false);
    }
  }

  async register(payload: RegisterRequest): Promise<AuthResponse> {
    this._loading.set(true);
    try {
      const fd = new FormData();
      fd.append('nombre', payload.nombre);
      fd.append('apellido', payload.apellido);
      fd.append('email', payload.email);
      fd.append('password', payload.password);
      fd.append('password_confirmation', payload.password_confirmation);
      if (payload.telefono != null && payload.telefono !== '') {
        fd.append('telefono', payload.telefono);
      }
      if (payload.sexo) {
        fd.append('sexo', payload.sexo);
      }
      if (payload.fecha_nacimiento) {
        fd.append('fecha_nacimiento', payload.fecha_nacimiento);
      }
      if (payload.direccion != null && payload.direccion !== '') {
        fd.append('direccion', payload.direccion);
      }
      if (payload.device_name) {
        fd.append('device_name', payload.device_name);
      }
      if (payload.foto) {
        fd.append('foto', payload.foto, payload.foto.name);
      }

      const response = await firstValueFrom(
        this.http.post<AuthResponse>(`${environment.apiUrl}/auth/register`, fd),
      );
      this.handleAuthResponse(response);
      await this.me();
      return response;
    } finally {
      this._loading.set(false);
    }
  }

  async logout(): Promise<void> {
    try {
      await firstValueFrom(
        this.http.post(`${environment.apiUrl}/auth/logout`, {}),
      );
    } catch {
      /* aunque falle el backend, limpiamos sesión local */
    } finally {
      this.clearSession();
    }
  }

  private _meInFlight: Promise<MeResponse | null> | null = null;

  async me(): Promise<MeResponse | null> {
    if (!this.isAuthenticated()) {
      return null;
    }
    if (this._meInFlight) {
      return this._meInFlight;
    }
    this._meInFlight = (async () => {
      try {
        const response = await firstValueFrom(
          this.http.get<MeResponse>(`${environment.apiUrl}/auth/me`),
        );
        this._user.set(response.user);
        this._roles.set(response.roles);
        this._memberships.set(response.memberships ?? []);
        return response;
      } catch {
        this.clearSession();
        return null;
      } finally {
        this._meInFlight = null;
      }
    })();
    return this._meInFlight;
  }

  async updateProfile(payload: UpdateProfilePayload): Promise<MeResponse> {
    const fd = new FormData();
    if (payload.foto) {
      fd.append('foto', payload.foto, payload.foto.name);
    }
    const response = await firstValueFrom(
      this.http.patch<MeResponse>(`${environment.apiUrl}/auth/me`, fd),
    );
    this._user.set(response.user);
    this._roles.set(response.roles);
    this._memberships.set(response.memberships ?? []);
    return response;
  }

  setMemberships(memberships: Membership[]): void {
    this._memberships.set(memberships);
  }

  upsertMembership(membership: Membership): void {
    this._memberships.update((list) => {
      const idx = list.findIndex(
        (m) => m.role === membership.role && m.club_id === membership.club_id,
      );
      if (idx >= 0) {
        const next = [...list];
        next[idx] = membership;
        return next;
      }
      return [...list, membership];
    });
  }

  clearSession(): void {
    this.tokenStorage.clear();
    this._user.set(null);
    this._roles.set([]);
    this._memberships.set([]);
  }

  private handleAuthResponse(response: AuthResponse): void {
    this.tokenStorage.set(response.token);
    this._user.set(response.user);
    const roles = (response.user.roles ?? []).map((r) => r.name);
    this._roles.set(roles);
  }
}
