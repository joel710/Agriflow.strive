import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, UrlTree, Router } from '@angular/router';
import { Observable } from 'rxjs';
import { map, take } from 'rxjs/operators';
import { AuthService } from '../services/auth.service'; // Ajustez le chemin

@Injectable({
  providedIn: 'root'
})
export class AuthGuard implements CanActivate {

  constructor(private authService: AuthService, private router: Router) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {

    return this.authService.currentUser$.pipe(
      take(1), // Important to complete the observable stream
      map(user => {
        const isAuthenticated = !!user;
        if (isAuthenticated) {
          // Check for roles if route.data.roles is defined
          const expectedRoles = route.data['roles'] as Array<string>;
          if (expectedRoles && expectedRoles.length > 0) {
            if (expectedRoles.includes(user.role)) {
              return true; // User has the required role
            } else {
              // Role not authorized, redirect to a 'forbidden' page or home
              this.router.navigate(['/']); // Or an access-denied page
              return false;
            }
          }
          return true; // Authenticated, no specific role required
        } else {
          // Not authenticated, redirect to login page
          this.router.navigate(['/login'], { queryParams: { returnUrl: state.url } });
          return false;
        }
      })
    );
  }
}
