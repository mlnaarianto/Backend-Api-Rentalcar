<x-filament-panels::page>
    <style>
        .chat-shell {
            display: flex;
            gap: 20px;
            height: 75vh;
            min-height: 550px;
            width: 100%;
        }
        .chat-panel {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        html.dark .chat-panel {
            background: #111827;
            border-color: #374151;
        }

        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        html.dark .chat-header {
            background: #1f2937;
            border-color: #374151;
        }
        .chat-header h3 {
            font-weight: bold;
            font-size: 15px;
            color: #1f2937;
            margin: 0;
        }
        html.dark .chat-header h3 { color: #f3f4f6; }

        .chat-header span { font-size: 13px; font-weight: 600; color: #4b5563; }
        html.dark .chat-header span { color: #d1d5db; }
        .chat-header .room-title { color: #d97706; font-weight: bold; }
        html.dark .chat-header .room-title { color: #fbbf24; }

        #admin-chat-list { flex: 1; overflow-y: auto; }
        .chat-list-empty { text-align: center; padding: 40px 20px; color: #9ca3af; font-size: 14px; }
        html.dark .chat-list-empty { color: #6b7280; }

        .chat-list-item {
            padding: 15px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s;
        }
        html.dark .chat-list-item { border-color: #1f2937; }
        .chat-list-item:hover { background: #f9fafb; }
        html.dark .chat-list-item:hover { background: #1f2937; }
        .chat-list-item.selected {
            background: #fef3c7;
            border-left: 4px solid #d97706;
        }
        html.dark .chat-list-item.selected {
            background: rgba(217, 119, 6, 0.15);
            border-left: 4px solid #fbbf24;
        }
        .chat-list-item .name { font-weight: bold; font-size: 14px; color: #111827; margin-bottom: 4px; }
        html.dark .chat-list-item .name { color: #f3f4f6; }
        .chat-list-item .last-msg { font-size: 12px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        html.dark .chat-list-item .last-msg { color: #9ca3af; }

        #admin-chat-box {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f9fafb;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        html.dark #admin-chat-box { background: #1f2937; }
        .chat-box-empty {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; height: 100%; color: #9ca3af; font-size: 14px;
        }
        html.dark .chat-box-empty { color: #6b7280; }

        .chat-input-bar { padding: 12px 15px; border-top: 1px solid #e5e7eb; background: #ffffff; }
        html.dark .chat-input-bar { background: #111827; border-color: #374151; }
        .chat-input-bar .row { display: flex; gap: 10px; }

        #admin-message-input {
            flex: 1; padding: 10px 14px; border: 1px solid #d1d5db;
            border-radius: 8px; font-size: 13px; outline: none;
            background: #ffffff; color: #111827;
        }
        html.dark #admin-message-input {
            background: #1f2937; border-color: #4b5563; color: #f3f4f6;
        }
        #admin-message-input::placeholder { color: #9ca3af; }
        html.dark #admin-message-input::placeholder { color: #6b7280; }

        #send-btn {
            padding: 10px 20px; background: #d97706; color: white;
            font-weight: 600; border: none; border-radius: 8px;
            font-size: 13px; cursor: pointer;
        }
        #send-btn:hover { background: #b45309; }

        .msg-bubble {
            max-width: 70%; padding: 10px 14px; border-radius: 12px;
            font-size: 13px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .msg-bubble.from-admin { background: #d97706; color: #ffffff; border: none; }
        .msg-bubble.from-customer { background: #ffffff; color: #1f2937; border: 1px solid #e5e7eb; }
        html.dark .msg-bubble.from-customer { background: #374151; color: #f3f4f6; border-color: #4b5563; }
        .msg-bubble .sender { font-size: 10px; font-weight: 600; margin-bottom: 2px; opacity: 0.8; }
        .msg-bubble p { margin: 0; line-height: 1.4; word-break: break-word; }
        .msg-bubble .time { font-size: 9px; display: block; text-align: right; margin-top: 4px; opacity: 0.7; }
    </style>

    <div class="chat-shell">
        
        <!-- KOLOM KIRI: Daftar Inbox Customer (35%) -->
        <div class="chat-panel" style="width: 35%;">
            <div class="chat-header">
                <h3>Inbox Customer</h3>
            </div>
            
            <div id="admin-chat-list">
                <div class="chat-list-empty">Memuat daftar chat...</div>
            </div>
        </div>

        <!-- KOLOM KANAN: Ruang Obrolan Realtime (65%) -->
        <div class="chat-panel" style="width: 65%;">
            <div class="chat-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span>Room: <span id="current-room-title" class="room-title">Pilih chat di samping</span></span>
            </div>

            <div id="admin-chat-box">
                <div class="chat-box-empty">
                    <p>Pilih salah satu customer di sebelah kiri untuk mulai membalas.</p>
                </div>
            </div>

            <div class="chat-input-bar">
                <div class="row">
                    <input id="admin-message-input" type="text" placeholder="Ketik balasan untuk customer..." />
                    <button id="send-btn" type="button">Kirim</button>
                </div>
            </div>
        </div>

    </div>

    <!-- 🔥 FIREBASE JS SDK REALTIME LISTENER -->
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { 
            getFirestore, 
            collection, 
            addDoc, 
            query, 
            orderBy, 
            onSnapshot, 
            serverTimestamp, 
            doc, 
            setDoc 
        } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

        const firebaseConfig = {
            projectId: "rental-b9f93"
        };

        const app = initializeApp(firebaseConfig);
        const db = getFirestore(app);

        let activeChatPath = null;
        let unsubscribeMessages = null;

        const escapeHtml = (text) => {
            if (!text) return "";
            return text.toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        };

        const formatTime = (timestamp) => {
            if (!timestamp) return "";
            const date = timestamp.toDate ? timestamp.toDate() : new Date(timestamp);
            return new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(date);
        };

        const resolveDisplayName = (docId, data) => {
            if (data.customer_name) return data.customer_name;
            if (data.name) return data.name;
            if (data.user_name) return data.user_name;
            if (data.last_sender_name && data.last_sender_name !== "Admin Rental") {
                return data.last_sender_name;
            }

            if (docId.includes('room_user_')) {
                let clean = docId.replace('room_user_', '');
                clean = clean.replace(/_com$/, '.com');
                let parts = clean.split('_');
                if (parts.length > 1) {
                    let domain = parts.pop();
                    let username = parts.join('.');
                    return `${username}@${domain}`;
                }
                return clean;
            }

            if (docId.includes('room_rental_')) {
                let parts = docId.split('_');
                if (docId.includes('_user_')) {
                    return `Penyewa (ID: ${parts[parts.length - 1]})`;
                }
                if (docId.includes('_driver_')) {
                    return `Driver (ID: ${parts[parts.length - 1]})`;
                }
                return docId.replace(/_/g, ' ');
            }

            return docId;
        };

        async function loadAllChats() {
            const listContainer = document.getElementById("admin-chat-list");
            
            const chatsRef = collection(db, "chats");
            const storeChatsRef = collection(db, "store_chats");

            onSnapshot(chatsRef, (snapshotChats) => {
                onSnapshot(storeChatsRef, (snapshotStore) => {
                    let allDocs = [];

                    snapshotChats.forEach(docSnap => {
                        allDocs.push({ id: docSnap.id, parent: 'chats', ...docSnap.data() });
                    });

                    snapshotStore.forEach(docSnap => {
                        allDocs.push({ id: docSnap.id, parent: 'store_chats', ...docSnap.data() });
                    });

                    allDocs.sort((a, b) => {
                        const timeA = a.updated_at?.toMillis ? a.updated_at.toMillis() : 0;
                        const timeB = b.updated_at?.toMillis ? b.updated_at.toMillis() : 0;
                        return timeB - timeA;
                    });

                    if (allDocs.length === 0) {
                        listContainer.innerHTML = '<div class="chat-list-empty">Belum ada pesan masuk.</div>';
                        return;
                    }

                    let html = "";
                    allDocs.forEach((data) => {
                        const chatPath = `${data.parent}/${data.id}`;
                        const displayName = resolveDisplayName(data.id, data);
                        const lastMsg = data.last_message || '';
                        const isSelected = activeChatPath === chatPath;

                        html += `
                            <div onclick="window.selectChatRoom('${chatPath}', '${escapeHtml(displayName)}')" 
                                 class="chat-list-item ${isSelected ? 'selected' : ''}">
                                <div class="name">${escapeHtml(displayName)}</div>
                                <div class="last-msg">${escapeHtml(lastMsg)}</div>
                            </div>
                        `;
                    });
                    listContainer.innerHTML = html;
                });
            });
        }

        loadAllChats();

        window.selectChatRoom = function(chatPath, displayName) {
            activeChatPath = chatPath;
            document.getElementById("current-room-title").innerText = `${displayName}`;

            const chatBox = document.getElementById("admin-chat-box");
            chatBox.innerHTML = '<div class="chat-box-empty"><p>Memuat pesan...</p></div>';

            if (unsubscribeMessages) {
                unsubscribeMessages();
            }

            const msgQuery = query(collection(db, chatPath, "messages"), orderBy("created_at", "asc"));

            unsubscribeMessages = onSnapshot(msgQuery, (snapshot) => {
                chatBox.innerHTML = "";

                if (snapshot.empty) {
                    chatBox.innerHTML = '<div class="chat-box-empty"><p>Belum ada riwayat pesan di room ini.</p></div>';
                    return;
                }

                snapshot.forEach((docSnap) => {
                    const data = docSnap.data();
                    const isAdmin = data.sender_id === 'admin';
                    const timeText = formatTime(data.created_at);
                    const messageText = data.text || data.message || '';

                    const div = document.createElement("div");
                    div.style.display = "flex";
                    div.style.justifyContent = isAdmin ? "flex-end" : "flex-start";
                    div.style.width = "100%";

                    div.innerHTML = `
                        <div class="msg-bubble ${isAdmin ? 'from-admin' : 'from-customer'}">
                            <div class="sender">${escapeHtml(data.sender_name)}</div>
                            <p>${escapeHtml(messageText)}</p>
                            ${timeText ? `<span class="time">${timeText}</span>` : ''}
                        </div>
                    `;
                    chatBox.appendChild(div);
                });

                chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: "smooth" });
            });
        };

        async function sendAdminMessage() {
            if (!activeChatPath) {
                alert("Pilih room chat terlebih dahulu di sebelah kiri!");
                return;
            }

            const input = document.getElementById("admin-message-input");
            const text = input.value.trim();
            if (!text) return;

            await addDoc(collection(db, activeChatPath, "messages"), {
                text: text,
                sender_id: "admin",
                sender_name: "Admin Rental",
                created_at: serverTimestamp()
            });

            await setDoc(doc(db, activeChatPath), {
                last_message: text,
                last_sender_id: "admin",
                last_sender_name: "Admin Rental",
                updated_at: serverTimestamp()
            }, { merge: true });

            input.value = "";
        }

        document.getElementById("send-btn").addEventListener("click", sendAdminMessage);
        document.getElementById("admin-message-input").addEventListener("keydown", function(e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                sendAdminMessage();
            }
        });
    </script>
</x-filament-panels::page>