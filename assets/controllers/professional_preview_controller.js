import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'professionalNameInput',
        'legalNameInput',
        'siretInput',
        'emailInput',
        'phoneInput',
        'addressInput',
        'postalCodeInput',
        'cityInput',
        'websiteUrlInput',
        'nameOutput',
        'legalNameOutput',
        'siretOutput',
        'emailOutput',
        'phoneOutput',
        'addressOutput',
        'postalCodeOutput',
        'cityOutput',
        'websiteUrlOutput',
        'legalNameRow',
        'siretRow',
        'emailRow',
        'phoneRow',
        'addressRow',
        'postalCodeRow',
        'cityRow',
        'websiteUrlRow',
        'logoInput',
        'logoImage',
        'logoPlaceholder',
        'logoFileName',
        'dropzone',
        'deleteLogoSection',
        'deleteLogoButton',
        'deleteLogoStatus',
    ];

    static values = {
        nameFallback: String,
        legalNameFallback: String,
        siretFallback: String,
        emailFallback: String,
        phoneFallback: String,
        addressFallback: String,
        postalCodeFallback: String,
        cityFallback: String,
        websiteUrlFallback: String,
        logo: String,
        deleteLogoUrl: String,
        deleteLogoToken: String,
    };

    connect() {
        this.objectUrl = null;
        this.update();
        this.renderExistingLogo();
    }

    disconnect() {
        this.revokeObjectUrl();
    }

    update() {
        const name = this.valueOrFallback(
            this.professionalNameInputTarget,
            this.nameFallbackValue,
        );
        const legalName = this.valueOrFallback(
            this.legalNameInputTarget,
            this.legalNameFallbackValue,
        );
        const siret = this.valueOrFallback(
            this.siretInputTarget,
            this.siretFallbackValue,
        );
        const email = this.valueOrFallback(
            this.emailInputTarget,
            this.emailFallbackValue,
        );
        const phone = this.valueOrFallback(
            this.phoneInputTarget,
            this.phoneFallbackValue,
        );
        const address = this.valueOrFallback(
            this.addressInputTarget,
            this.addressFallbackValue,
        );
        const postalCode = this.valueOrFallback(
            this.postalCodeInputTarget,
            this.postalCodeFallbackValue,
        );
        const city = this.valueOrFallback(
            this.cityInputTarget,
            this.cityFallbackValue,
        );
        const websiteUrl = this.valueOrFallback(
            this.websiteUrlInputTarget,
            this.websiteUrlFallbackValue,
        );

        this.nameOutputTarget.textContent = name;

        this.setOptionalValue(
            this.legalNameOutputTarget,
            this.legalNameRowTarget,
            legalName,
        );
        this.setOptionalValue(
            this.siretOutputTarget,
            this.siretRowTarget,
            siret,
        );
        this.setOptionalValue(
            this.emailOutputTarget,
            this.emailRowTarget,
            email,
        );
        this.setOptionalValue(
            this.phoneOutputTarget,
            this.phoneRowTarget,
            phone,
        );
        this.setOptionalValue(
            this.addressOutputTarget,
            this.addressRowTarget,
            address,
        );
        this.setOptionalValue(
            this.postalCodeOutputTarget,
            this.postalCodeRowTarget,
            postalCode,
        );
        this.setOptionalValue(
            this.cityOutputTarget,
            this.cityRowTarget,
            city,
        );
        this.setOptionalValue(
            this.websiteUrlOutputTarget,
            this.websiteUrlRowTarget,
            websiteUrl,
        );
    }

    changeLogo() {
        this.revokeObjectUrl();

        const file = this.logoInputTarget.files?.item(0);

        if (!file) {
            this.renderExistingLogo();

            return;
        }

        this.objectUrl = URL.createObjectURL(file);
        this.logoImageTarget.setAttribute('src', this.objectUrl);
        this.logoImageTarget.hidden = false;
        this.logoPlaceholderTarget.hidden = true;
        this.logoFileNameTarget.textContent = file.name;
    }

    dragOver(event) {
        event.preventDefault();

        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'copy';
        }

        this.dropzoneTarget.classList.add('is-dragging');
    }

    dragLeave(event) {
        if (
            event.relatedTarget instanceof Node
            && event.currentTarget.contains(event.relatedTarget)
        ) {
            return;
        }

        this.dropzoneTarget.classList.remove('is-dragging');
    }

    dropLogo(event) {
        event.preventDefault();
        this.dropzoneTarget.classList.remove('is-dragging');

        const file = event.dataTransfer?.files?.item(0);

        if (!file) {
            return;
        }

        const transfer = new DataTransfer();
        transfer.items.add(file);
        this.logoInputTarget.files = transfer.files;

        this.changeLogo();
    }

    async deleteLogo() {
        if (this.deleteLogoButtonTarget.disabled) {
            return;
        }

        if (!window.confirm(
            'Supprimer définitivement le logo professionnel actuel ?'
        )) {
            return;
        }

        this.deleteLogoButtonTarget.disabled = true;
        this.setDeleteLogoStatus(
            'Suppression du logo en cours…',
            'pending',
        );

        try {
            const response = await fetch(this.deleteLogoUrlValue, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.deleteLogoTokenValue,
                },
            });

            const payload = await this.parseJsonResponse(response);
            const message = typeof payload?.message === 'string'
                ? payload.message
                : null;

            if (!response.ok || payload?.success !== true) {
                throw new Error(
                    message ?? 'Le logo n\'a pas pu être supprimé.'
                );
            }

            this.clearLogoSelection();
            this.logoValue = '';
            this.renderExistingLogo();
            this.deleteLogoSectionTarget.hidden = true;

            this.setDeleteLogoStatus(
                message ?? 'Le logo professionnel a bien été supprimé.',
                'success',
            );
        } catch (error) {
            const message = error instanceof Error
                ? error.message
                : 'Le logo n\'a pas pu être supprimé.';

            this.deleteLogoButtonTarget.disabled = false;
            this.setDeleteLogoStatus(message, 'error');
        }
    }

    valueOrFallback(input, fallback) {
        const value = input.value.trim();

        return value || fallback.trim();
    }

    setOptionalValue(output, row, value) {
        output.textContent = value;
        row.hidden = value.length === 0;
    }

    renderExistingLogo() {
        const existingLogo = this.logoValue.trim();

        if (existingLogo) {
            this.logoImageTarget.setAttribute('src', existingLogo);
            this.logoImageTarget.hidden = false;
            this.logoPlaceholderTarget.hidden = true;
            this.logoFileNameTarget.textContent =
                'Logo actuel conservé tant qu\'aucun nouveau fichier n\'est choisi.';

            return;
        }

        this.logoImageTarget.removeAttribute('src');
        this.logoImageTarget.hidden = true;
        this.logoPlaceholderTarget.hidden = false;
        this.logoFileNameTarget.textContent = 'Aucun fichier sélectionné.';
    }

    clearLogoSelection() {
        this.revokeObjectUrl();
        this.logoInputTarget.value = '';
        this.dropzoneTarget.classList.remove('is-dragging');
    }

    async parseJsonResponse(response) {
        const contentType = response.headers.get('content-type') ?? '';

        if (!contentType.includes('application/json')) {
            return null;
        }

        try {
            return await response.json();
        } catch {
            return null;
        }
    }

    setDeleteLogoStatus(message, type) {
        const status = this.deleteLogoStatusTarget;

        status.textContent = message;
        status.hidden = false;
        status.classList.remove(
            'text-muted',
            'text-success',
            'text-danger',
        );
        status.classList.add(
            type === 'success'
                ? 'text-success'
                : type === 'error'
                    ? 'text-danger'
                    : 'text-muted',
        );
        status.setAttribute(
            'role',
            type === 'error' ? 'alert' : 'status',
        );
        status.setAttribute(
            'aria-live',
            type === 'error' ? 'assertive' : 'polite',
        );
    }

    revokeObjectUrl() {
        if (!this.objectUrl) {
            return;
        }

        URL.revokeObjectURL(this.objectUrl);
        this.objectUrl = null;
    }
}
