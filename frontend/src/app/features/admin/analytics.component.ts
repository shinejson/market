import { Component, inject, signal } from '@angular/core';
import { CurrencyPipe, JsonPipe } from '@angular/common';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-admin-analytics',
  imports: [CurrencyPipe, JsonPipe],
  template: `
    <h1>Platform analytics</h1>
    @if (data(); as d) {
      <div class="grid kpis">
        <div class="card pad"><p class="muted">GMV</p><h2>{{ +(d.gmv || 0) | currency }}</h2></div>
        <div class="card pad"><p class="muted">Orders</p><h2>{{ d.orders || 0 }}</h2></div>
      </div>
    }
    @if (insight(); as i) {
      <div class="card pad"><h3>Insights</h3><p>{{ i.narrative || i.summary || (i | json) }}</p></div>
    }
    <pre class="card pad">{{ data() | json }}</pre>
  `,
  styles: [`
    .kpis { grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); margin: 16px 0; }
    .pad { padding: 16px; margin-bottom: 10px; }
    pre { overflow:auto; font-size: 12px; }
  `],
})
export class AdminAnalyticsComponent {
  data = signal<any>(null);
  insight = signal<any>(null);
  constructor() {
    const api = inject(ApiService);
    api.adminAnalytics().subscribe((res) => this.data.set(res.data));
    api.adminInsights().subscribe((res) => this.insight.set(res.data));
  }
}
