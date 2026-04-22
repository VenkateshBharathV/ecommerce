document.addEventListener('DOMContentLoaded', function () {
    setupSmartSearch();
    setupChatbot();
    setupCancelOrder();
});

function setupSmartSearch() {
    const searchRoot = document.querySelector('[data-smart-search]');
    if (!searchRoot) {
        return;
    }

    const form = searchRoot.querySelector('form');
    const input = searchRoot.querySelector('input[name="search"]');
    const suggestionBox = searchRoot.querySelector('[data-search-suggestions]');
    const resultTarget = document.querySelector('[data-search-results]');
    const summaryTarget = document.querySelector('[data-search-summary]');
    const micButton = searchRoot.querySelector('[data-voice-search]');
    let debounceTimer = null;

    function hideSuggestions() {
        if (!suggestionBox) {
            return;
        }

        suggestionBox.hidden = true;
        suggestionBox.innerHTML = '';
    }

    function renderSuggestions(suggestions) {
        if (!suggestionBox) {
            return;
        }

        if (!suggestions.length) {
            hideSuggestions();
            return;
        }

        suggestionBox.innerHTML = suggestions.map(function (item) {
            return '<a class="ai-search__suggestion" href="index.php?search=' + encodeURIComponent(item.name) + '">' +
                '<strong>' + escapeHtml(item.name) + '</strong>' +
                '<small>' + escapeHtml(item.category || 'Product') + ' • Rs ' + Number(item.price).toLocaleString() + '</small>' +
                '</a>';
        }).join('');
        suggestionBox.hidden = false;
    }

    input?.addEventListener('input', function () {
        const query = this.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < 2) {
            hideSuggestions();
            return;
        }

        debounceTimer = setTimeout(function () {
            fetch('search.php?mode=suggest&q=' + encodeURIComponent(query))
                .then(function (response) { return response.json(); })
                .then(function (data) { renderSuggestions(data.suggestions || []); })
                .catch(hideSuggestions);
        }, 180);
    });

    document.addEventListener('click', function (event) {
        if (!searchRoot.contains(event.target)) {
            hideSuggestions();
        }
    });

    form?.addEventListener('submit', function (event) {
        if (!resultTarget || !input.value.trim()) {
            return;
        }

        event.preventDefault();
        performLiveSearch(input.value.trim(), resultTarget, summaryTarget);
    });

    micButton?.addEventListener('click', function () {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (!SpeechRecognition) {
            showInlineToast('Voice search is not supported in this browser.', 'error');
            return;
        }

        const recognition = new SpeechRecognition();
        recognition.lang = 'en-IN';
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;

        micButton.textContent = '...';
        recognition.start();

        recognition.onresult = function (event) {
            const transcript = event.results[0][0].transcript || '';
            input.value = transcript;

            if (resultTarget) {
                performLiveSearch(transcript, resultTarget, summaryTarget);
            } else {
                form.submit();
            }
        };

        recognition.onerror = function () {
            micButton.innerHTML = '&#127908;';
        };

        recognition.onend = function () {
            micButton.innerHTML = '&#127908;';
        };
    });
}

function performLiveSearch(query, resultTarget, summaryTarget) {
    fetch('search.php?mode=search&q=' + encodeURIComponent(query))
        .then(function (response) { return response.json(); })
        .then(function (data) {
            resultTarget.innerHTML = data.html || '<p>No products found.</p>';

            if (!summaryTarget) {
                return;
            }

            const intent = data.intent || {};
            const parts = [];

            if (intent.category) {
                parts.push('Category: ' + intent.category);
            }
            if (intent.max_price) {
                parts.push('Under Rs ' + Number(intent.max_price).toLocaleString());
            }
            if (intent.min_price) {
                parts.push('Above Rs ' + Number(intent.min_price).toLocaleString());
            }

            summaryTarget.textContent = data.count + ' result(s)' + (parts.length ? ' • ' + parts.join(' • ') : '');
        })
        .catch(function () {
            if (summaryTarget) {
                summaryTarget.textContent = 'Search is temporarily unavailable.';
            }
        });
}

