import { Component, inject } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '../core/auth.service';

@Component({
  selector: 'app-seller-shell',
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  template: `
    <div class="dash">
      <aside>
        <a routerLink="/" class="brand serif">MarketHub</a>
        <p class="muted">Seller console</p>
        <nav>
          <a routerLink="/seller" routerLinkActive="on" [routerLinkActiveOptions]="{exact:true}">Dashboard</a>
          <a routerLink="/seller/orders" routerLinkActive="on">Orders</a>
          <a routerLink="/seller/products" routerLinkActive="on">Products</a>
          <a routerLink="/seller/inventory" routerLinkActive="on">Inventory</a>
          <a routerLink="/seller/ads" routerLinkActive="on">Ads</a>
          <a routerLink="/seller/domains" routerLinkActive="on">Domains</a>
          <a routerLink="/seller/api-keys" routerLinkActive="on">API keys</a>
          <a routerLink="/seller/webhooks" routerLinkActive="on">Webhooks</a>
          <a routerLink="/seller/ai" routerLinkActive="on">AI</a>
          <a routerLink="/seller/analytics" routerLinkActive="on">Analytics</a>
        </nav>
        <button class="btn ghost" (click)="auth.logout()">Log out</button>
      </aside>
      <section class="body"><router-outlet /></section>
    </div>
  `,
  styles: [`
    .dash { display:grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
    aside { padding: 28px 20px; border-right: 1px solid var(--line); background: #f7f1e4; display:flex; flex-direction:column; gap: 12px; }
    .brand { font-size: 22px; }
    nav { display:flex; flex-direction:column; gap: 8px; margin: 16px 0 auto; }
    nav a { padding: 10px 12px; border-radius: 12px; font-weight: 600; }
    nav a.on { background: var(--ink); color: #fff; }
    .body { padding: 28px; }
    @media (max-width: 800px) { .dash { grid-template-columns: 1fr; } }
  `],
})
export class SellerShellComponent {
  auth = inject(AuthService);
}
