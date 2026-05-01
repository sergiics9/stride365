import { Pipe, PipeTransform } from '@angular/core';

const MINUTE = 60_000;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;
const WEEK = 7 * DAY;
const MONTH = 30 * DAY;
const YEAR = 365 * DAY;

@Pipe({ name: 'relativeDate' })
export class RelativeDatePipe implements PipeTransform {
  transform(value: string | Date | number | null | undefined): string {
    if (value === null || value === undefined || value === '') return '';

    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) return '';

    const diff = Date.now() - date.getTime();

    if (diff < MINUTE) return 'hace unos segundos';
    if (diff < HOUR) {
      const m = Math.floor(diff / MINUTE);
      return `hace ${m} ${m === 1 ? 'minuto' : 'minutos'}`;
    }
    if (diff < DAY) {
      const h = Math.floor(diff / HOUR);
      return `hace ${h} ${h === 1 ? 'hora' : 'horas'}`;
    }
    if (diff < 2 * DAY) return 'ayer';
    if (diff < WEEK) {
      const d = Math.floor(diff / DAY);
      return `hace ${d} días`;
    }
    if (diff < MONTH) {
      const w = Math.floor(diff / WEEK);
      return `hace ${w} ${w === 1 ? 'semana' : 'semanas'}`;
    }
    if (diff < YEAR) {
      const mo = Math.floor(diff / MONTH);
      return `hace ${mo} ${mo === 1 ? 'mes' : 'meses'}`;
    }
    const y = Math.floor(diff / YEAR);
    return `hace ${y} ${y === 1 ? 'año' : 'años'}`;
  }
}
