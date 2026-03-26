import { Controller } from "@hotwired/stimulus";
import {
  evaluatePasswordStrength,
  updateEntropy,
  bindPasswordGenerator
} from "../modules/passwordUtils.js";

export default class extends Controller {
  static targets = ["entropy", "submit", "generate"]; // <- on retire "password"

  connect() {
    this.onInput = this.onInput.bind(this);

    // On récupère l'input password même si UX TogglePassword l'a recréé
    // On veut dans credentials, celui du nouveau mot de pass (proche de l'entropy)
    if (this.hasEntropyTarget) {
        const container = this.entropyTarget.closest('.w-100') ?? this.element;
        this.passwordEl = container.querySelector('input[type="password"]');
    } else {
        this.passwordEl = this.element.querySelector('input[type="password"]');
    }

    if (!this.passwordEl) {
      // On évite de planter la page
      return;
    }

    this.passwordEl.addEventListener("input", this.onInput);

    if (this.hasGenerateTarget) {
      bindPasswordGenerator(this.generateTarget, this.passwordEl);
    }

    this.onInput();
  }

  disconnect() {
    if (this.passwordEl) {
      this.passwordEl.removeEventListener("input", this.onInput);
    }
  }

  onInput() {
    const mdp = this.passwordEl?.value ?? "";
    const entropy = evaluatePasswordStrength(mdp);

    const passOk = this.hasEntropyTarget
      ? updateEntropy(this.entropyTarget, entropy)
      : entropy >= 60;

    if (this.hasSubmitTarget) {
      if (passOk) this.submitTarget.removeAttribute("disabled");
      else this.submitTarget.setAttribute("disabled", "disabled");
    }
  }
}
