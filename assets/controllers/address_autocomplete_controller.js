import { Controller } from "@hotwired/stimulus";
import { APILoader } from 'https://unpkg.com/@googlemaps/extended-component-library@0.6';

// Stimulus controller pour Google Autocomplete
export default class extends Controller {
    static targets = ["autocomplete", "city", "postalCode", "adress", "additionalFields"];

    connect() {
        this.autocompleteInstance = null;
        this.initialized = false;
        // Au départ : désactive & enlève required sur city/CP
        this.disableAdditionalFields();
    }

    async initializeAutocomplete() {
        if (this.initialized) return;
        this.initialized = true;

        const { Autocomplete } = await APILoader.importLibrary('places');
        this.autocompleteInstance = new Autocomplete(this.autocompleteTarget, {
            fields: ['address_components', 'geometry', 'name'],
            types: ['address'],
        });

        this.autocompleteInstance.addListener('place_changed', () => {
            const place = this.autocompleteInstance.getPlace();
            if (!place.geometry) return;
            this.fillInAddress(place);
        });
    }

    fillInAddress(place) {
        function getComponent(type) {
            for (const comp of place.address_components || []) {
                if (comp.types.includes(type)) {
                    return comp.long_name || comp.short_name || '';
                }
            }
            return '';
        }
        const street = [getComponent('street_number'), getComponent('route')].filter(Boolean).join(' ');
        // Utilise un fallback pour la ville si 'locality' absent
        const city = getComponent('locality') || getComponent('postal_town') || getComponent('administrative_area_level_2') || getComponent('administrative_area_level_1');
        const postalCode = getComponent('postal_code');

        // Synchronise les champs
        this.autocompleteTarget.value = street;
        this.adressTarget.value = street;
        this.cityTarget.value = city;
        this.postalCodeTarget.value = postalCode;

        // Débloque les champs ville/code postal, les rend obligatoires, affiche la zone
        this.cityTarget.disabled = false;
        this.postalCodeTarget.disabled = false;
        this.cityTarget.required = true;
        this.postalCodeTarget.required = true;
        this.additionalFieldsTarget.style.display = 'block';

        // Déclencheur pour la validation native HTML5/Symfony
        this.adressTarget.dispatchEvent(new Event('input', { bubbles: true }));
        this.cityTarget.dispatchEvent(new Event('input', { bubbles: true }));
        this.postalCodeTarget.dispatchEvent(new Event('input', { bubbles: true }));
    }

    focus() {
        this.initializeAutocomplete();
    }

    reset(event) {
        if (event) event.preventDefault();
        this.autocompleteTarget.value = "";
        this.adressTarget.value = "";
        this.cityTarget.value = "";
        this.postalCodeTarget.value = "";
        this.disableAdditionalFields();
        this.additionalFieldsTarget.style.display = 'none';
        this.initialized = false;
    }

    disableAdditionalFields() {
        this.cityTarget.disabled = true;
        this.postalCodeTarget.disabled = true;
        this.cityTarget.required = false;
        this.postalCodeTarget.required = false;
    }
}