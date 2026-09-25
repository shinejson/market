import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-seller-api-keys',
  imports: [FormsModule],
  template: `
    <h1>Seller API keys</h1>
    <form class="card pad" (ngSubmit)="create()">
      <div class="field"><label>Name</label><input [(ngModel)]="name" name="name" required /></div>
      <p class="muted">Scopes: products:read, orders:read, orders:fulfill, inventory:write, settlements:read</p>
      <button class="btn ok" type="submit">Create key</button>
    </form>
    @if (secret()) {
      <div class="card pad">
        <p>Copy this secret now. It will not be shown again.</p>
        <code>{{ secret() }}</code>
      </div>
    }
    @for (k of keys(); track k.id) {
      <div class="card pad row">
        <div>
          <strong>{{ k.name }}</strong>
          <p class="muted">{{ k.key_prefix }}… · {{ k.environment }} · {{ k.revoked_at ? 'revoked' : 'active' }}</p>
        </div>
        @if (!k.revoked_at) {
          <button class="btn ghost" (click)="revoke(k.id)">Revoke</button>
        }
      </div>
    }
  `,
  styles: [`
    .pad { padding: 14px; margin: 10px 0; }
    .row { display:flex; justify-content:space-between; align-items:center; }
    code { word-break: break-all; }
  `],
})
export class SellerApiKeysComponent {
  private api = inject(ApiService);
  keys = signal<any[]>([]);
  name = 'ERP sync';
  secret = signal('');

  constructor() { this.reload(); }

  reload() {
    this.api.sellerApiKeys().subscribe((res) => this.keys.set(res.data));
  }

  create() {
    this.api.createApiKey({
      name: this.name,
      scopes: ['products:read', 'orders:read', 'orders:fulfill', 'inventory:write', 'settlements:read'],
      environment: 'live',
    }).subscribe((res) => {
      this.secret.set(res.data.secret);
      this.reload();
    });
  }

  revoke(id: number) {
    this.api.revokeApiKey(id).subscribe(() => this.reload());
  }
}
