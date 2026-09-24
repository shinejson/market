import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth.service';

@Component({
  selector: 'app-login',
  imports: [FormsModule, RouterLink],
  template: `
    <div class="wrap page">
      <form class="card form" (ngSubmit)="submit()">
        <h1>Welcome back</h1>
        <div class="field"><label>Email</label><input [(ngModel)]="email" name="email" type="email" required /></div>
        <div class="field"><label>Password</label><input [(ngModel)]="password" name="password" type="password" required /></div>
        @if (error()) { <p class="err">{{ error() }}</p> }
        <button class="btn" [disabled]="busy()">Log in</button>
        <p class="muted">No account? <a routerLink="/register">Register</a></p>
        <p class="muted demos">Demos: customer&#64;markethub.test · seller1&#64;markethub.test · admin&#64;markethub.test</p>
      </form>
    </div>
  `,
  styles: [`
    .page { padding: 48px 0; display:flex; justify-content:center; }
    .form { width: min(420px, 100%); padding: 28px; }
    .demos { font-size: 12px; }
  `],
})
export class LoginComponent {
  private auth = inject(AuthService);
  private router = inject(Router);
  email = 'customer@markethub.test';
  password = 'password';
  busy = signal(false);
  error = signal('');

  submit() {
    this.busy.set(true);
    this.error.set('');
    this.auth.login(this.email, this.password).subscribe({
      next: (res) => {
        const role = res.data.user.role;
        if (role === 'super_admin') this.router.navigateByUrl('/admin');
        else if (role === 'tenant_owner' || role === 'store_staff') this.router.navigateByUrl('/seller');
        else this.router.navigateByUrl('/');
      },
      error: (e) => {
        this.error.set(e.error?.error?.message || 'Login failed');
        this.busy.set(false);
      },
    });
  }
}
