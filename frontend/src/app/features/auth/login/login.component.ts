import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service'; // Ajustez chemin

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss']
})
export class LoginComponent implements OnInit {
  loginForm!: FormGroup;
  errorMessage: string | null = null;
  isLoading: boolean = false;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required]]
    });
  }

  onSubmit(): void {
    if (this.loginForm.invalid) {
      this.errorMessage = "Veuillez corriger les erreurs du formulaire.";
      return;
    }
    this.isLoading = true;
    this.errorMessage = null;

    this.authService.login(this.loginForm.value).subscribe({
      next: (response) => {
        this.isLoading = false;
        // Redirection basée sur le rôle ou vers le tableau de bord par défaut
        const userRole = response.user.role;
        if (userRole === 'producteur') {
          this.router.navigate(['/tableau-producteur']);
        } else if (userRole === 'client') {
          this.router.navigate(['/tableau-client']);
        } else if (userRole === 'admin') {
          this.router.navigate(['/admin']);
        } else {
          this.router.navigate(['/']); // Page d'accueil par défaut
        }
      },
      error: (error) => {
        this.isLoading = false;
        this.errorMessage = error.error?.message || 'Échec de la connexion. Veuillez réessayer.';
        console.error('Login error:', error);
      }
    });
  }
}
