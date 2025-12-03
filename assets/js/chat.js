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
        contentDiv.innerHTML = `<strong>${roleLabel}:</strong> ${this.escapeHtml(content)}`;
        
        messageDiv.appendChild(contentDiv);
        this.chatMessages.appendChild(messageDiv);
        
        // Scroll to bottom
        this.scrollToBottom();
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
        // Optional: Load previous messages from database
        // This can be implemented if you want to persist chat history
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

