import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, tap, catchError, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import { User } from '../models/user.model'; // Interface/classe User à définir

// Définition de l'interface User (peut être dans un fichier séparé user.model.ts)
export interface ProducerProfile {
  id: number;
  user_id: number;
  farm_name: string;
  siret?: string;
  experience_years?: number;
  farm_type?: string;
  surface_hectares?: number;
  farm_address?: string;
  certifications?: string;
  delivery_availability?: string;
  farm_description?: string;
  farm_photo_url?: string;
  created_at: string;
  updated_at: string;
}

export interface CustomerProfile {
  id: number;
  user_id: number;
  delivery_address?: string;
  food_preferences?: string;
  created_at: string;
  updated_at: string;
}

export interface UserSettings {
  id: number;
  user_id: number;
  notification_email: boolean;
  notification_sms: boolean;
  notification_app: boolean;
  language: string;
  theme: string;
  created_at: string;
  updated_at: string;
}

export interface Wallet {
  id: number;
  user_id: number;
  balance: string; // Laisser en string si l'API retourne comme ça, convertir en number si besoin
  currency: string;
  created_at: string;
  updated_at: string;
}


export interface User {
  id: number;
  email: string;
  phone?: string;
  role: 'producteur' | 'client' | 'admin';
  is_active: boolean;
  last_login?: string;
  created_at: string;
  updated_at: string;
  email_verified_at?: string;
  producer?: ProducerProfile;
  customer?: CustomerProfile;
  settings?: UserSettings;
  wallet?: Wallet;
}


@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = environment.apiUrl;
  private currentUserSubject = new BehaviorSubject<User | null>(this.getPersistedUser());
  public currentUser$ = this.currentUserSubject.asObservable();
  private currentTokenSubject = new BehaviorSubject<string | null>(this.getPersistedToken());
  public currentToken$ = this.currentTokenSubject.asObservable();

  constructor(private http: HttpClient) { }

  public get currentUserValue(): User | null {
    return this.currentUserSubject.value;
  }

  public get currentTokenValue(): string | null {
    return this.currentTokenSubject.value;
  }

  private getPersistedUser(): User | null {
    const user = localStorage.getItem('currentUser');
    return user ? JSON.parse(user) : null;
  }

  private getPersistedToken(): string | null {
    return localStorage.getItem('authToken');
  }

  register(userData: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/register`, userData);
  }

  login(credentials: any): Observable<{ token: string, user: User }> {
    return this.http.post<{ token: string, user: User }>(`${this.apiUrl}/login`, credentials).pipe(
      tap(response => {
        if (response.user && response.token) {
          localStorage.setItem('currentUser', JSON.stringify(response.user));
          localStorage.setItem('authToken', response.token);
          this.currentUserSubject.next(response.user);
          this.currentTokenSubject.next(response.token);
        }
      }),
      catchError(this.handleError)
    );
  }

  logout(): Observable<any> {
    // Always remove local storage items even if API call fails
    const cleanup = () => {
      localStorage.removeItem('currentUser');
      localStorage.removeItem('authToken');
      this.currentUserSubject.next(null);
      this.currentTokenSubject.next(null);
    };

    // It's important to send the token for the logout endpoint to invalidate it server-side
    if (!this.currentTokenValue) {
        cleanup();
        return new Observable(observer => { // Or use of(null) from RxJS
            observer.next(null);
            observer.complete();
        });
    }

    // No need to set headers manually if an HttpInterceptor is used for token
    return this.http.post(`${this.apiUrl}/logout`, {}).pipe(
      tap(() => cleanup()),
      catchError(error => {
        cleanup(); // Ensure cleanup even on API error
        return this.handleError(error);
      })
    );
  }

  // Method to get current user details from API (e.g., on app load if token exists)
  fetchUser(): Observable<User> {
    return this.http.get<User>(`${this.apiUrl}/user`).pipe(
      tap(user => {
        localStorage.setItem('currentUser', JSON.stringify(user));
        this.currentUserSubject.next(user);
      }),
      catchError(error => {
        // If fetching user fails (e.g. token expired), log out
        this.logout().subscribe();
        return this.handleError(error);
      })
    );
  }

  isAuthenticated(): boolean {
    return !!this.currentTokenValue;
  }

  // Basic error handler
  private handleError(error: any) {
    console.error('API Error:', error);
    // You could further parse error.error or error.message
    return throwError(() => new Error(error.message || 'Something bad happened; please try again later.'));
  }
}
