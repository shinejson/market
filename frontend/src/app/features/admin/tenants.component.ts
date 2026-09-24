import { Component, inject, signal } from '@angular/core';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-admin-tenants',
  template: `
    <h1>Tenants</h1>
    @for (t of tenants(); track t.id) {
      <div class="card pad row">
        <div>
          <strong>{{ t.name }}</strong>
          <p class="muted">{{ t.slug }} · {{ t.status }}</p>
        </div>
        <div>
          @if (t.status !== 'active') {
            <button class="btn ok" (click)="set(t.id, 'active')">Approve</button>
          }
          @if (t.status === 'active') {
            <button class="btn ghost" (click)="set(t.id, 'suspended')">Suspend</button>
          }
        </div>
      </div>
    }
  `,
  styles: [` .pad { padding: 14px; margin: 8px 0; } .row { display:flex; justify-content:space-between; align-items:center; } `],
})
export class AdminTenantsComponent {
  private api = inject(ApiService);
  tenants = signal<any[]>([]);
  constructor() { this.reload(); }
  reload() { this.api.adminTenants().subscribe((res) => this.tenants.set(res.data)); }
  set(id: number, status: string) {
    this.api.updateTenantStatus(id, status).subscribe(() => this.reload());
  }
}
