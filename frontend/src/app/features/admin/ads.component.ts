import { Component, inject, signal } from '@angular/core';
import { CurrencyPipe } from '@angular/common';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-admin-ads',
  imports: [CurrencyPipe],
  template: `
    <h1>Ad campaigns</h1>
    @if (health(); as h) {
      <div class="card pad"><p class="muted">Webhooks {{ h.active }}/{{ h.endpoints }} active · failed 24h {{ h.failed_24h }}</p></div>
    }
    @for (c of campaigns(); track c.id) {
      <div class="card pad">
        <strong>{{ c.name }}</strong>
        <p class="muted">tenant {{ c.tenant_id }} · {{ c.status }} · spent {{ +c.spent_total | currency }}</p>
      </div>
    }
    @if (costs().length) {
      <h3>AI cost by tenant</h3>
      @for (row of costs(); track row.tenant_id) {
        <div class="card pad row">
          <span>tenant {{ row.tenant_id }}</span>
          <span>{{ row.tokens }} tokens · {{ +row.cost | currency }}</span>
        </div>
      }
    }
  `,
  styles: [`
    .pad { padding: 14px; margin: 10px 0; }
    .row { display:flex; justify-content:space-between; }
  `],
})
export class AdminAdsComponent {
  campaigns = signal<any[]>([]);
  costs = signal<any[]>([]);
  health = signal<any>(null);
  constructor() {
    const api = inject(ApiService);
    api.adminAds().subscribe((res) => this.campaigns.set(res.data));
    api.adminAiCosts().subscribe((res) => this.costs.set(res.data));
    api.adminWebhookHealth().subscribe((res) => this.health.set(res.data));
  }
}
