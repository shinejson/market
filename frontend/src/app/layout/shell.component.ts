import { Component, inject } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '../core/auth.service';

@Component({
  selector: 'app-shell',
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  template: `
    <header class="top">
      <div class="wrap bar">
        <a routerLink="/" class="brand serif">MarketHub</a>
        <nav>
          <a routerLink="/" routerLinkActive="on" [routerLinkActiveOptions]="{exact:true}">Home</a>
          <a routerLink="/products" routerLinkActive="on">Products</a>
          <a routerLink="/stores" routerLinkActive="on">Stores</a>
          @if (auth.isLoggedIn()) {
            <a routerLink="/cart" routerLinkActive="on">Cart</a>
            <a routerLink="/orders" routerLinkActive="on">Orders</a>
          }
        </nav>
        <div class="actions">
          @if (!auth.isLoggedIn()) {
            <a routerLink="/login" class="btn ghost">Log in</a>
            <a routerLink="/register" class="btn">Join</a>
          } @else {
            @if (auth.hasRole('tenant_owner','store_staff')) {
              <a routerLink="/seller" class="btn ghost">Seller</a>
            }
            @if (auth.hasRole('super_admin')) {
              <a routerLink="/admin" class="btn ghost">Admin</a>
            }
            @if (!auth.hasRole('tenant_owner','store_staff','super_admin')) {
              <a routerLink="/sell" class="btn ghost">Sell</a>
            }
            <button class="btn" (click)="auth.logout()">Log out</button>
          }
        </div>
      </div>
    </header>
    <main><router-outlet /></main>
    <footer>
      <div class="wrap">
        <p class="serif">One marketplace. Many stores.</p>
        <p class="muted">Phase 1 marketplace SaaS — demo accounts use password <code>password</code>.</p>
      </div>
    </footer>
  `,
  styles: [`
    .top { position: sticky; top: 0; z-index: 20; background: rgba(244,239,230,.92); backdrop-filter: blur(10px); border-bottom: 1px solid var(--line); }
    .bar { display:flex; align-items:center; gap: 20px; min-height: 72px; }
    .brand { font-size: 26px; }
    nav { display:flex; gap: 16px; flex: 1; }
    nav a { font-weight: 600; color: var(--ink-soft); }
    nav a.on { color: var(--ink); }
    .actions { display:flex; gap: 8px; align-items:center; }
    main { min-height: calc(100vh - 200px); }
    footer { padding: 40px 0 56px; border-top: 1px solid var(--line); }
    @media (max-width: 720px) {
      .bar { flex-wrap: wrap; padding: 12px 0; }
      nav { order: 3; width: 100%; overflow:auto; }
    }
  `],
})
export class ShellComponent {
  auth = inject(AuthService);
}
