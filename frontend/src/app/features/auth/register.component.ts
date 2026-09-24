import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth.service';

@Component({
  selector: 'app-register',
  imports: [FormsModule, RouterLink],
  template: `
    <div class="wrap page">
      <form class="card form" (ngSubmit)="submit()">
        <h1>Create an account</h1>
        <div class="field"><label>Name</label><input [(ngModel)]="name" name="name" required /></div>
        <div class="field"><label>Email</label><input [(ngModel)]="email" name="email" type="email" required /></div>
        <div class="field"><label>Phone</label><input [(ngModel)]="phone" name="phone" /></div>
        <div class="field"><label>Password</label><input [(ngModel)]="password" name="password" type="password" required minlength="8" /></div>
        @if (error()) { <p class="err">{{ error() }}</p> }
        <button class="btn" [disabled]="busy()">Register</button>
        <p class="muted">Already here? <a routerLink="/login">Log in</a></p>
      </form>
    </div>
  `,
  styles: [`
    .page { padding: 48px 0; display:flex; justify-content:center; }
    .form { width: min(420px, 100%); padding: 28px; }
  `],
})
export class RegisterComponent {
  private auth = inject(AuthService);
  private router = inject(Router);
  name = '';
  email = '';
  phone = '';
  password = '';
  busy = signal(false);
  error = signal('');

  submit() {
    this.busy.set(true);
    this.auth.register({ name: this.name, email: this.email, password: this.password, phone: this.phone }).subscribe({
      next: () => this.router.navigateByUrl('/'),
      error: (e) => {
        this.error.set(e.error?.error?.message || 'Registration failed');
        this.busy.set(false);
      },
    });
  }
}
