import { Component, inject } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '../core/auth.service';

@Component({
  selector: 'app-admin-shell',
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  template: `
    <div class="dash">
      <aside>
        <a routerLink="/" class="brand serif">MarketHub</a>
        <p class="muted">Super admin</p>
        <nav>
          <a routerLink="/admin" routerLinkActive="on" [routerLinkActiveOptions]="{exact:true}">Metrics</a>
          <a routerLink="/admin/tenants" routerLinkActive="on">Tenants</a>
          <a routerLink="/admin/orders" routerLinkActive="on">Orders</a>
          <a routerLink="/admin/audit" routerLinkActive="on">Audit log</a>
        </nav>
        <button class="btn ghost" (click)="auth.logout()">Log out</button>
      </aside>
      <section class="body"><router-outlet /></section>
    </div>
  `,
  styles: [`
    .dash { display:grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
    aside { padding: 28px 20px; border-right: 1px solid var(--line); background: #1c1914; color: #f4efe6; display:flex; flex-direction:column; gap: 12px; }
    .brand { font-size: 22px; }
    .muted { color: #cfc6b4; }
    nav { display:flex; flex-direction:column; gap: 8px; margin: 16px 0 auto; }
    nav a { padding: 10px 12px; border-radius: 12px; font-weight: 600; }
    nav a.on { background: #c45c26; color: #fff; }
    .body { padding: 28px; }
    .btn.ghost { color: #f4efe6; border-color: #4a453c; }
  `],
})
export class AdminShellComponent {
  auth = inject(AuthService);
}
