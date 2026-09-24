import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CurrencyPipe, DatePipe } from '@angular/common';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-orders',
  imports: [RouterLink, CurrencyPipe, DatePipe],
  template: `
    <div class="wrap page">
      <h1>Your orders</h1>
      @if (!orders().length) {
        <div class="empty card">No orders yet.</div>
      }
      @for (o of orders(); track o.id) {
        <a class="card row" [routerLink]="['/orders', o.id]">
          <div>
            <strong>Order #{{ o.id }}</strong>
            <p class="muted">{{ o.placed_at | date:'medium' }}</p>
          </div>
          <span class="pill">{{ o.status }}</span>
          <strong>{{ +o.grand_total | currency }}</strong>
        </a>
      }
    </div>
  `,
  styles: [`
    .page { padding: 32px 0 64px; display:grid; gap: 12px; }
    .row { padding: 16px 18px; display:flex; justify-content:space-between; align-items:center; gap: 12px; }
  `],
})
export class OrdersComponent {
  orders = signal<any[]>([]);
  constructor() {
    inject(ApiService).myOrders().subscribe((res) => this.orders.set(res.data));
  }
}
