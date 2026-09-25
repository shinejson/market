import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';
import { ProductCard } from '../../core/models';
import { ProductCardComponent } from '../../shared/product-card.component';

@Component({
  selector: 'app-catalog',
  imports: [FormsModule, ProductCardComponent],
  template: `
    <div class="wrap page">
      <h1>Products</h1>
      <form class="filters" (ngSubmit)="load()">
        <input [(ngModel)]="q" name="q" placeholder="Search the square" />
        <select [(ngModel)]="sort" name="sort" (change)="load()">
          <option value="newest">Newest</option>
          <option value="price_asc">Price: low</option>
          <option value="price_desc">Price: high</option>
          <option value="name">Name</option>
        </select>
        <button class="btn" type="submit">Search</button>
      </form>
      @if (loading()) {
        <div class="grid cards">@for (i of [1,2,3,4,5,6]; track i) { <div class="skeleton" style="height:280px"></div> }</div>
      } @else if (!products().length) {
        <div class="empty card">No results. Reset filters and try again.</div>
      } @else {
        <div class="grid cards">
          @for (p of products(); track p.impression_id || p.id) { <app-product-card [product]="p" /> }
        </div>
      }
    </div>
  `,
  styles: [`
    .page { padding: 32px 0 64px; }
    .filters { display:flex; gap: 10px; margin: 16px 0 24px; }
    .filters input, .filters select { flex:1; border:1px solid var(--line); border-radius: 12px; padding: 11px 12px; }
    .cards { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }
  `],
})
export class CatalogComponent {
  private api = inject(ApiService);
  products = signal<ProductCard[]>([]);
  loading = signal(true);
  q = '';
  sort = 'newest';

  constructor() { this.load(); }

  load() {
    this.loading.set(true);
    const params: Record<string, string> = { sort: this.sort, per_page: '24' };
    if (this.q) params['q'] = this.q;
    this.api.marketProducts(params).subscribe({
      next: (res) => { this.products.set(res.data); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }
}
