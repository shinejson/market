import { Component, inject, signal } from '@angular/core';
import { CurrencyPipe, JsonPipe } from '@angular/common';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-seller-analytics',
  imports: [CurrencyPipe, JsonPipe],
  template: `
    <h1>Analytics</h1>
    @if (data(); as d) {
      <div class="grid kpis">
        <div class="card pad"><p class="muted">GMV</p><h2>{{ +(d.gmv || 0) | currency }}</h2></div>
        <div class="card pad"><p class="muted">Orders</p><h2>{{ d.orders || 0 }}</h2></div>
        <div class="card pad"><p class="muted">Views</p><h2>{{ d.views || d.product_views || 0 }}</h2></div>
      </div>
      <pre class="card pad">{{ d | json }}</pre>
    }
  `,
  styles: [`
    .kpis { grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); margin: 16px 0; }
    .pad { padding: 16px; }
    pre { overflow:auto; font-size: 12px; }
  `],
})
export class SellerAnalyticsComponent {
  data = signal<any>(null);
  constructor() {
    inject(ApiService).sellerAnalytics().subscribe((res) => this.data.set(res.data));
  }
}
