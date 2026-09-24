import { Component, inject, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { CurrencyPipe } from '@angular/common';
import { ApiService } from '../../core/api.service';
import { AuthService } from '../../core/auth.service';
import { ProductCard } from '../../core/models';

@Component({
  selector: 'app-product',
  imports: [RouterLink, CurrencyPipe],
  template: `
    <div class="wrap page">
      @if (error()) { <p class="err">{{ error() }}</p> }
      @if (product(); as p) {
        <div class="grid layout">
          <div class="card media" [style.backgroundImage]="bg"></div>
          <div>
            <a class="muted" [routerLink]="['/stores', p.store?.slug]">{{ p.store?.name }}</a>
            <h1>{{ p.name }}</h1>
            <p class="price">{{ +p.price | currency }}</p>
            <p>{{ p.description }}</p>
            @if (p.variants[0]; as v) {
              <p class="muted">SKU {{ v.sku }} · {{ v.available }} in stock</p>
              <div class="row">
                <button class="btn accent" [disabled]="busy() || v.available < 1" (click)="add(v.id)">Add to cart</button>
                <span class="pill">{{ p.brand }}</span>
              </div>
            }
            @if (notice()) { <p class="muted">{{ notice() }}</p> }
          </div>
        </div>
      }
    </div>
  `,
  styles: [`
    .page { padding: 32px 0 64px; }
    .layout { grid-template-columns: 1.1fr 1fr; align-items:start; }
    .media { min-height: 420px; background: #d9d0c0 center/cover; }
    h1 { margin: 8px 0; }
    .price { font-size: 32px; }
    .row { display:flex; gap: 12px; align-items:center; margin-top: 18px; }
    @media (max-width: 800px) { .layout { grid-template-columns: 1fr; } }
  `],
})
export class ProductComponent {
  private api = inject(ApiService);
  private auth = inject(AuthService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  product = signal<ProductCard | null>(null);
  error = signal('');
  notice = signal('');
  busy = signal(false);

  get bg() {
    const img = this.product()?.image;
    return img ? `url('${img}')` : 'linear-gradient(135deg,#1f4b3a,#c45c26)';
  }

  constructor() {
    const slug = this.route.snapshot.paramMap.get('slug')!;
    this.api.marketProduct(slug).subscribe({
      next: (res) => this.product.set(res.data),
      error: () => this.error.set('Product not found.'),
    });
  }

  add(variantId: number) {
    if (!this.auth.isLoggedIn()) {
      this.router.navigate(['/login']);
      return;
    }
    this.busy.set(true);
    this.api.addToCart(variantId, 1).subscribe({
      next: () => { this.notice.set('Added to cart.'); this.busy.set(false); },
      error: (e) => { this.error.set(e.error?.error?.message || 'Could not add to cart'); this.busy.set(false); },
    });
  }
}
