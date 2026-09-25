import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-seller-domains',
  imports: [FormsModule],
  template: `
    <h1>Custom domains</h1>
    <form class="card pad" (ngSubmit)="add()">
      <div class="field"><label>Hostname</label><input [(ngModel)]="host" name="host" placeholder="shop.example.com" required /></div>
      <button class="btn ok" type="submit">Request</button>
    </form>
    @if (txt()) {
      <div class="card pad">
        <p>Add this TXT record, then verify.</p>
        <p><strong>{{ txt()?.name }}</strong></p>
        <p class="muted">{{ txt()?.value }}</p>
      </div>
    }
    @for (d of domains(); track d.id) {
      <div class="card pad row">
        <div>
          <strong>{{ d.domain }}</strong>
          <p class="muted">{{ d.status }} · cert {{ d.cert_status }}</p>
        </div>
        <button class="btn ghost" (click)="verify(d.id)">Verify</button>
      </div>
    }
  `,
  styles: [`
    .pad { padding: 14px; margin: 10px 0; }
    .row { display:flex; justify-content:space-between; align-items:center; }
  `],
})
export class SellerDomainsComponent {
  private api = inject(ApiService);
  domains = signal<any[]>([]);
  host = '';
  txt = signal<{ name: string; value: string } | null>(null);

  constructor() { this.reload(); }

  reload() {
    this.api.sellerDomains().subscribe((res) => this.domains.set(res.data));
  }

  add() {
    this.api.addDomain(this.host).subscribe((res) => {
      this.txt.set({ name: res.data.txt_name, value: res.data.txt_value });
      this.host = '';
      this.reload();
    });
  }

  verify(id: number) {
    this.api.verifyDomain(id, true).subscribe(() => this.reload());
  }
}
