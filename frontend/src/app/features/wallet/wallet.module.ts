import { NgModule } from '@angular/core';
import { CommonModule, CurrencyPipe, DatePipe, TitleCasePipe } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { AuthGuard } from '../../core/guards/auth.guard';

import { WalletViewComponent } from './wallet-view/wallet-view.component';

const walletRoutes: Routes = [
  {
    path: 'portefeuille', // ou 'mon-portefeuille'
    component: WalletViewComponent,
    canActivate: [AuthGuard] // Nécessite d'être connecté
  }
];

@NgModule({
  declarations: [
    WalletViewComponent
  ],
  imports: [
    CommonModule,
    RouterModule.forChild(walletRoutes)
  ],
  providers: [ // Fournir les pipes ici si WalletModule n'est pas importé dans un module qui les a déjà
    CurrencyPipe,
    DatePipe,
    TitleCasePipe
  ]
})
export class WalletModule { }
