import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../environments/environment';
import {
  AuthResponse,
  LoginRequest,
  MeResponse,
  RegisterRequest,
  RoleName,
  User,
} from '../../shared/models';
import { TokenStorageService } from './token-storage.service';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly tokenStorage = inject(TokenStorageService);

  private readonly _user = signal<User | null>(null);
  private readonly _roles = signal<RoleName[]>([]);
  private readonly _subscribed = signal<boolean>(false);
  private readonly _loading = signal<boolean>(false);

  readonly user = this._user.asReadonly();
  readonly roles = this._roles.asReadonly();
  readonly subscribed = this._subscribed.asReadonly();
  readonly loading = this._loading.asReadonly();

  readonly isAuthenticated = computed(() => this.tokenStorage.token() !== null);

  readonly isSuperAdmin = computed(() => this._roles().includes('super_admin'));
  readonly isAdminClub = computed(() => this._roles().includes('admin_club'));
  readonly isGuia = computed(() => this._roles().includes('guia'));
  readonly isSocio = computed(() => this._roles().includes('socio'));

  readonly fullName = computed(() => {
    const u = this._user();
    if (!u) return '';
    return `${u.nombre ?? ''} ${u.apellido ?? ''}`.trim() || u.email;
  });

  hasRole(role: RoleName): boolean {
    return this._roles().includes(role);
  }

  hasAnyRole(roles: RoleName[]): boolean {
    return roles.some((r) => this._roles().includes(r));
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
      const response = await firstValueFrom(
        this.http.post<AuthResponse>(`${environment.apiUrl}/auth/register`, payload),
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

  async me(): Promise<MeResponse | null> {
    if (!this.isAuthenticated()) {
      return null;
    }
    try {
      const response = await firstValueFrom(
        this.http.get<MeResponse>(`${environment.apiUrl}/auth/me`),
      );
      this._user.set(response.user);
      this._roles.set(response.roles);
      this._subscribed.set(response.subscribed);
      return response;
    } catch {
      this.clearSession();
      return null;
    }
  }

  setSubscribed(value: boolean): void {
    this._subscribed.set(value);
  }

  clearSession(): void {
    this.tokenStorage.clear();
    this._user.set(null);
    this._roles.set([]);
    this._subscribed.set(false);
  }

  private handleAuthResponse(response: AuthResponse): void {
    this.tokenStorage.set(response.token);
    this._user.set(response.user);
    const roles = (response.user.roles ?? []).map((r) => r.name);
    this._roles.set(roles);
  }
}
