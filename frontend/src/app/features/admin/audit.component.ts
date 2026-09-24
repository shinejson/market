import { Component, inject, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { ApiService } from '../../core/api.service';

@Component({
  selector: 'app-admin-audit',
  imports: [DatePipe],
  template: `
    <h1>Audit log</h1>
    @for (a of logs(); track a.id) {
      <div class="card pad">
        <strong>{{ a.action }}</strong> {{ a.subject_type }} #{{ a.subject_id }}
        <p class="muted">{{ a.created_at | date:'medium' }} · actor {{ a.actor_user_id }}</p>
      </div>
    }
  `,
  styles: [` .pad { padding: 12px; margin: 8px 0; } `],
})
export class AdminAuditComponent {
  logs = signal<any[]>([]);
  constructor() {
    inject(ApiService).adminAuditLogs().subscribe((res) => this.logs.set(res.data));
  }
}
