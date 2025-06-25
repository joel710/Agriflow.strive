import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, AbstractControl, ValidationErrors } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service'; // Ajustez chemin

// Custom validator for password matching
export function passwordMatchValidator(control: AbstractControl): ValidationErrors | null {
  const password = control.get('password');
  const password_confirmation = control.get('password_confirmation');

  if (password && password_confirmation && password.value !== password_confirmation.value) {
    return { passwordMismatch: true };
  }
  return null;
}

@Component({
  selector: 'app-register',
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.scss']
})
export class RegisterComponent implements OnInit {
  registerForm!: FormGroup;
  errorMessage: string | null = null;
  isLoading: boolean = false;
  currentStep: 'roleSelection' | 'producerForm' | 'clientForm' = 'roleSelection';

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.registerForm = this.fb.group({
      role: ['client', Validators.required], // Default role
      email: ['', [Validators.required, Validators.email]],
      phone: [''],
      password: ['', [Validators.required, Validators.minLength(8)]], // Add more validators from Laravel rule
      password_confirmation: ['', Validators.required],
      // Producer fields
      farm_name: [''],
      siret: [''], // Add pattern validator if needed: Validators.pattern(/^\d{14}$/)
      experience_years: [null],
      farm_type: ['cultures'],
      surface_hectares: [null],
      farm_address: [''],
      certifications: [''],
      delivery_availability: ['3j'],
      farm_description: [''],
      // farm_photo_url: [''], // File upload handled differently

      // Customer fields
      delivery_address: [''],
      food_preferences: ['aucune'],
    }, { validators: passwordMatchValidator });

    this.onRoleChange(); // Set initial conditional validators
  }

  onRoleChange(): void {
    const role = this.registerForm.get('role')?.value;
    this.clearConditionalValidators();

    if (role === 'producteur') {
      this.registerForm.get('farm_name')?.setValidators([Validators.required, Validators.maxLength(255)]);
      this.registerForm.get('siret')?.setValidators([Validators.pattern(/^\d{14}$/)]); // Example for SIRET
      // Add other producer validators as needed
    } else if (role === 'client') {
      this.registerForm.get('delivery_address')?.setValidators([Validators.required]);
      // Add other client validators
    }
    this.updateFormValidity();
  }

  clearConditionalValidators(): void {
    this.registerForm.get('farm_name')?.clearValidators();
    this.registerForm.get('siret')?.clearValidators();
    this.registerForm.get('delivery_address')?.clearValidators();
    // Clear others as needed
  }

  updateFormValidity(): void {
      this.registerForm.get('farm_name')?.updateValueAndValidity();
      this.registerForm.get('siret')?.updateValueAndValidity();
      this.registerForm.get('delivery_address')?.updateValueAndValidity();
      // Update others
  }

  selectRole(role: 'producerForm' | 'clientForm'): void {
    this.currentStep = role;
    this.registerForm.patchValue({ role: role === 'producerForm' ? 'producteur' : 'client' });
    this.onRoleChange(); // Trigger validator changes
  }

  goBackToRoleSelection(): void {
    this.currentStep = 'roleSelection';
  }


  onSubmit(): void {
    if (this.registerForm.invalid) {
      this.errorMessage = "Veuillez corriger les erreurs du formulaire.";
      // Mark all fields as touched to display errors
      Object.values(this.registerForm.controls).forEach(control => {
        control.markAsTouched();
      });
      return;
    }
    this.isLoading = true;
    this.errorMessage = null;

    const formData = { ...this.registerForm.value };
    // Remove empty producer/client fields based on role before submitting
    if (formData.role === 'client') {
        delete formData.farm_name; delete formData.siret; // etc. for all producer fields
    } else if (formData.role === 'producteur') {
        delete formData.delivery_address; delete formData.food_preferences; // etc. for all client fields
    }


    this.authService.register(formData).subscribe({
      next: (response) => {
        this.isLoading = false;
        // Navigate to login or directly to dashboard if API auto-logins (current API does not auto-login on register)
        this.router.navigate(['/login'], { queryParams: { registrationSuccess: true } });
      },
      error: (error) => {
        this.isLoading = false;
        if (error.error && error.error.errors) { // Laravel validation errors
            const messages = Object.values(error.error.errors).flat();
            this.errorMessage = messages.join(' ');
        } else {
            this.errorMessage = error.error?.message || 'Échec de l\'inscription. Veuillez réessayer.';
        }
        console.error('Registration error:', error);
      }
    });
  }
}
