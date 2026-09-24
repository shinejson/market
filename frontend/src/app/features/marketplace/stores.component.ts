import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { Storefront } from '../../core/models';

@Component({
  selector: 'app-stores',
  imports: [RouterLink],
  template: `
    <div class="wrap page">
      <h1>Stores</h1>
      <div class="grid">
        @for (s of stores(); track s.id) {
          <a class="card store" [routerLink]="['/stores', s.slug]">
            <h3 class="serif">{{ s.name }}</h3>
            <p class="muted">{{ s.description }}</p>
            <span class="pill">{{ s.city }}</span>
          </a>
        }
      </div>
    </div>
  `,
  styles: [`
    .page { padding: 32px 0 64px; }
    .grid { grid-template-columns: repeat(auto-fill, minmax(280px,1fr)); }
    .store { padding: 22px; display:block; }
  `],
})
export class StoresComponent {
  stores = signal<Storefront[]>([]);
  constructor() {
    inject(ApiService).marketStores().subscribe((res) => this.stores.set(res.data));
  }
}
