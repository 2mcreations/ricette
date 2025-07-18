const DEBUG = false;
// Registrazione Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        const swPath = (window.basePath || '') + 'sw.js';
        navigator.serviceWorker.register(swPath).then(registration => {
            // Forza aggiornamento
            registration.update().catch(error => {
                console.error('Errore aggiornamento service worker:', error);
            });
        }).catch(error => {
            console.error('Service Worker: Errore registrazione', error);
        });
    });
}

// Moltiplicatore nelle ricette
function adjustQuantities(originalServings) {
    if (DEBUG) console.log('adjustQuantities: Inizio, multiplier=', document.getElementById('multiplier').value, 'originalServings=', originalServings);
    const multiplier = parseFloat(document.getElementById('multiplier').value) || 1;
    const ingredients = document.querySelectorAll('#ingredients-list li');

    ingredients.forEach(ingredient => {
        let originalText = ingredient.getAttribute('data-original');
        let match = originalText.match(/^(\d*\.?\d*)\s*(.*)$/);
        if (match && match[1]) {
            let quantity = parseFloat(match[1]);
            let unitAndRest = match[2];
            let newQuantity = quantity * multiplier;
            let formattedQuantity = newQuantity % 1 === 0 ? parseInt(newQuantity) : newQuantity.toFixed(2);
            ingredient.textContent = `${formattedQuantity} ${unitAndRest}`;
            if (DEBUG) console.log('adjustQuantities: Aggiornato ingrediente:', originalText, '→', ingredient.textContent);
        } else {
            ingredient.textContent = originalText;
            if (DEBUG) console.log('adjustQuantities: Ingrediente non numerico:', originalText);
        }
    });

    let newServings = originalServings * multiplier;
    document.getElementById('servings').textContent = newServings % 1 === 0 ? parseInt(newServings) : newServings.toFixed(2);
    if (DEBUG) console.log('adjustQuantities: Nuove porzioni:', newServings);
}

function setupMultiplierButtons() {
    const minusBtn = document.getElementById('minus-btn');
    const plusBtn = document.getElementById('plus-btn');
    const multiplierInput = document.getElementById('multiplier');

    if (minusBtn && plusBtn && multiplierInput) {
        minusBtn.addEventListener('click', () => {
            let currentValue = parseInt(multiplierInput.value);
            if (currentValue > 1) {
                multiplierInput.value = currentValue - 1;
                // Trigger the 'input' event to update quantities
                multiplierInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });

        plusBtn.addEventListener('click', () => {
            let currentValue = parseInt(multiplierInput.value);
            multiplierInput.value = currentValue + 1;
            // Trigger the 'input' event to update quantities
            multiplierInput.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }
}

// Prevenire doppia sottomissione dei form
function setupFormSubmission() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', event => {
            const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                const loadingText = submitButton.getAttribute('data-loading-text');
                if (loadingText) submitButton.innerText = loadingText;
            }
        });
    });
}

// Conferma eliminazione
function confirmDelete(event) {
    if (!confirm('Sei sicuro di voler eliminare questa ricetta? Questa azione è irreversibile.')) {
        event.preventDefault();
    }
}

// Gestione pulsante di condivisione
function setupShareButton() {
    const shareButton = document.getElementById('share-recipe');
    if (shareButton) {
        shareButton.addEventListener('click', () => {
            shareRecipe();
        });
    }
}

// Condivisione ricetta
function shareRecipe() {
    const title = document.querySelector('h1')?.textContent || 'Ricetta';
    const url = window.location.href;
    const shareData = {
        title: title,
        url: url
    };

    if (navigator.share) {
        navigator.share(shareData).then(() => {
            if (DEBUG) console.log('Ricetta condivisa con successo');
        }).catch(error => {
            console.error('Errore durante la condivisione:', error);
        });
    } else {
        // Fallback: copia il link negli appunti
        navigator.clipboard.writeText(url).then(() => {
            const fallbackSpan = document.getElementById('share-fallback');
            if (fallbackSpan) {
                fallbackSpan.textContent = 'Link copiato negli appunti!';
                fallbackSpan.classList.remove('d-none');
                setTimeout(() => {
                    fallbackSpan.classList.add('d-none');
                    fallbackSpan.textContent = '';
                }, 3000);
            }
        }).catch(error => {
            console.error('Errore copia link:', error);
            const fallbackSpan = document.getElementById('share-fallback');
            if (fallbackSpan) {
                fallbackSpan.textContent = 'Errore durante la copia del link.';
                fallbackSpan.classList.remove('d-none');
            }
        });
    }
}

// Gestione installazione PWA
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    const installButton = document.createElement('button');
    installButton.className = 'btn btn-secondary mt-3';
    installButton.textContent = 'Installa Ricettario';
    installButton.addEventListener('click', () => {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                if (DEBUG) console.log('PWA installata');
            } else {
                if (DEBUG) console.log('Installazione PWA rifiutata');
            }
            deferredPrompt = null;
            installButton.remove();
        });
    });
    document.querySelector('.container')?.appendChild(installButton);
});

// Inizializzazione al caricamento
window.addEventListener('load', () => {
    if (DEBUG) console.log('Window load: Inizializzazione');
    setupFormSubmission();
    setupShareButton();
    setupMultiplierButtons();
});