<x-filament-panels::page>
    <div style="display: flex; gap: 20px; height: 75vh; min-height: 550px; width: 100%;">
        
        <!-- KOLOM KIRI: Daftar Inbox Customer (35%) -->
        <div style="width: 35%; background: white; border: 1px solid #e5e7eb; border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="padding: 15px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                <h3 style="font-weight: bold; font-size: 15px; color: #1f2937; margin: 0;">Inbox Customer</h3>
            </div>
            
            <div id="admin-chat-list" style="flex: 1; overflow-y: auto;">
                <div style="text-align: center; padding: 40px 20px; color: #9ca3af; font-size: 14px;">
                    Memuat daftar chat...
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN: Ruang Obrolan Realtime (65%) -->
        <div style="width: 65%; background: white; border: 1px solid #e5e7eb; border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <!-- Header Room -->
            <div style="padding: 15px; border-bottom: 1px solid #e5e7eb; background: #f9fafb; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 13px; font-weight: 600; color: #4b5563;">
                    Room: <span id="current-room-title" style="color: #d97706; font-weight: bold;">Pilih chat di samping</span>
                </span>
            </div>

            <!-- Box Pesan -->
            <div id="admin-chat-box" style="flex: 1; padding: 20px; overflow-y: auto; background: #f9fafb; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #9ca3af; font-size: 14px;">
                    <p>Pilih salah satu customer di sebelah kiri untuk mulai membalas.</p>
                </div>
            </div>

            <!-- Form Input Balasan Admin -->
            <div style="padding: 12px 15px; border-top: 1px solid #e5e7eb; background: #ffffff;">
                <div style="display: flex; gap: 10px;">
                    <input id="admin-message-input" type="text" placeholder="Ketik balasan untuk customer..." 
                           style="flex: 1; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; outline: none;" />
                    <button id="send-btn" type="button" style="padding: 10px 20px; background: #d97706; color: white; font-weight: 600; border: none; border-radius: 8px; font-size: 13px; cursor: pointer;">
                        Kirim
                    </button>
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

        let activeChatId = null;
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

        // 1. REALTIME LISTENER UNTUK DAFTAR INBOX
        const chatsQuery = query(collection(db, "chats"), orderBy("updated_at", "desc"));
        
        onSnapshot(chatsQuery, (snapshot) => {
            const listContainer = document.getElementById("admin-chat-list");
            let html = "";

            if (snapshot.empty) {
                listContainer.innerHTML = '<div style="text-align: center; padding: 40px 20px; color: #9ca3af; font-size: 14px;">Belum ada pesan masuk.</div>';
                return;
            }

            snapshot.forEach((docSnap) => {
                const chatId = docSnap.id;
                const data = docSnap.data();
                const userName = data.name || data.user_name || 'Customer';
                const lastMsg = data.last_message || '';
                const isSelected = activeChatId === chatId;

                html += `
                    <div onclick="window.selectChatRoom('${chatId}', '${escapeHtml(userName)}')" 
                         style="padding: 15px; cursor: pointer; border-bottom: 1px solid #f3f4f6; background: ${isSelected ? '#fef3c7' : 'transparent'}; border-left: ${isSelected ? '4px solid #d97706' : 'none'}; transition: background 0.2s;">
                        <div style="font-weight: bold; font-size: 14px; color: #111827; margin-bottom: 4px;">
                            ${escapeHtml(userName)}
                        </div>
                        <div style="font-size: 12px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${escapeHtml(lastMsg)}
                        </div>
                    </div>
                `;
            });
            listContainer.innerHTML = html;
        });

        // 2. FUNGSI PILIH ROOM & REALTIME LISTENER PESAN
        window.selectChatRoom = function(chatId, userName) {
            activeChatId = chatId;
            document.getElementById("current-room-title").innerText = `${userName} (${chatId})`;

            const chatBox = document.getElementById("admin-chat-box");
            chatBox.innerHTML = '<div style="text-align: center; color: #9ca3af; font-size: 13px;">Memuat pesan...</div>';

            if (unsubscribeMessages) {
                unsubscribeMessages();
            }

            const msgQuery = query(collection(db, "chats", chatId, "messages"), orderBy("created_at", "asc"));

            unsubscribeMessages = onSnapshot(msgQuery, (snapshot) => {
                chatBox.innerHTML = "";

                if (snapshot.empty) {
                    chatBox.innerHTML = '<div style="text-align: center; color: #9ca3af; font-size: 13px;">Belum ada riwayat pesan di room ini.</div>';
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
                        <div style="max-width: 70%; padding: 10px 14px; border-radius: 12px; font-size: 13px; background: ${isAdmin ? '#d97706' : '#ffffff'}; color: ${isAdmin ? '#ffffff' : '#1f2937'}; border: ${isAdmin ? 'none' : '1px solid #e5e7eb'}; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <div style="font-size: 10px; font-weight: 600; margin-bottom: 2px; opacity: 0.8;">${escapeHtml(data.sender_name)}</div>
                            <p style="margin: 0; line-height: 1.4; word-break: break-word;">${escapeHtml(messageText)}</p>
                            ${timeText ? `<span style="font-size: 9px; display: block; text-align: right; margin-top: 4px; opacity: 0.7;">${timeText}</span>` : ''}
                        </div>
                    `;
                    chatBox.appendChild(div);
                });

                chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: "smooth" });
            });
        };

        // 3. FUNGSI KIRIM PESAN (Menggunakan key 'text' agar sinkron dengan Flutter)
        async function sendAdminMessage() {
            if (!activeChatId) {
                alert("Pilih room chat terlebih dahulu di sebelah kiri!");
                return;
            }

            const input = document.getElementById("admin-message-input");
            const text = input.value.trim();
            if (!text) return;

            await addDoc(collection(db, "chats", activeChatId, "messages"), {
                text: text, // 👈 Kunci field 'text' wajib sama persis dengan Flutter
                sender_id: "admin",
                sender_name: "Admin Rental",
                created_at: serverTimestamp()
            });

            await setDoc(doc(db, "chats", activeChatId), {
                last_message: text,
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