// Registrazione Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        // Usa basePath definito nei file PHP
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
    console.log('adjustQuantities: Inizio, multiplier=', document.getElementById('multiplier').value, 'originalServings=', originalServings);
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
            console.log('adjustQuantities: Aggiornato ingrediente:', originalText, '→', ingredient.textContent);
        } else {
            ingredient.textContent = originalText;
            console.log('adjustQuantities: Ingrediente non numerico:', originalText);
        }
    });

    let newServings = originalServings * multiplier;
    document.getElementById('servings').textContent = newServings % 1 === 0 ? parseInt(newServings) : newServings.toFixed(2);
    console.log('adjustQuantities: Nuove porzioni:', newServings);
}

// Prevenire doppia sottomissione dei form
function setupFormSubmission() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            form.addEventListener('submit', () => {
                submitButton.disabled = true;
                submitButton.textContent = form.id === 'delete-form' ? 'Eliminazione...' : 'Salvataggio...';
            });
        }
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
            console.log('Ricetta condivisa con successo');
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
                console.log('PWA installata');
            } else {
                console.log('Installazione PWA rifiutata');
            }
            deferredPrompt = null;
            installButton.remove();
        });
    });
    document.querySelector('.container')?.appendChild(installButton);
});