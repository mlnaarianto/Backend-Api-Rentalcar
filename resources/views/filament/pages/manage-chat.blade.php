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

        // Fungsi helper untuk mengambil nama asli user dari ID atau field database
        const resolveDisplayName = (docId, data) => {
            // 1. Cek apakah ada field langsung di dokumen
            if (data.customer_name) return data.customer_name;
            if (data.name) return data.name;
            if (data.user_name) return data.user_name;
            if (data.last_sender_name && data.last_sender_name !== "Admin Rental") {
                return data.last_sender_name;
            }

            // 2. Jika berbentuk room email (misal: room_user_kyosohma567_gmail_com)
            if (docId.includes('room_user_')) {
                let clean = docId.replace('room_user_', '');
                clean = clean.replace(/_com$/, '.com');
                // Ubah _ menjadi titik (.) kecuali bagian belakang
                let parts = clean.split('_');
                if (parts.length > 1) {
                    let domain = parts.pop();
                    let username = parts.join('.');
                    return `${username}@${domain}`;
                }
                return clean;
            }

            // 3. Jika berbentuk penugasan rental/driver (misal: room_rental_2_user_3)
            if (docId.includes('room_rental_')) {
                let parts = docId.split('_');
                // Mencoba mendeteksi apakah ini chat dengan user/driver lalu memberikan label yang ramah
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

        // 1. REALTIME LISTENER UNTUK GABUNGAN 'chats' & 'store_chats'
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
                        listContainer.innerHTML = '<div style="text-align: center; padding: 40px 20px; color: #9ca3af; font-size: 14px;">Belum ada pesan masuk.</div>';
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
                                 style="padding: 15px; cursor: pointer; border-bottom: 1px solid #f3f4f6; background: ${isSelected ? '#fef3c7' : 'transparent'}; border-left: ${isSelected ? '4px solid #d97706' : 'none'}; transition: background 0.2s;">
                                <div style="font-weight: bold; font-size: 14px; color: #111827; margin-bottom: 4px;">
                                    ${escapeHtml(displayName)}
                                </div>
                                <div style="font-size: 12px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    ${escapeHtml(lastMsg)}
                                </div>
                            </div>
                        `;
                    });
                    listContainer.innerHTML = html;
                });
            });
        }

        loadAllChats();

        // 2. FUNGSI PILIH ROOM & REALTIME LISTENER PESAN
        window.selectChatRoom = function(chatPath, displayName) {
            activeChatPath = chatPath;
            document.getElementById("current-room-title").innerText = `${displayName}`;

            const chatBox = document.getElementById("admin-chat-box");
            chatBox.innerHTML = '<div style="text-align: center; color: #9ca3af; font-size: 13px;">Memuat pesan...</div>';

            if (unsubscribeMessages) {
                unsubscribeMessages();
            }

            const msgQuery = query(collection(db, chatPath, "messages"), orderBy("created_at", "asc"));

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

        // 3. FUNGSI KIRIM PESAN
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