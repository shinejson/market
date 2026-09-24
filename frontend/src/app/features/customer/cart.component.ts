import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CurrencyPipe } from '@angular/common';
import { ApiService } from '../../core/api.service';
import { CartPayload } from '../../core/models';

@Component({
  selector: 'app-cart',
  imports: [RouterLink, CurrencyPipe],
  template: `
    <div class="wrap page">
      <h1>Cart</h1>
      @if (!cart() || !cart()!.groups.length) {
        <div class="empty card">Your cart is empty. <a routerLink="/products">Browse products</a></div>
      } @else {
        @for (g of cart()!.groups; track g.store.id) {
          <section class="card group">
            <h3>{{ g.store.name }}</h3>
            @for (item of g.items; track item.id) {
              <div class="row">
                <div>
                  <strong>{{ item.product_name }}</strong>
                  <p class="muted">{{ item.sku }} · {{ +item.unit_price | currency }}</p>
                </div>
                <div class="qty">
                  <button class="btn ghost" (click)="setQty(item.id, item.qty - 1)">-</button>
                  <span>{{ item.qty }}</span>
                  <button class="btn ghost" (click)="setQty(item.id, item.qty + 1)">+</button>
                </div>
                <strong>{{ +item.line_total | currency }}</strong>
              </div>
            }
            <p class="muted">Store subtotal {{ +g.subtotal | currency }} · delivery {{ +g.store.delivery_fee | currency }}</p>
          </section>
        }
        <aside class="card totals">
          <p>Items {{ +cart()!.totals.subtotal | currency }}</p>
          <p>Delivery {{ +cart()!.totals.delivery_total | currency }}</p>
          <h2>Total {{ +cart()!.totals.grand_total | currency }}</h2>
          <a routerLink="/checkout" class="btn accent">Checkout</a>
        </aside>
      }
    </div>
  `,
  styles: [`
    .page { padding: 32px 0 64px; display:grid; gap: 16px; }
    .group { padding: 18px; }
    .row { display:flex; justify-content:space-between; gap: 12px; align-items:center; padding: 10px 0; border-bottom: 1px solid var(--line); }
    .qty { display:flex; gap: 8px; align-items:center; }
    .totals { padding: 18px; }
  `],
})
export class CartComponent {
  private api = inject(ApiService);
  cart = signal<CartPayload | null>(null);

  constructor() { this.refresh(); }

  refresh() {
    this.api.cart().subscribe((res) => this.cart.set(res.data));
  }

  setQty(id: number, qty: number) {
    this.api.updateCartItem(id, qty).subscribe((res) => this.cart.set(res.data));
  }
}
