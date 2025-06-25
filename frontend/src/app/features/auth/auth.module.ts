import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms'; // Import ReactiveFormsModule
import { RouterModule, Routes } from '@angular/router';

import { LoginComponent } from './login/login.component';
import { RegisterComponent } from './register/register.component';

// Définir les routes spécifiques à l'authentification
const authRoutes: Routes = [
  { path: 'login', component: LoginComponent },
  { path: 'register', component: RegisterComponent }
  // { path: 'forgot-password', component: ForgotPasswordComponent }, // Example
];

@NgModule({
  declarations: [
    LoginComponent,
    RegisterComponent
  ],
  imports: [
    CommonModule,
    ReactiveFormsModule, // Ajouter ReactiveFormsModule ici
    RouterModule.forChild(authRoutes) // Importer les routes d'authentification
  ],
  // Pas besoin d'exporter les composants s'ils sont seulement utilisés via le routing
})
export class AuthModule { }
