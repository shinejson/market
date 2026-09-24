import { Component, inject, signal } from '@angular/core';
import { CurrencyPipe } from '@angular/common';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-seller-dashboard',
  imports: [CurrencyPipe],
  template: `
    <h1>Dashboard</h1>
    @if (data(); as d) {
      <div class="grid kpis">
        <div class="card pad"><p class="muted">Today sales</p><h2>{{ +d.sales_today | currency }}</h2></div>
        <div class="card pad"><p class="muted">Open orders</p><h2>{{ d.open_orders }}</h2></div>
        <div class="card pad"><p class="muted">Low stock</p><h2>{{ d.low_stock }}</h2></div>
      </div>
      <h3>Recent orders</h3>
      @for (o of d.recent_orders; track o.id) {
        <div class="card pad row">
          <span>#{{ o.id }}</span>
          <span class="pill">{{ o.status }}</span>
          <span>{{ +o.subtotal | currency }}</span>
        </div>
      }
    }
  `,
  styles: [`
    .kpis { grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); margin: 16px 0 28px; }
    .pad { padding: 16px; margin-bottom: 8px; }
    .row { display:flex; justify-content:space-between; }
  `],
})
export class SellerDashboardComponent {
  data = signal<any>(null);
  constructor() {
    inject(ApiService).sellerDashboard().subscribe((res) => this.data.set(res.data));
  }
}
