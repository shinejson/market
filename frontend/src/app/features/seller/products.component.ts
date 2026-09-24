import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CurrencyPipe } from '@angular/common';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-seller-products',
  imports: [FormsModule, CurrencyPipe],
  template: `
    <div class="head">
      <h1>Products</h1>
      <button class="btn" (click)="showForm = !showForm">New product</button>
    </div>
    @if (showForm) {
      <form class="card pad" (ngSubmit)="create()">
        <div class="field"><label>Store</label>
          <select [(ngModel)]="form.store_id" name="store_id" required>
            @for (s of stores(); track s.id) { <option [value]="s.id">{{ s.name }}</option> }
          </select>
        </div>
        <div class="field"><label>Name</label><input [(ngModel)]="form.name" name="name" required /></div>
        <div class="field"><label>Price</label><input type="number" step="0.01" [(ngModel)]="form.price" name="price" required /></div>
        <div class="field"><label>Qty</label><input type="number" [(ngModel)]="form.quantity" name="quantity" /></div>
        <div class="field"><label>Status</label>
          <select [(ngModel)]="form.status" name="status">
            <option value="draft">draft</option>
            <option value="active">active</option>
          </select>
        </div>
        <button class="btn ok" type="submit">Save</button>
      </form>
    }
    @for (p of products(); track p.id) {
      <div class="card pad row">
        <div>
          <strong>{{ p.name }}</strong>
          <p class="muted">{{ p.status }} · {{ +p.price | currency }}</p>
        </div>
        <button class="btn ghost" (click)="toggle(p)">{{ p.status === 'active' ? 'Archive' : 'Activate' }}</button>
      </div>
    }
  `,
  styles: [`
    .head { display:flex; justify-content:space-between; align-items:center; }
    .pad { padding: 14px; margin: 10px 0; }
    .row { display:flex; justify-content:space-between; align-items:center; }
  `],
})
export class SellerProductsComponent {
  private api = inject(ApiService);
  products = signal<any[]>([]);
  stores = signal<any[]>([]);
  showForm = false;
  form = { store_id: '', name: '', price: 10, quantity: 10, status: 'active' };

  constructor() {
    this.reload();
    this.api.sellerStores().subscribe((res) => {
      this.stores.set(res.data);
      if (res.data[0]) this.form.store_id = res.data[0].id;
    });
  }

  reload() {
    this.api.sellerProducts().subscribe((res) => this.products.set(res.data));
  }

  create() {
    this.api.createProduct(this.form).subscribe(() => { this.showForm = false; this.reload(); });
  }

  toggle(p: any) {
    const status = p.status === 'active' ? 'archived' : 'active';
    this.api.updateProduct(p.id, { status }).subscribe(() => this.reload());
  }
}
