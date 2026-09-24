import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { ProductCard, Storefront } from '../../core/models';
import { ProductCardComponent } from '../../shared/product-card.component';

@Component({
  selector: 'app-home',
  imports: [RouterLink, ProductCardComponent],
  template: `
    <section class="hero">
      <div class="wrap">
        <p class="kicker">Independent stores. One checkout.</p>
        <h1>A marketplace built like a city market — many stalls, one square.</h1>
        <p class="lede">Browse products across sellers, keep a multi-store cart, and pay once. Sellers fulfil their own orders.</p>
        <div class="cta">
          <a routerLink="/products" class="btn accent">Shop the square</a>
          <a routerLink="/stores" class="btn ghost">Visit stores</a>
        </div>
      </div>
    </section>
    <section class="wrap block">
      <div class="head">
        <h2>Featured this week</h2>
        <a routerLink="/products">See all</a>
      </div>
      @if (loading()) {
        <div class="grid cards">@for (i of [1,2,3,4]; track i) { <div class="skeleton" style="height:280px"></div> }</div>
      } @else {
        <div class="grid cards">
          @for (p of products(); track p.id) { <app-product-card [product]="p" /> }
        </div>
      }
    </section>
    <section class="wrap block">
      <div class="head"><h2>Stores on the square</h2></div>
      <div class="grid stores">
        @for (s of stores(); track s.id) {
          <a class="card store" [routerLink]="['/stores', s.slug]">
            <h3 class="serif">{{ s.name }}</h3>
            <p class="muted">{{ s.description }}</p>
            <span class="pill">{{ s.city }} · {{ s.delivery_days }} day delivery</span>
          </a>
        }
      </div>
    </section>
  `,
  styles: [`
    .hero { padding: 72px 0 40px; background: radial-gradient(1200px 400px at 10% -10%, #f3d9b8, transparent); }
    h1 { font-size: clamp(36px, 6vw, 64px); margin: 8px 0 12px; max-width: 16ch; }
    .kicker { letter-spacing: .16em; text-transform: uppercase; font-size: 12px; font-weight: 700; color: var(--accent); }
    .lede { max-width: 52ch; font-size: 18px; color: var(--ink-soft); }
    .cta { display:flex; gap: 10px; margin-top: 22px; }
    .block { padding: 28px 0 48px; }
    .head { display:flex; justify-content:space-between; align-items:baseline; }
    .cards { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }
    .stores { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
    .store { padding: 22px; display:block; }
  `],
})
export class HomeComponent {
  private api = inject(ApiService);
  products = signal<ProductCard[]>([]);
  stores = signal<Storefront[]>([]);
  loading = signal(true);

  constructor() {
    this.api.marketProducts({ per_page: 8 }).subscribe({
      next: (res) => { this.products.set(res.data); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
    this.api.marketStores().subscribe((res) => this.stores.set(res.data));
  }
}
