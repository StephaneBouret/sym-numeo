import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = [
        "form",
        "firstNames",
        "lastName",
        "result",
        "status",
        "submitBtn",
        "aspirationValue",
        "expressionValue",
        "axeValue",
        "vigilanceValue",
        "aspirationText",
        "expressionText",
        "pairText",
        "narrativeCard",
    ];
    static values = { endpoint: String };

    normalize() {
        this.firstNamesTarget.value = this.normalizeIdentity(
            this.firstNamesTarget.value,
            false,
        );
        this.lastNameTarget.value = this.normalizeIdentity(
            this.lastNameTarget.value,
            true,
        );
    }

    async submit(event) {
        event.preventDefault();
        this.normalize();

        const formData = new FormData(this.formTarget);
        const birthDate = formData.get("cap[birthDate]");

        const payload = {
            firstNames: this.firstNamesTarget.value,
            lastName: this.lastNameTarget.value,
            birthDate: birthDate,
        };

        this.setStatus("Calcul en cours…");
        this.submitBtnTarget.disabled = true;

        try {
            const res = await fetch(this.endpointValue, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            });

            const json = await res.json();

            if (!res.ok || !json.ok) {
                this.setStatus(json.error ?? "Erreur de calcul.");
                return;
            }

            this.renderResult(json.data, json.content);
            this.setStatus("");
        } catch (e) {
            console.error(e);
            this.setStatus("Impossible de contacter le serveur.");
        } finally {
            this.submitBtnTarget.disabled = false;
        }
    }

    renderResult(data, content) {
        // MVP : rendu simple (on passera au losange ensuite)
        // this.resultTarget.innerHTML = `
        //   <div class="d-flex flex-column gap-2">
        //     <div><strong>Chemin/Aspiration :</strong> ${data.aspiration}</div>
        //     <div><strong>Expression :</strong> ${data.expression}</div>
        //     <div><strong>Axe :</strong> ${data.axe}</div>
        //     <div><strong>Point de vigilance :</strong> ${data.vigilance}</div>
        //   </div>
        // `;
        // NOMBRES
        this.aspirationValueTarget.textContent = data.aspiration ?? "—";
        this.expressionValueTarget.textContent = data.expression ?? "—";
        this.axeValueTarget.textContent = data.axe ?? "—";
        this.vigilanceValueTarget.textContent = data.vigilance ?? "—";

        // ASPIRATION (texte seul)
        this.aspirationTextTarget.textContent =
            content?.aspiration ?? "Contenu en cours d'enrichissement.";

        // EXPRESSION (texte seul)
        this.expressionTextTarget.textContent =
            content?.expression ?? "Contenu en cours d'enrichissement.";

        // PAIRE Aspiration/Expression
        const pair = content?.pair ?? null;

        if (
            pair?.exists &&
            Array.isArray(pair.paragraphs) &&
            pair.paragraphs.length > 0
        ) {
            this.pairTextTarget.innerHTML = pair.paragraphs
                .map((p) => `<p class="mb-2">${this.escapeHtml(p)}</p>`)
                .join("");
        } else {
            this.pairTextTarget.innerHTML = '<em>Lecture relationnelle en cours d\'enrichissement.</em>';
        }

        if (this.hasNarrativeCardTarget) {
            this.narrativeCardTarget.classList.remove("d-none");
        }
    }

    setStatus(msg) {
        this.statusTarget.textContent = msg;
    }

    normalizeIdentity(value, isLastName) {
        let v = (value ?? "").trim();

        // accents → ASCII
        v = v.normalize("NFD").replace(/[\u0300-\u036f]/g, "");

        // autorise lettres, espaces, tirets, apostrophes (selon ton choix)
        v = v.replace(/[^A-Za-z \-']/g, " ");

        // espaces propres
        v = v.replace(/\s+/g, " ").trim();

        // nom : souvent tout en MAJ
        v = isLastName ? v.toUpperCase() : this.titleish(v);

        return v;
    }

    titleish(v) {
        return v
            .split(" ")
            .map((part) =>
                part
                    .split("-")
                    .map((p) =>
                        p ? p[0].toUpperCase() + p.slice(1).toLowerCase() : "",
                    )
                    .join("-"),
            )
            .join(" ");
    }

    escapeHtml(str) {
        const div = document.createElement("div");
        div.textContent = String(str);
        return div.innerHTML;
    }
}
