import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CurrencyPipe } from '@angular/common';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-seller-ads',
  imports: [FormsModule, CurrencyPipe],
  template: `
    <div class="head">
      <h1>Sponsored ads</h1>
      <button class="btn" (click)="showForm = !showForm">New campaign</button>
    </div>
    <div class="card pad">
      <p class="muted">Ad wallet</p>
      <h2>{{ +balance() | currency }}</h2>
      <form class="row" (ngSubmit)="fund()">
        <input type="number" min="1" step="1" [(ngModel)]="fundAmount" name="amount" />
        <button class="btn ok" type="submit">Add funds</button>
      </form>
    </div>
    @if (showForm) {
      <form class="card pad" (ngSubmit)="create()">
        <div class="field"><label>Store</label>
          <select [(ngModel)]="form.store_id" name="store_id" required>
            @for (s of stores(); track s.id) { <option [value]="s.id">{{ s.name }}</option> }
          </select>
        </div>
        <div class="field"><label>Name</label><input [(ngModel)]="form.name" name="name" required /></div>
        <div class="field"><label>Daily budget</label><input type="number" step="0.01" [(ngModel)]="form.daily_budget" name="daily_budget" /></div>
        <div class="field"><label>Total budget</label><input type="number" step="0.01" [(ngModel)]="form.total_budget" name="total_budget" /></div>
        <div class="field"><label>CPC bid</label><input type="number" step="0.01" min="0.05" [(ngModel)]="form.bid_cpc" name="bid_cpc" /></div>
        <div class="field"><label>Product</label>
          <select [(ngModel)]="form.product_id" name="product_id" required>
            @for (p of products(); track p.id) { <option [value]="p.id">{{ p.name }}</option> }
          </select>
        </div>
        <button class="btn ok" type="submit">Create draft</button>
      </form>
    }
    @for (c of campaigns(); track c.id) {
      <div class="card pad row">
        <div>
          <strong>{{ c.name }}</strong>
          <p class="muted">{{ c.status }} · bid {{ +c.bid_cpc | currency }} · spent {{ +c.spent_total | currency }}</p>
        </div>
        <button class="btn ghost" (click)="toggle(c)">{{ c.status === 'active' ? 'Pause' : 'Activate' }}</button>
      </div>
    }
  `,
  styles: [`
    .head { display:flex; justify-content:space-between; align-items:center; }
    .pad { padding: 14px; margin: 10px 0; }
    .row { display:flex; justify-content:space-between; align-items:center; gap: 10px; }
    .row input { max-width: 140px; border:1px solid var(--line); border-radius: 12px; padding: 10px; }
  `],
})
export class SellerAdsComponent {
  private api = inject(ApiService);
  balance = signal('0.00');
  campaigns = signal<any[]>([]);
  stores = signal<any[]>([]);
  products = signal<any[]>([]);
  showForm = false;
  fundAmount = 25;
  form = { store_id: '', name: 'Launch', daily_budget: 10, total_budget: 100, bid_cpc: 0.25, product_id: '' };

  constructor() {
    this.reload();
    this.api.sellerStores().subscribe((res) => {
      this.stores.set(res.data);
      if (res.data[0]) this.form.store_id = res.data[0].id;
    });
    this.api.sellerProducts().subscribe((res) => {
      this.products.set(res.data);
      if (res.data[0]) this.form.product_id = res.data[0].id;
    });
  }

  reload() {
    this.api.sellerAds().subscribe((res) => {
      this.balance.set(res.data.balance);
      this.campaigns.set(res.data.campaigns);
    });
  }

  fund() {
    this.api.fundAds(this.fundAmount).subscribe(() => this.reload());
  }

  create() {
    this.api.createAd({
      store_id: this.form.store_id,
      name: this.form.name,
      daily_budget: this.form.daily_budget,
      total_budget: this.form.total_budget,
      bid_cpc: this.form.bid_cpc,
      product_ids: [this.form.product_id],
    }).subscribe(() => { this.showForm = false; this.reload(); });
  }

  toggle(c: any) {
    const status = c.status === 'active' ? 'paused' : 'active';
    this.api.updateAd(c.id, { status }).subscribe(() => this.reload());
  }
}
