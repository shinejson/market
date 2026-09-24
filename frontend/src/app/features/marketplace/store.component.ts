import { Component, inject, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { ProductCard, Storefront } from '../../core/models';
import { ProductCardComponent } from '../../shared/product-card.component';

@Component({
  selector: 'app-store',
  imports: [ProductCardComponent],
  template: `
    <div class="banner">
      <div class="wrap">
        <h1>{{ store()?.name }}</h1>
        <p>{{ store()?.description }}</p>
      </div>
    </div>
    <div class="wrap page">
      <div class="grid cards">
        @for (p of products(); track p.id) { <app-product-card [product]="p" /> }
      </div>
    </div>
  `,
  styles: [`
    .banner { background: #1f4b3a; color: #f4efe6; padding: 48px 0; }
    .page { padding: 28px 0 64px; }
    .cards { grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); }
  `],
})
export class StoreComponent {
  store = signal<Storefront | null>(null);
  products = signal<ProductCard[]>([]);
  constructor() {
    const slug = inject(ActivatedRoute).snapshot.paramMap.get('slug')!;
    inject(ApiService).marketStore(slug).subscribe((res) => {
      this.store.set(res.data.store);
      this.products.set(res.data.products);
    });
  }
}
