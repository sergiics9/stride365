import { Injectable, signal } from '@angular/core';

const TOKEN_KEY = 'tfg_auth_token';

@Injectable({ providedIn: 'root' })
export class TokenStorageService {
  private readonly _token = signal<string | null>(this.read());

  readonly token = this._token.asReadonly();

  set(token: string): void {
    this._token.set(token);
    try {
      localStorage.setItem(TOKEN_KEY, token);
    } catch {
      /* storage no disponible (modo incógnito, etc.) */
    }
  }

  clear(): void {
    this._token.set(null);
    try {
      localStorage.removeItem(TOKEN_KEY);
    } catch {
      /* noop */
    }
  }

  private read(): string | null {
    try {
      return localStorage.getItem(TOKEN_KEY);
    } catch {
      return null;
    }
  }
}
