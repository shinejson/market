import { Component, inject, signal } from '@angular/core';
import { CurrencyPipe } from '@angular/common';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-seller-orders',
  imports: [CurrencyPipe],
  template: `
    <h1>Fulfilment queue</h1>
    @for (o of orders(); track o.id) {
      <div class="card pad">
        <div class="row">
          <strong>#{{ o.id }}</strong>
          <span class="pill">{{ o.status }}</span>
          <span>{{ +o.subtotal | currency }}</span>
        </div>
        @for (item of o.items; track item.id) {
          <p class="muted">{{ item.qty }} x {{ item.product_name }}</p>
        }
        <div class="actions">
          @if (o.status === 'awaiting_fulfillment') {
            <button class="btn" (click)="move(o.id, 'processing')">Start processing</button>
          }
          @if (o.status === 'processing') {
            <button class="btn" (click)="move(o.id, 'shipped')">Mark shipped</button>
          }
          @if (o.status === 'shipped') {
            <button class="btn ok" (click)="move(o.id, 'delivered')">Mark delivered</button>
          }
        </div>
      </div>
    }
  `,
  styles: [`
    .pad { padding: 16px; margin: 10px 0; }
    .row { display:flex; gap: 12px; align-items:center; }
    .actions { margin-top: 8px; }
  `],
})
export class SellerOrdersComponent {
  private api = inject(ApiService);
  orders = signal<any[]>([]);
  constructor() { this.reload(); }
  reload() { this.api.sellerOrders().subscribe((res) => this.orders.set(res.data)); }
  move(id: number, status: string) {
    this.api.updateSellerOrderStatus(id, status).subscribe(() => this.reload());
  }
}
