import { ChangeDetectionStrategy, Component, Directive, computed, inject, input } from '@angular/core';

let nextId = 0;

/**
 * Campo com rótulo, ajuda e erro. O controle dentro dele recebe a diretiva uiInput,
 * que liga id, aria-describedby e aria-invalid automaticamente:
 *
 *   <ui-form-field label="E-mail" [required]="true" [error]="erroEmail()">
 *     <input uiInput type="email" />
 *   </ui-form-field>
 */
@Component({
  selector: 'ui-form-field',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { class: 'ui-field' },
  template: `
    <label class="ui-field__label" [attr.for]="controlId">
      {{ label() }}
      @if (required()) {
        <span class="ui-field__required" aria-hidden="true">*</span>
      }
    </label>
    <ng-content />
    @if (hint()) {
      <p class="ui-field__hint" [id]="hintId">{{ hint() }}</p>
    }
    @if (error()) {
      <p class="ui-field__error" [id]="errorId">{{ error() }}</p>
    }
  `,
})
export class UiFormField {
  readonly label = input.required<string>();
  readonly hint = input<string | null>(null);
  readonly error = input<string | null>(null);
  readonly required = input(false);

  private readonly uid = `ui-field-${nextId++}`;
  readonly controlId = `${this.uid}-control`;
  readonly hintId = `${this.uid}-hint`;
  readonly errorId = `${this.uid}-error`;

  readonly describedBy = computed(() => {
    const ids = [this.hint() ? this.hintId : null, this.error() ? this.errorId : null].filter(Boolean);
    return ids.length ? ids.join(' ') : null;
  });
}

/** Controle de formulário (input, textarea, select) dentro de ui-form-field. */
@Directive({
  selector: 'input[uiInput], textarea[uiInput], select[uiInput]',
  host: {
    class: 'ui-input',
    '[attr.id]': 'field?.controlId ?? null',
    '[attr.aria-describedby]': 'field?.describedBy() ?? null',
    '[attr.aria-invalid]': 'field?.error() ? "true" : null',
    '[attr.aria-required]': 'field?.required() ? "true" : null',
  },
})
export class UiInput {
  protected readonly field = inject(UiFormField, { optional: true });
}
