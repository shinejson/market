import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-seller-ai',
  imports: [FormsModule],
  template: `
    <h1>Mock AI studio</h1>
    <form class="card pad" (ngSubmit)="describe()">
      <div class="field"><label>Product</label>
        <select [(ngModel)]="productId" name="product_id">
          @for (p of products(); track p.id) { <option [value]="p.id">{{ p.name }}</option> }
        </select>
      </div>
      <div class="row">
        <button class="btn ok" type="submit">Generate description</button>
        <button class="btn ghost" type="button" (click)="categorize()">Suggest category</button>
      </div>
    </form>
    @if (insight(); as i) {
      <div class="card pad"><h3>Insights</h3><p>{{ i.narrative || i.summary || JSON.stringify(i) }}</p></div>
    }
    @for (g of gens(); track g.id) {
      <div class="card pad">
        <p class="muted">{{ g.feature }} · {{ g.review_status }}</p>
        <p>{{ g.output }}</p>
        @if (g.review_status === 'draft') {
          <div class="row">
            <button class="btn ok" (click)="review(g.id, 'approved')">Approve</button>
            <button class="btn ghost" (click)="review(g.id, 'rejected')">Reject</button>
          </div>
        }
      </div>
    }
  `,
  styles: [`
    .pad { padding: 14px; margin: 10px 0; }
    .row { display:flex; gap: 10px; }
  `],
})
export class SellerAiComponent {
  JSON = JSON;
  private api = inject(ApiService);
  products = signal<any[]>([]);
  gens = signal<any[]>([]);
  insight = signal<any>(null);
  productId = '';

  constructor() {
    this.api.sellerProducts().subscribe((res) => {
      this.products.set(res.data);
      if (res.data[0]) this.productId = res.data[0].id;
    });
    this.reload();
    this.api.aiInsights().subscribe((res) => this.insight.set(res.data));
  }

  reload() {
    this.api.aiGenerations().subscribe((res) => this.gens.set(res.data));
  }

  describe() {
    this.api.aiDescribe(+this.productId).subscribe(() => this.reload());
  }

  categorize() {
    this.api.aiCategorize(+this.productId).subscribe(() => this.reload());
  }

  review(id: number, status: string) {
    this.api.reviewGeneration(id, status).subscribe(() => this.reload());
  }
}