function setupChatbot() {
    const root = document.querySelector('[data-ai-chatbot]');
    if (!root) {
        return;
    }

    const toggle = root.querySelector('[data-chat-toggle]');
    const close = root.querySelector('[data-chat-close]');
    const panel = root.querySelector('[data-chat-panel]');
    const form = root.querySelector('[data-chat-form]');
    const messages = root.querySelector('[data-chat-messages]');
    const typingIndicator = root.querySelector('[data-chat-typing]');
    const quickButtons = root.querySelectorAll('[data-chat-prompt]');
    const input = form?.querySelector('input[name="message"]');
    const endpoint = root.getAttribute('data-chat-endpoint') || '../ai_chat.php';

    function scrollMessages() {
        messages.scrollTop = messages.scrollHeight;
    }

    function appendMessage(text, type) {
        const message = document.createElement('div');
        message.className = 'ai-chatbot__message ai-chatbot__message--' + type + ' ai-fade-in';
        message.textContent = text;
        messages.appendChild(message);
        scrollMessages();
    }

    function appendProducts(products) {
        if (!products || !products.length) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'ai-chatbot__card-grid ai-fade-in';

        products.forEach(function (product) {
            const card = document.createElement('article');
            card.className = 'ai-chatbot__product-card' + (product.highlight ? ' is-highlighted' : '');
            card.innerHTML = '' +
                '<div class="ai-chatbot__product-media">' +
                    '<img src="' + escapeHtml(product.image || '') + '" alt="' + escapeHtml(product.name || 'Product') + '">' +
                '</div>' +
                '<div class="ai-chatbot__product-body">' +
                    '<span class="ai-chatbot__product-tag">' + escapeHtml(product.category || 'Store') + '</span>' +
                    '<h4>' + escapeHtml(product.name || 'Product') + '</h4>' +
                    '<p>' + escapeHtml(trimDescription(product.description || 'No description available.')) + '</p>' +
                    '<strong>Rs ' + Number(product.price || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 }) + '</strong>' +
                '</div>';
            wrapper.appendChild(card);
        });

        messages.appendChild(wrapper);
        scrollMessages();
    }

    function openPanel() {
        panel.hidden = false;
        requestAnimationFrame(function () {
            panel.classList.add('ai-chatbot__panel--open');
            toggle.classList.add('ai-chatbot__toggle--hidden');
        });
    }

    function closePanel() {
        panel.classList.remove('ai-chatbot__panel--open');
        panel.classList.add('ai-chatbot__panel--closing');

        window.setTimeout(function () {
            panel.hidden = true;
            panel.classList.remove('ai-chatbot__panel--closing');
            toggle.classList.remove('ai-chatbot__toggle--hidden');
        }, 260);
    }

    function setTypingState(active) {
        if (!typingIndicator) {
            return;
        }

        typingIndicator.hidden = !active;
        scrollMessages();
    }

    function sendPrompt(text) {
        if (!text) {
            return;
        }

        appendMessage(text, 'user');
        setTypingState(true);

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: text }),
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                setTypingState(false);
                appendMessage(data.reply || 'I could not answer that right now.', 'bot');
                appendProducts(data.products || []);

                if (data.meta && data.meta.track_url) {
                    const link = document.createElement('a');
                    link.className = 'ai-chatbot__track-link ai-fade-in';
                    link.href = data.meta.track_url;
                    link.textContent = 'Open tracking page';
                    messages.appendChild(link);
                    scrollMessages();
                }
            })
            .catch(function () {
                setTypingState(false);
                appendMessage('The assistant is temporarily unavailable.', 'bot');
            });
    }

    toggle?.addEventListener('click', openPanel);
    close?.addEventListener('click', closePanel);

    quickButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const prompt = button.getAttribute('data-chat-prompt') || '';
            sendPrompt(prompt);
        });
    });

    form?.addEventListener('submit', function (event) {
        event.preventDefault();
        const text = input.value.trim();

        if (!text) {
            return;
        }

        input.value = '';
        sendPrompt(text);
    });

    if (window.innerWidth <= 640) {
        closePanel();
    } else {
        openPanel();
    }
}

function setupCancelOrder() {
    const buttons = document.querySelectorAll('.cancel-btn[data-id]');
    if (!buttons.length) {
        return;
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (button.disabled || button.classList.contains('is-loading')) {
                return;
            }

            const orderId = button.getAttribute('data-id');
            const endpoint = button.getAttribute('data-endpoint') || '../cancel_order.php';

            if (!window.confirm('Are you sure you want to cancel this order?')) {
                return;
            }

            const card = button.closest('.order-card');
            const statusBadge = card?.querySelector('[data-order-status]');
            const statusText = card?.querySelector('[data-order-status-text]');
            const feedback = card?.querySelector('[data-cancel-feedback]');
            const buttonLabel = button.querySelector('[data-cancel-label]') || button;

            button.classList.add('is-loading');
            button.disabled = true;
            buttonLabel.textContent = 'Cancelling...';

            const body = new FormData();
            body.append('order_id', orderId);

            fetch(endpoint, {
                method: 'POST',
                body: body,
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.status !== 'success') {
                        throw new Error(data.message || 'Cancellation failed.');
                    }

                    if (statusText) {
                        statusText.textContent = 'Cancelled';
                    }
                    if (statusBadge) {
                        statusBadge.style.background = '#7d8597';
                        statusBadge.classList.add('is-cancelled');
                    }
                    if (feedback) {
                        feedback.textContent = data.message || 'Order cancelled successfully.';
                    }

                    button.classList.remove('is-loading');
                    button.classList.add('is-cancelled');
                    buttonLabel.textContent = 'Cancelled';
                    showInlineToast(data.message || 'Order cancelled successfully.', 'success');
                })
                .catch(function (error) {
                    button.classList.remove('is-loading');
                    button.disabled = false;
                    buttonLabel.textContent = 'Cancel Order';

                    if (feedback) {
                        feedback.textContent = error.message || 'Unable to cancel the order.';
                    }

                    showInlineToast(error.message || 'Unable to cancel the order.', 'error');
                });
        });
    });
}

function trimDescription(text) {
    const clean = String(text || '').trim();
    return clean.length > 90 ? clean.slice(0, 87) + '...' : clean;
}

function showInlineToast(message, type) {
    const existing = document.querySelector('.ai-inline-toast');
    if (existing) {
        existing.remove();
    }

    const toast = document.createElement('div');
    toast.className = 'ai-inline-toast ai-inline-toast--' + (type || 'success');
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(function () {
        toast.classList.add('is-visible');
    });

    window.setTimeout(function () {
        toast.classList.remove('is-visible');
        window.setTimeout(function () {
            toast.remove();
        }, 220);
    }, 2200);
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
