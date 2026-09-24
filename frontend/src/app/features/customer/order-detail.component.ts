import { Component, inject, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { CurrencyPipe, DatePipe } from '@angular/common';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-order-detail',
  imports: [CurrencyPipe, DatePipe],
  template: `
    <div class="wrap page">
      @if (order(); as o) {
        <h1>Order #{{ o.id }}</h1>
        <p class="pill">{{ o.status }}</p>
        <p class="muted">Placed {{ o.placed_at | date:'medium' }}</p>
        @for (so of o.seller_orders; track so.id) {
          <section class="card pad">
            <h3>{{ so.store?.name || ('Store #' + so.store_id) }} — {{ so.status }}</h3>
            @for (item of so.items; track item.id) {
              <p>{{ item.qty }} x {{ item.product_name }} · {{ +item.unit_price | currency }}</p>
            }
          </section>
        }
        <h2>{{ +o.grand_total | currency }}</h2>
        @if (o.status === 'pending_payment' || o.status === 'paid') {
          <button class="btn ghost" (click)="cancel(o.id)">Cancel order</button>
        }
      }
    </div>
  `,
  styles: [` .page { padding: 32px 0 64px; } .pad { padding: 16px; margin: 12px 0; } `],
})
export class OrderDetailComponent {
  private api = inject(ApiService);
  order = signal<any>(null);
  constructor() {
    const id = Number(inject(ActivatedRoute).snapshot.paramMap.get('id'));
    this.api.myOrder(id).subscribe((res) => this.order.set(res.data));
  }
  cancel(id: number) {
    this.api.cancelOrder(id).subscribe((res) => this.order.set(res.data));
  }
}
