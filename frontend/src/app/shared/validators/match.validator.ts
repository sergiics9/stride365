import { AbstractControl, ValidationErrors, ValidatorFn } from '@angular/forms';

export function matchValidator(controlName: string, matchingControlName: string): ValidatorFn {
  return (group: AbstractControl): ValidationErrors | null => {
    const control = group.get(controlName);
    const matching = group.get(matchingControlName);
    if (!control || !matching) return null;

    if (matching.errors && !matching.errors['mismatch']) {
      return null;
    }

    if (control.value !== matching.value) {
      matching.setErrors({ ...matching.errors, mismatch: true });
      return { mismatch: true };
    }

    if (matching.errors) {
      const { mismatch, ...rest } = matching.errors;
      matching.setErrors(Object.keys(rest).length ? rest : null);
    }
    return null;
  };
}
