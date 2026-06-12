const nameCheck = /^[-_a-zA-Z0-9]{4,22}$/;
const tokenCheck = /^[-_/+a-zA-Z0-9]{24,}$/;

/**
 * Génère et soumet un jeton CSRF dans :
 * - un champ du formulaire ;
 * - un cookie.
 *
 * Fonctionnement basé sur SameOriginCsrfTokenManager de Symfony.
 *
 * Important :
 * Utiliser form.requestSubmit() afin de déclencher l'événement submit.
 * form.submit() ne déclenche pas cet événement et ce listener ne sera donc pas exécuté.
 */
document.addEventListener('submit', function (event) {
    generateCsrfToken(event.target);
}, true);

/**
 * Lorsque Turbo gère l'envoi du formulaire,
 * le jeton CSRF est également envoyé dans les en-têtes HTTP.
 *
 * Nécessite l'activation de :
 * framework.csrf_protection.check_header
 * dans la configuration Symfony.
 */
document.addEventListener('turbo:submit-start', function (event) {
    const h = generateCsrfHeaders(event.detail.formSubmission.formElement);

    Object.keys(h).map(function (k) {
        event.detail.formSubmission.fetchRequest.headers[k] = h[k];
    });
});

/**
 * Une fois le formulaire soumis via Turbo,
 * le cookie CSRF est supprimé.
 */
document.addEventListener('turbo:submit-end', function (event) {
    removeCsrfToken(event.detail.formSubmission.formElement);
});

/**
 * Génère un jeton CSRF et crée le cookie associé.
 *
 * @param {HTMLFormElement} formElement
 */
export function generateCsrfToken(formElement) {
    const csrfField = formElement.querySelector(
        'input[data-controller="csrf-protection"], input[name="_csrf_token"]'
    );

    if (!csrfField) {
        return;
    }

    let csrfCookie = csrfField.getAttribute(
        'data-csrf-protection-cookie-value'
    );

    let csrfToken = csrfField.value;

    /**
     * Lors du premier chargement :
     * - le nom du cookie est conservé ;
     * - un nouveau jeton CSRF aléatoire est généré.
     */
    if (!csrfCookie && nameCheck.test(csrfToken)) {
        csrfField.setAttribute(
            'data-csrf-protection-cookie-value',
            csrfCookie = csrfToken
        );

        csrfField.defaultValue = csrfToken = btoa(
            String.fromCharCode.apply(
                null,
                (window.crypto || window.msCrypto)
                    .getRandomValues(new Uint8Array(18))
            )
        );
    }

    csrfField.dispatchEvent(
        new Event('change', { bubbles: true })
    );

    /**
     * Création du cookie CSRF.
     *
     * En HTTPS :
     * - préfixe __Host-
     * - Secure activé
     */
    if (csrfCookie && tokenCheck.test(csrfToken)) {
        const cookie =
            csrfCookie +
            '_' +
            csrfToken +
            '=' +
            csrfCookie +
            '; path=/; samesite=strict';

        document.cookie =
            window.location.protocol === 'https:'
                ? '__Host-' + cookie + '; secure'
                : cookie;
    }
}

/**
 * Génère les en-têtes HTTP contenant le jeton CSRF.
 *
 * Utilisé principalement par Turbo.
 *
 * @param {HTMLFormElement} formElement
 * @returns {Object}
 */
export function generateCsrfHeaders(formElement) {
    const headers = {};

    const csrfField = formElement.querySelector(
        'input[data-controller="csrf-protection"], input[name="_csrf_token"]'
    );

    if (!csrfField) {
        return headers;
    }

    const csrfCookie = csrfField.getAttribute(
        'data-csrf-protection-cookie-value'
    );

    // Ajout du jeton dans les en-têtes HTTP.
    if (tokenCheck.test(csrfField.value) && nameCheck.test(csrfCookie)) {
        headers[csrfCookie] = csrfField.value;
    }

    return headers;
}

/**
 * Supprime le cookie CSRF après soumission du formulaire.
 *
 * @param {HTMLFormElement} formElement
 */
export function removeCsrfToken(formElement) {
    const csrfField = formElement.querySelector(
        'input[data-controller="csrf-protection"], input[name="_csrf_token"]'
    );

    if (!csrfField) {
        return;
    }

    const csrfCookie = csrfField.getAttribute(
        'data-csrf-protection-cookie-value'
    );

    /**
     * Suppression du cookie :
     * max-age=0 indique au navigateur
     * d'effacer immédiatement le cookie.
     */
    if (tokenCheck.test(csrfField.value) && nameCheck.test(csrfCookie)) {
        const cookie =
            csrfCookie +
            '_' +
            csrfField.value +
            '=0; path=/; samesite=strict; max-age=0';

        document.cookie =
            window.location.protocol === 'https:'
                ? '__Host-' + cookie + '; secure'
                : cookie;
    }
}

// Chargement différé du contrôleur Stimulus.
export default 'csrf-protection-controller';
