// FILE: /public/chat-widget.js

/**
 * SplashSupportAI - Chat Widget
 * Embeddable customer chat widget
 */

(function() {
    'use strict';

    const script = document.currentScript;
    const tenantCode = script.getAttribute('data-tenant');
    const baseUrl = script.getAttribute('data-url') || script.src.split('/chat-widget.js')[0];

    if (!tenantCode) {
        console.error('SplashSupportAI: data-tenant attribute is required');
        return;
    }

    let sessionToken = localStorage.getItem('splash_chat_session_' + tenantCode);
    let isOpen = false;
    let messages = [];

    // Create widget HTML
    function createWidget() {
        const widgetHTML = `
            <div id="splash-chat-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; font-family: Arial, sans-serif;">
                <div id="splash-chat-box" style="display: none; width: 350px; height: 500px; background: white; border-radius: 10px; box-shadow: 0 5px 40px rgba(0,0,0,0.16); flex-direction: column; overflow: hidden;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: bold; font-size: 16px;">SplashSupportAI</div>
                            <div style="font-size: 12px; opacity: 0.9;">We're here to help</div>
                        </div>
                        <button id="splash-chat-close" style="background: transparent; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
                    </div>
                    <div id="splash-chat-messages" style="flex: 1; overflow-y: auto; padding: 15px; background: #f5f5f5;"></div>
                    <div style="padding: 15px; background: white; border-top: 1px solid #eee;">
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="splash-chat-input" placeholder="Type your message..." style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                            <button id="splash-chat-send" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold;">Send</button>
                        </div>
                    </div>
                </div>
                <button id="splash-chat-toggle" style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer; display: flex; align-items: center; justify-content: center; color: white; font-size: 28px;">
                    <span>💬</span>
                </button>
            </div>
        `;

        const div = document.createElement('div');
        div.innerHTML = widgetHTML;
        document.body.appendChild(div);

        // Event listeners
        document.getElementById('splash-chat-toggle').addEventListener('click', toggleChat);
        document.getElementById('splash-chat-close').addEventListener('click', toggleChat);
        document.getElementById('splash-chat-send').addEventListener('click', sendMessage);
        document.getElementById('splash-chat-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }

    function toggleChat() {
        isOpen = !isOpen;
        const chatBox = document.getElementById('splash-chat-box');
        const toggle = document.getElementById('splash-chat-toggle');

        if (isOpen) {
            chatBox.style.display = 'flex';
            toggle.style.display = 'none';
            if (!sessionToken) {
                initSession();
            } else {
                loadMessages();
            }
        } else {
            chatBox.style.display = 'none';
            toggle.style.display = 'flex';
        }
    }

    function initSession() {
        fetch(baseUrl + '/chat/init', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'tenant_code=' + encodeURIComponent(tenantCode)
        })
        .then(response => response.json())
        .then(data => {
            if (data.session_token) {
                sessionToken = data.session_token;
                localStorage.setItem('splash_chat_session_' + tenantCode, sessionToken);
                addSystemMessage('Welcome! How can we help you today?');
            }
        })
        .catch(error => {
            console.error('Error initializing chat:', error);
        });
    }

    function sendMessage() {
        const input = document.getElementById('splash-chat-input');
        const message = input.value.trim();

        if (!message || !sessionToken) return;

        input.value = '';
        addMessage('customer', message);

        fetch(baseUrl + '/chat/message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_token: sessionToken,
                message: message
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.ai_reply) {
                setTimeout(() => {
                    addMessage('agent', data.ai_reply);
                }, 500);
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            addSystemMessage('Failed to send message. Please try again.');
        });
    }

    function loadMessages() {
        fetch(baseUrl + '/chat/getMessages?session_token=' + encodeURIComponent(sessionToken))
            .then(response => response.json())
            .then(data => {
                if (data.messages && data.messages.length > 0) {
                    const messagesContainer = document.getElementById('splash-chat-messages');
                    messagesContainer.innerHTML = '';
                    data.messages.forEach(msg => {
                        addMessage(msg.sender_type, msg.body_text, false);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading messages:', error);
            });
    }

    function addMessage(sender, text, scroll = true) {
        const messagesContainer = document.getElementById('splash-chat-messages');

        const isCustomer = sender === 'customer';
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = `
            margin-bottom: 15px;
            display: flex;
            ${isCustomer ? 'justify-content: flex-end;' : 'justify-content: flex-start;'}
        `;

        const bubble = document.createElement('div');
        bubble.style.cssText = `
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.4;
            ${isCustomer
                ? 'background: #667eea; color: white;'
                : 'background: white; color: #333; border: 1px solid #e0e0e0;'}
        `;
        bubble.textContent = text;

        messageDiv.appendChild(bubble);
        messagesContainer.appendChild(messageDiv);

        if (scroll) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }

    function addSystemMessage(text) {
        const messagesContainer = document.getElementById('splash-chat-messages');
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = 'margin-bottom: 15px; text-align: center; font-size: 12px; color: #999;';
        messageDiv.textContent = text;
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Initialize widget when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createWidget);
    } else {
        createWidget();
    }
})();
