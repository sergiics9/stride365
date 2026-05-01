import { HttpErrorResponse } from '@angular/common/http';

import { ApiError } from '../models';

export function toApiError(error: unknown): ApiError {
  if (error instanceof HttpErrorResponse) {
    const body = error.error as { message?: string; errors?: Record<string, string[]> } | null;
    return {
      status: error.status,
      message: body?.message ?? error.message ?? 'Error de comunicación con el servidor.',
      errors: body?.errors,
    };
  }

  return {
    status: 0,
    message: 'Error inesperado.',
  };
}

export function firstFieldError(error: ApiError | null, field: string): string | null {
  if (!error?.errors) return null;
  const list = error.errors[field];
  return list && list.length ? list[0] : null;
}
