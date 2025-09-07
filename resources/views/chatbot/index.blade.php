@extends('layouts.app')

@section('main-content')
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h4 class="text-center fw-bold">Loncey ChatBot</h4>
                </div>

                <div class="card-body chat-body d-flex flex-column" id="chatBox"
                    style="min-height:300px; max-height:500px; overflow:auto;">
                    <div class="chat-message chat-bot animate-msg">
                        💬 <strong>Loncey Tech Virtual Assistant</strong><br>
                        How can I assist you today?
                    </div>
                </div>

                <div class="card-footer">
                    <div class="input-group">
                        <input type="text" id="userInput" class="form-control" placeholder="Enter your question">
                        <button class="btn btn-outline-secondary" type="button" id="sendBtn">
                            <i class="bi bi-send-fill"></i> Send
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .chat-message {
            padding: 10px 14px;
            margin-bottom: 8px;
            border-radius: 12px;
            max-width: 85%;
            font-size: 14px;
            line-height: 1.4;
            animation: fadeSlide 0.4s ease-in-out;
        }

        .chat-bot {
            background: #e5f0f3; /* light brand tint */
            color: #34667a;
            align-self: flex-start;
            border: 1px solid #c9dfe5;
        }

        .chat-user {
            background: #2b4c5a; /* dark solid blue-grey */
            color: #fff;
            align-self: flex-end;
            text-align: right;
        }

        .chat-temp {
            opacity: 0.7;
            font-style: italic;
        }

        /* Animation */
        @keyframes fadeSlide {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script>
        (function() {
            const chatBox = document.getElementById('chatBox');
            const userInput = document.getElementById('userInput');
            const sendBtn = document.getElementById('sendBtn');

            function appendMessage(html, cls = 'chat-bot') {
                const d = document.createElement('div');
                d.className = 'chat-message ' + cls + ' animate-msg';
                d.innerHTML = html;
                chatBox.appendChild(d);
                chatBox.scrollTop = chatBox.scrollHeight;
                return d;
            }

            function appendUser(text) {
                return appendMessage('<strong>You</strong><br>' + escapeHtml(text), 'chat-user');
            }

            function escapeHtml(unsafe) {
                return unsafe.replace(/[&<"']/g, function(m) {
                    return ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    } [m]);
                });
            }

            sendBtn.addEventListener('click', sendMessage);
            userInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') sendMessage();
            });

            async function sendMessage() {
                const msg = userInput.value.trim();
                if (!msg) return;
                appendUser(msg);
                userInput.value = '';

                const typingEl = appendMessage('Typing...', 'chat-temp');

                try {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

                    const res = await fetch("{{ route('chat') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            message: msg
                        })
                    });

                    const data = await res.json();
                    typingEl.remove();

                    if (res.ok && data.reply) {
                        const cleanText = data.reply
                            .replace(/^\*+\s?/gm, '')
                            .replace(/\n/g, '<br>')
                            .trim();

                        appendMessage('<strong>Loncey Tech Virtual Assistant</strong><br>' + cleanText, 'chat-bot');
                    } else {
                        appendMessage('Error: ' + (data.message || data.error || 'Unknown'), 'chat-bot');
                    }
                } catch (err) {
                    typingEl.remove();
                    appendMessage('Network error: ' + err.message, 'chat-bot');
                }
            }

        })();
    </script>
@endsection
