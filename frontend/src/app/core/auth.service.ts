import { Injectable, computed, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { tap } from 'rxjs/operators';
import { AuthResponse, User } from './models';

const TOKEN_KEY = 'mh_token';
const USER_KEY = 'mh_user';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly userSignal = signal<User | null>(this.readUser());
  readonly user = this.userSignal.asReadonly();
  readonly isLoggedIn = computed(() => !!this.userSignal());
  readonly role = computed(() => this.userSignal()?.role ?? null);

  constructor(private http: HttpClient, private router: Router) {}

  token(): string | null {
    return localStorage.getItem(TOKEN_KEY);
  }

  login(email: string, password: string) {
    return this.http.post<{ data: AuthResponse }>('/api/auth/login', { email, password }).pipe(
      tap((res) => this.persist(res.data)),
    );
  }

  register(payload: { name: string; email: string; password: string; phone?: string }) {
    return this.http.post<{ data: AuthResponse }>('/api/auth/register', payload).pipe(
      tap((res) => this.persist(res.data)),
    );
  }

  logout() {
    this.http.post('/api/auth/logout', {}).subscribe({ complete: () => this.clear() });
    this.clear();
    this.router.navigateByUrl('/');
  }

  hasRole(...roles: string[]): boolean {
    const role = this.userSignal()?.role;
    return !!role && roles.includes(role);
  }

  private persist(data: AuthResponse) {
    localStorage.setItem(TOKEN_KEY, data.token);
    localStorage.setItem(USER_KEY, JSON.stringify(data.user));
    this.userSignal.set(data.user);
  }

  private clear() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    this.userSignal.set(null);
  }

  private readUser(): User | null {
    try {
      const raw = localStorage.getItem(USER_KEY);
      return raw ? (JSON.parse(raw) as User) : null;
    } catch {
      return null;
    }
  }
}
