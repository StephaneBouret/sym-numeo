import { startStimulusApp } from '@symfony/stimulus-bundle';
import ThemeController from './controllers/theme_controller.js';
import CapController from './controllers/cap_controller.js';
import ResetPassword from './controllers/reset_password_controller.js';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
app.register('theme', ThemeController);
app.register('cap', CapController);
app.register('reset-password', ResetPassword);
