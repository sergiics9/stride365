export function formatDurationHms(totalSeconds: number | null | undefined): string {
  if (totalSeconds == null || totalSeconds <= 0) {
    return '—';
  }
  const h = Math.floor(totalSeconds / 3600);
  const m = Math.floor((totalSeconds % 3600) / 60);
  const s = totalSeconds % 60;
  if (h > 0) {
    return `${h} h ${m} min`;
  }
  if (m > 0) {
    return `${m} min ${s} s`;
  }
  return `${s} s`;
}

export function formatPaceMinPerKm(secPerKm: number | null | undefined): string {
  if (secPerKm == null || secPerKm <= 0) {
    return '—';
  }
  const m = Math.floor(secPerKm / 60);
  const s = Math.round(secPerKm % 60);
  return `${m}:${s.toString().padStart(2, '0')} /km`;
}
