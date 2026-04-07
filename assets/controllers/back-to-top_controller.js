import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        this.toggleVisibility = this.toggleVisibility.bind(this);

        window.addEventListener("scroll", this.toggleVisibility);
        window.addEventListener("load", this.toggleVisibility);

        this.toggleVisibility();
    }

    disconnect() {
        window.removeEventListener("scroll", this.toggleVisibility);
        window.removeEventListener("load", this.toggleVisibility);
    }

    toggleVisibility() {
        this.element.classList.toggle("active", window.scrollY > 100);
    }

    scrollToTop(event) {
        event.preventDefault();

        window.scrollTo({
            top: 0,
            behavior: "smooth",
        });
    }
}
