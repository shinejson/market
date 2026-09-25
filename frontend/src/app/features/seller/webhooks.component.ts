import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-seller-webhooks',
  imports: [FormsModule],
  template: `
    <h1>Webhooks</h1>
    <form class="card pad" (ngSubmit)="create()">
      <div class="field"><label>Callback URL</label><input [(ngModel)]="url" name="url" placeholder="https://example.test/hooks" required /></div>
      <div class="field"><label>Event</label>
        <select [(ngModel)]="event" name="event">
          @for (e of catalog(); track e) { <option [value]="e">{{ e }}</option> }
        </select>
      </div>
      <button class="btn ok" type="submit">Create</button>
    </form>
    @if (secret()) {
      <div class="card pad"><p>Signing secret (copy once)</p><code>{{ secret() }}</code></div>
    }
    @for (w of hooks(); track w.id) {
      <div class="card pad">
        <strong>{{ w.url }}</strong>
        <p class="muted">{{ w.status }} · {{ (w.event_types || []).join(', ') }}</p>
      </div>
    }
  `,
  styles: [`
    .pad { padding: 14px; margin: 10px 0; }
    code { word-break: break-all; }
  `],
})
export class SellerWebhooksComponent {
  private api = inject(ApiService);
  hooks = signal<any[]>([]);
  catalog = signal<string[]>([]);
  url = 'https://example.test/hooks';
  event = 'order.placed';
  secret = signal('');

  constructor() {
    this.reload();
    this.api.webhookCatalog().subscribe((res) => {
      this.catalog.set(res.data);
      if (res.data[0]) this.event = res.data[0];
    });
  }

  reload() {
    this.api.sellerWebhooks().subscribe((res) => this.hooks.set(res.data));
  }

  create() {
    this.api.createWebhook({ url: this.url, event_types: [this.event] }).subscribe((res) => {
      this.secret.set(res.data.secret);
      this.reload();
    });
  }
}
