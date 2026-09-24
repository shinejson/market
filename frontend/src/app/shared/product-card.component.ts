import { Component, Input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CurrencyPipe } from '@angular/common';
import { ProductCard } from '../core/models';

@Component({
  selector: 'app-product-card',
  imports: [RouterLink, CurrencyPipe],
  template: `
    <a class="card item" [routerLink]="['/products', product.slug]">
      <div class="thumb" [style.backgroundImage]="bg"></div>
      <div class="meta">
        <span class="pill">{{ product.store?.name }}</span>
        <h3>{{ product.name }}</h3>
        <p class="price">{{ +product.price | currency }}</p>
      </div>
    </a>
  `,
  styles: [`
    .item { overflow:hidden; display:block; height:100%; }
    .thumb { aspect-ratio: 1; background: #d9d0c0 center/cover no-repeat; }
    .meta { padding: 14px 16px 18px; }
    h3 { margin: 8px 0 4px; font-size: 18px; }
    .price { margin: 0; font-size: 20px; }
  `],
})
export class ProductCardComponent {
  @Input({ required: true }) product!: ProductCard;
  get bg() {
    return this.product.image ? `url('${this.product.image}')` : 'linear-gradient(135deg,#1f4b3a,#c45c26)';
  }
}
