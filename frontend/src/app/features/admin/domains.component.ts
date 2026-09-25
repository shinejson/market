import { Component, inject, signal } from '@angular/core';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-admin-domains',
  template: `
    <h1>Domains</h1>
    @for (d of domains(); track d.id) {
      <div class="card pad row">
        <div>
          <strong>{{ d.domain }}</strong>
          <p class="muted">tenant {{ d.tenant_id }} · {{ d.status }}</p>
        </div>
        <button class="btn ghost" (click)="verify(d.id)">Force verify</button>
      </div>
    }
  `,
  styles: [`
    .pad { padding: 14px; margin: 10px 0; }
    .row { display:flex; justify-content:space-between; align-items:center; }
  `],
})
export class AdminDomainsComponent {
  private api = inject(ApiService);
  domains = signal<any[]>([]);
  constructor() { this.reload(); }
  reload() {
    this.api.adminDomains().subscribe((res) => this.domains.set(res.data));
  }
  verify(id: number) {
    this.api.adminVerifyDomain(id).subscribe(() => this.reload());
  }
}
