import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-sell',
  imports: [FormsModule],
  template: `
    <div class="wrap page">
      <form class="card form" (ngSubmit)="submit()">
        <h1>Open a store</h1>
        <p class="muted">Register as a tenant. Super admin approval is required before publishing.</p>
        <div class="field"><label>Business name</label><input [(ngModel)]="name" name="name" required /></div>
        <div class="field"><label>First store name</label><input [(ngModel)]="storeName" name="storeName" /></div>
        <div class="field"><label>Country (ISO-2)</label><input [(ngModel)]="country" name="country" maxlength="2" /></div>
        @if (error()) { <p class="err">{{ error() }}</p> }
        @if (ok()) { <p>Application submitted. An admin will approve your tenant.</p> }
        <button class="btn" [disabled]="busy()">Apply</button>
      </form>
    </div>
  `,
  styles: [`
    .page { padding: 48px 0; display:flex; justify-content:center; }
    .form { width: min(480px, 100%); padding: 28px; }
  `],
})
export class SellComponent {
  private api = inject(ApiService);
  private router = inject(Router);
  name = '';
  storeName = '';
  country = 'GH';
  busy = signal(false);
  error = signal('');
  ok = signal(false);

  submit() {
    this.busy.set(true);
    this.api.registerTenant({ name: this.name, store_name: this.storeName, country: this.country }).subscribe({
      next: () => { this.ok.set(true); this.busy.set(false); },
      error: (e) => { this.error.set(e.error?.error?.message || 'Failed'); this.busy.set(false); },
    });
  }
}
