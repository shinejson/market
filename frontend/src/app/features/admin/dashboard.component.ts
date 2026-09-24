import { Component, inject, signal } from '@angular/core';
import { CurrencyPipe } from '@angular/common';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-admin-dashboard',
  imports: [CurrencyPipe],
  template: `
    <h1>Platform metrics</h1>
    @if (m(); as d) {
      <div class="grid kpis">
        <div class="card pad"><p class="muted">Tenants</p><h2>{{ d.tenants }}</h2></div>
        <div class="card pad"><p class="muted">Active stores</p><h2>{{ d.active_stores }}</h2></div>
        <div class="card pad"><p class="muted">Customers</p><h2>{{ d.customers }}</h2></div>
        <div class="card pad"><p class="muted">Products</p><h2>{{ d.products }}</h2></div>
        <div class="card pad"><p class="muted">Orders</p><h2>{{ d.orders }}</h2></div>
        <div class="card pad"><p class="muted">GMV</p><h2>{{ +d.gmv | currency }}</h2></div>
        <div class="card pad"><p class="muted">Commission</p><h2>{{ +d.commission | currency }}</h2></div>
      </div>
    }
  `,
  styles: [`
    .kpis { grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); }
    .pad { padding: 16px; }
  `],
})
export class AdminDashboardComponent {
  m = signal<any>(null);
  constructor() {
    inject(ApiService).adminMetrics().subscribe((res) => this.m.set(res.data));
  }
}
