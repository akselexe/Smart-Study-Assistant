// Chat functionality
class ChatApp {
    constructor() {
        this.sessionId = this.getOrCreateSessionId();
        this.chatMessages = document.getElementById('chatMessages');
        this.chatForm = document.getElementById('chatForm');
        this.messageInput = document.getElementById('messageInput');
        this.sendButton = document.getElementById('sendButton');
        this.subjectSelect = document.getElementById('subject');
        this.clearChatBtn = document.getElementById('clearChat');
        this.newSessionBtn = document.getElementById('newSession');
        
        this.init();
    }

    init() {
        this.chatForm.addEventListener('submit', (e) => this.handleSubmit(e));
        this.clearChatBtn.addEventListener('click', () => this.clearChat());
        this.newSessionBtn.addEventListener('click', () => this.newSession());
        
        // Load chat history if available
        this.loadChatHistory();
    }

    getOrCreateSessionId() {
        let sessionId = sessionStorage.getItem('chatSessionId');
        if (!sessionId) {
            sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('chatSessionId', sessionId);
        }
        return sessionId;
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        const message = this.messageInput.value.trim();
        if (!message) return;

        // Add user message to chat
        this.addMessage('user', message);
        this.messageInput.value = '';
        
        // Disable input while processing
        this.setLoading(true);

        try {
            const requestBody = {
                message: message,
                session_id: this.sessionId,
                subject: this.subjectSelect.value
            };
            
            // Include exercise context if available
            if (window.exerciseContext && window.exerciseContext.id) {
                requestBody.exercise_id = window.exerciseContext.id;
            }
            
            const response = await fetch('api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestBody)
            });

            const data = await response.json();

            if (data.success) {
                this.addMessage('assistant', data.message);
            } else {
                this.addErrorMessage(data.error || 'An error occurred. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            this.addErrorMessage('Failed to connect to the server. Please check your connection and API key.');
        } finally {
            this.setLoading(false);
        }
    }

    addMessage(role, content) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${role}`;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        const roleLabel = role === 'user' ? 'You' : 'Assistant';
        if (role === 'assistant') {
            // Render assistant content using a safe third-party renderer (marked) + DOMPurify.
            // We strip triple-backtick fences (```) and all '*' characters per request before parsing.
            contentDiv.innerHTML = `<strong>${roleLabel}:</strong> <div class="assistant-content">Rendering...</div>`;
            // Append before async rendering so DOM position is set
            messageDiv.appendChild(contentDiv);
            this.chatMessages.appendChild(messageDiv);
            this.renderAssistantMessage(content, contentDiv.querySelector('.assistant-content'));
            return; // already appended
        } else {
            // Keep user content escaped/plain
            contentDiv.innerHTML = `<strong>${roleLabel}:</strong> ${this.escapeHtml(content)}`;
        }
        
        messageDiv.appendChild(contentDiv);
        this.chatMessages.appendChild(messageDiv);
        
        // Scroll to bottom
        this.scrollToBottom();
    }

    // Render assistant message using marked + DOMPurify loaded from CDN.
    async renderAssistantMessage(content, container) {
        try {
            await this.ensureMarkdownLibs();

            // Per request: remove triple-backtick fences and all '*' characters before parsing
            let md = String(content || '');
            // Remove only the ``` fence markers (keep inner content)
            md = md.replace(/```/g, '');
            // Remove all asterisks
            md = md.replace(/\*/g, '');

            // Parse to HTML with marked (if available) otherwise escape
            let html;
            if (window.marked && typeof window.marked.parse === 'function') {
                html = window.marked.parse(md);
            } else {
                html = this.escapeHtml(md).replace(/\r?\n/g, '<br>');
            }

            // Sanitize with DOMPurify if available
            if (window.DOMPurify && typeof window.DOMPurify.sanitize === 'function') {
                container.innerHTML = window.DOMPurify.sanitize(html);
            } else {
                container.innerHTML = html;
            }
            this.scrollToBottom();
        } catch (err) {
            // Fallback: show escaped plain text
            container.textContent = String(content);
            this.scrollToBottom();
        }
    }

    // Ensure marked + DOMPurify are loaded; loads from CDN if missing.
    ensureMarkdownLibs() {
        if (window.marked && window.DOMPurify) return Promise.resolve();

        const loadScript = (url) => new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = url;
            s.async = true;
            s.onload = () => resolve();
            s.onerror = () => reject(new Error('Failed to load ' + url));
            document.head.appendChild(s);
        });

        // Load both libs in parallel (marked first is fine)
        const markedUrl = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
        const dompurifyUrl = 'https://cdn.jsdelivr.net/npm/dompurify@2.4.0/dist/purify.min.js';

        const promises = [];
        if (!window.marked) promises.push(loadScript(markedUrl));
        if (!window.DOMPurify) promises.push(loadScript(dompurifyUrl));

        return Promise.all(promises);
    }

    addErrorMessage(error) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = error;
        this.chatMessages.appendChild(errorDiv);
        this.scrollToBottom();
    }

    setLoading(loading) {
        this.sendButton.disabled = loading;
        this.messageInput.disabled = loading;
        
        if (loading) {
            this.sendButton.innerHTML = '<span class="loading"></span>';
        } else {
            this.sendButton.innerHTML = '<span>Send</span>';
            this.messageInput.focus();
        }
    }

    scrollToBottom() {
        this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
    }

    clearChat() {
        if (confirm('Are you sure you want to clear the chat?')) {
            this.chatMessages.innerHTML = `
                <div class="message assistant">
                    <div class="message-content">
                        <strong>Assistant:</strong> Chat cleared. How can I help you learn today?
                    </div>
                </div>
            `;
        }
    }

    newSession() {
        if (confirm('Start a new chat session?')) {
            this.sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('chatSessionId', this.sessionId);
            this.clearChat();
        }
    }

    async loadChatHistory() {
        // Load previous messages from database (if any) for the current session.
        // This will request `api/chat_history.php` which returns the messages for the
        // provided session_id (or the user's latest session if none provided).
        try {
            const resp = await fetch('api/chat_history.php?session_id=' + encodeURIComponent(this.sessionId), {
                credentials: 'same-origin'
            });
            const data = await resp.json();
            if (data && data.success && Array.isArray(data.messages) && data.messages.length > 0) {
                // Clear current messages area and render the returned history
                this.chatMessages.innerHTML = '';
                for (const m of data.messages) {
                    // m.role, m.message
                    this.addMessage(m.role, m.message);
                }
            }
        } catch (err) {
            // Fail silently — history is optional
            console.error('Failed to load chat history:', err);
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize chat app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ChatApp();
});

