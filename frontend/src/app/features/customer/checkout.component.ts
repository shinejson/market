import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { CurrencyPipe } from '@angular/common';
import { ApiService } from '../../core/api.service';
import { Address, CartPayload } from '../../core/models';

@Component({
  selector: 'app-checkout',
  imports: [FormsModule, CurrencyPipe],
  template: `
    <div class="wrap page">
      <h1>Checkout</h1>
      @if (quote(); as q) {
        <p class="muted">{{ q.groups.length }} store(s) · grand total {{ +q.totals.grand_total | currency }}</p>
      }
      <section class="card pad">
        <h3>Shipping address</h3>
        @for (a of addresses(); track a.id) {
          <label class="addr"><input type="radio" name="addr" [value]="a.id" [(ngModel)]="addressId" /> {{ a.full_name }} — {{ a.line1 }}, {{ a.city }}</label>
        }
        <details>
          <summary>New address</summary>
          <div class="field"><label>Full name</label><input [(ngModel)]="newAddr.full_name" name="full_name" /></div>
          <div class="field"><label>Line 1</label><input [(ngModel)]="newAddr.line1" name="line1" /></div>
          <div class="field"><label>City</label><input [(ngModel)]="newAddr.city" name="city" /></div>
          <div class="field"><label>Country</label><input [(ngModel)]="newAddr.country" name="country" maxlength="2" /></div>
          <button type="button" class="btn ghost" (click)="saveAddress()">Save address</button>
        </details>
      </section>
      @if (error()) { <p class="err">{{ error() }}</p> }
      <button class="btn accent" [disabled]="busy() || !addressId" (click)="pay()">Place order and pay</button>
    </div>
  `,
  styles: [`
    .page { padding: 32px 0 64px; display:grid; gap: 16px; max-width: 720px; }
    .pad { padding: 18px; }
    .addr { display:block; margin: 8px 0; }
  `],
})
export class CheckoutComponent {
  private api = inject(ApiService);
  private router = inject(Router);
  quote = signal<CartPayload | null>(null);
  addresses = signal<Address[]>([]);
  addressId: number | null = null;
  busy = signal(false);
  error = signal('');
  newAddr = { full_name: '', line1: '', city: '', country: 'GH' };

  constructor() {
    this.api.checkoutQuote().subscribe({
      next: (res) => this.quote.set(res.data),
      error: (e) => this.error.set(e.error?.error?.message || 'Cart issue'),
    });
    this.api.addresses().subscribe((res) => {
      this.addresses.set(res.data);
      const def = res.data.find((a) => a.is_default) || res.data[0];
      this.addressId = def?.id ?? null;
    });
  }

  saveAddress() {
    this.api.createAddress({ ...this.newAddr, is_default: !this.addresses().length }).subscribe((res) => {
      this.addresses.set([...this.addresses(), res.data]);
      this.addressId = res.data.id;
    });
  }

  pay() {
    if (!this.addressId) return;
    this.busy.set(true);
    const key = crypto.randomUUID();
    this.api.checkout(this.addressId, key).subscribe({
      next: (res) => {
        const url = res.payment?.url as string;
        if (url) {
          this.api.mockPay(url.replace(window.location.origin, '')).subscribe({
            next: () => this.router.navigate(['/orders', res.order.id]),
            error: () => this.router.navigate(['/orders', res.order.id]),
          });
        } else {
          this.router.navigate(['/orders', res.order.id]);
        }
      },
      error: (e) => {
        this.error.set(e.error?.error?.message || 'Checkout failed');
        this.busy.set(false);
      },
    });
  }
}
