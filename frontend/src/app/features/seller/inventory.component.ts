import { Component, inject, signal } from '@angular/core';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-seller-inventory',
  template: `
    <h1>Low stock</h1>
    @if (!items().length) { <div class="empty card">No low-stock items.</div> }
    @for (i of items(); track i.id) {
      <div class="card pad">
        <strong>{{ i.variant?.product?.name || 'Variant ' + i.variant_id }}</strong>
        <p class="muted">Available {{ i.quantity - i.reserved }} · threshold {{ i.low_stock_threshold }}</p>
      </div>
    }
  `,
  styles: [` .pad { padding: 14px; margin: 8px 0; } `],
})
export class SellerInventoryComponent {
  items = signal<any[]>([]);
  constructor() {
    inject(ApiService).lowStock().subscribe((res) => this.items.set(res.data));
  }
}
