import { startStimulusApp } from '@symfony/stimulus-bundle';
import ThemeController from './controllers/theme_controller.js';
import CapController from './controllers/cap_controller.js';
import ResetPassword from './controllers/reset_password_controller.js';
import BackToTopController from './controllers/back-to-top_controller.js';
import SubscriptionPaymentStatusController from './controllers/subscription_payment_status_controller.js';
import RegisterController from './controllers/register_controller.js';
import AddressAutocompleteController from './controllers/address_autocomplete_controller.js';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
app.register('theme', ThemeController);
app.register('cap', CapController);
app.register('reset-password', ResetPassword);
app.register('back-to-top', BackToTopController);
app.register('subscription-payment-status', SubscriptionPaymentStatusController);
app.register('register', RegisterController);
app.register('address-autocomplete', AddressAutocompleteController);
