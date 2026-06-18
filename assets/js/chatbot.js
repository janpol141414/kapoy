// Chatbot Widget JavaScript
(function() {
    const widget = document.getElementById('chatbotWidget');
    const toggle = document.getElementById('chatbotWidgetToggle');
    const close = document.getElementById('chatbotWidgetClose');
    const messages = document.getElementById('chatbotWidgetMessages');
    const input = document.getElementById('chatbotWidgetInput');
    const send = document.getElementById('chatbotWidgetSend');

    if (!widget || !toggle) return;

    // Toggle chatbot
    toggle.addEventListener('click', () => {
        widget.classList.toggle('show');
        if (widget.classList.contains('show')) {
            input.focus();
            const badge = document.getElementById('chatbotWidgetBadge');
            if (badge) badge.style.display = 'none';
        }
    });

    if (close) {
        close.addEventListener('click', () => {
            widget.classList.remove('show');
        });
    }

    // Send message
    if (send) {
        send.addEventListener('click', () => sendChatbotMessage());
    }

    if (input) {
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendChatbotMessage();
        });
    }
})();

function sendChatbotMessage(text) {
    const input = document.getElementById('chatbotWidgetInput');
    const messages = document.getElementById('chatbotWidgetMessages');
    
    const message = text || input.value.trim();
    if (!message) return;

    // Clear input
    if (!text) input.value = '';

    // Remove suggestions
    const suggestions = messages.querySelector('.chatbot-suggestions');
    if (suggestions) suggestions.remove();

    // Add user message
    appendChatbotMessage(message, 'user');

    // Show typing indicator
    const typingDiv = document.createElement('div');
    typingDiv.className = 'chatbot-message bot typing';
    typingDiv.innerHTML = '<div class="chatbot-msg-avatar"><i class="fas fa-robot"></i></div><div class="chatbot-msg-bubble"><div class="typing-dots"><span></span><span></span><span></span></div></div>';
    messages.appendChild(typingDiv);
    messages.scrollTop = messages.scrollHeight;

    // Call API
    // Detect base URL dynamically
    const _apiBase = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '').replace(/\/(auth|client|engineer|admin)$/, '');
    fetch(_apiBase + '/api/chatbot.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({message: message})
    })
    .then(r => r.json())
    .then(data => {
        typingDiv.remove();
        if (data.success) {
            appendChatbotMessage(data.response, 'bot');
        } else {
            appendChatbotMessage('Sorry, I encountered an error. Please try again.', 'bot');
        }
    })
    .catch(() => {
        typingDiv.remove();
        appendChatbotMessage('Sorry, I\'m having trouble connecting. Please try again later.', 'bot');
    });
}

function appendChatbotMessage(text, sender) {
    const messages = document.getElementById('chatbotWidgetMessages');
    const div = document.createElement('div');
    div.className = 'chatbot-message ' + sender;
    
    if (sender === 'bot') {
        div.innerHTML = `
            <div class="chatbot-msg-avatar"><i class="fas fa-hard-hat"></i></div>
            <div class="chatbot-msg-bubble">
                <p>${text.replace(/\n/g, '<br>')}</p>
                <span class="chatbot-msg-time">Just now</span>
            </div>
        `;
    } else {
        div.innerHTML = `
            <div class="chatbot-msg-bubble">
                <p>${text.replace(/\n/g, '<br>')}</p>
                <span class="chatbot-msg-time">Just now</span>
            </div>
        `;
    }
    
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
}
