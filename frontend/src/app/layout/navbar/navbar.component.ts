import { Component, OnInit, OnDestroy } from '@angular/core';
import { AuthService, User } from '../../core/services/auth.service'; // Ajustez chemin
import { Subscription } from 'rxjs';
import { Router } from '@angular/router';

@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss']
})
export class NavbarComponent implements OnInit, OnDestroy {
  currentUser: User | null = null;
  private authSubscription!: Subscription;

  constructor(private authService: AuthService, private router: Router) { }

  ngOnInit(): void {
    this.authSubscription = this.authService.currentUser$.subscribe(user => {
      this.currentUser = user;
    });
  }

  ngOnDestroy(): void {
    if (this.authSubscription) {
      this.authSubscription.unsubscribe();
    }
  }

  logout(): void {
    this.authService.logout().subscribe({
      next: () => {
        this.router.navigate(['/login']); // Rediriger vers la page de connexion après déconnexion
      },
      error: (err) => {
        console.error('Logout failed', err);
        // Gérer l'erreur de déconnexion, bien que le service nettoie déjà le local storage
        this.router.navigate(['/login']); // S'assurer de rediriger même en cas d'erreur API
      }
    });
  }
}
