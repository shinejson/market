import { Component, inject, signal } from '@angular/core';
import { CurrencyPipe } from '@angular/common';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-admin-orders',
  imports: [CurrencyPipe],
  template: `
    <h1>Orders</h1>
    @for (o of orders(); track o.id) {
      <div class="card pad row">
        <span>#{{ o.id }} {{ o.user?.email }}</span>
        <span class="pill">{{ o.status }}</span>
        <span>{{ +o.grand_total | currency }}</span>
      </div>
    }
  `,
  styles: [` .pad { padding: 14px; margin: 8px 0; } .row { display:flex; justify-content:space-between; } `],
})
export class AdminOrdersComponent {
  orders = signal<any[]>([]);
  constructor() {
    inject(ApiService).adminOrders().subscribe((res) => this.orders.set(res.data));
  }
}
