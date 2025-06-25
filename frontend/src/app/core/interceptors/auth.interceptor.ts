import { Injectable } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptor
} from '@angular/common/http';
import { Observable } from 'rxjs';
import { AuthService } from '../services/auth.service'; // Ajustez le chemin si nécessaire

@Injectable()
export class AuthInterceptor implements HttpInterceptor {

  constructor(private authService: AuthService) {}

  intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    const token = this.authService.currentTokenValue;

    if (token) {
      request = request.clone({
        setHeaders: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json' // Ensure API requests expect JSON
        }
      });
    } else {
      request = request.clone({
        setHeaders: {
          Accept: 'application/json' // For public requests
        }
      });
    }

    return next.handle(request);
  }
}
