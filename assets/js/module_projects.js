// assets/js/module_projects.js

// API Konfiguration
const GEMINI_API_KEY = "YOUR_GEMINI_API_KEY"; // <--- HIER KEY EINFÜGEN
const GEMINI_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" + GEMINI_API_KEY;

document.addEventListener('alpine:init', () => {
    Alpine.data('projectEditor', () => ({
        isSaving: false,
        project: {
            id: null,
            client_id: '',
            title: '',
            risk_factor: 1.2
        },
        items: [
            // Start mit einer leeren Zeile
            { title: '', description: '', hours: 0, loading: false, files: [], isDragging: false, uploading: false }
        ],

        addItem() {
            this.items.push({ title: '', description: '', hours: 0, loading: false, files: [], isDragging: false, uploading: false });
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            } else {
                // Letzte Zeile nur leeren, nicht löschen
                this.items[0].title = '';
                this.items[0].description = '';
                this.items[0].hours = 0;
                this.items[0].files = [];
                this.items[0].isDragging = false;
                this.items[0].uploading = false;
            }
        },

        calculateTotalHours() {
            return this.items.reduce((sum, item) => sum + Number(item.hours), 0).toFixed(2);
        },

        calculateBufferHours() {
            let base = this.items.reduce((sum, item) => sum + Number(item.hours), 0);
            let buffer = base * (this.project.risk_factor - 1);
            return buffer.toFixed(2);
        },

        calculateGrandTotal() {
            let base = this.items.reduce((sum, item) => sum + Number(item.hours), 0);
            return (base * this.project.risk_factor).toFixed(2);
        },

        // --- GEMINI KI INTEGRATION ---
        async askGemini(index) {
            let item = this.items[index];
            if (!item.title) return alert("Bitte erst eine Aufgabe eingeben.");

            item.loading = true;

            // Der Prompt: Wir zwingen Gemini in ein JSON Format
            const prompt = `
                Agiere als Senior Web Developer Projektleiter.
                Schätze den Aufwand für folgende Aufgabe: "${item.title}".
                Projekt-Kontext: "${this.project.title}".
                
                Antworte NUR mit einem JSON Objekt (kein Markdown, kein Text davor/danach) in diesem Format:
                {
                    "description": "Detaillierte technische Beschreibung der Schritte und Risiken.",
                    "hours": 4.5
                }
                Sei realistisch, eher konservativ.
            `;

            const payload = {
                contents: [{
                    parts: [{ text: prompt }]
                }]
            };

            try {
                const response = await fetch(GEMINI_URL, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                
                // Parsing der Gemini Antwort
                if (data.candidates && data.candidates[0].content) {
                    let rawText = data.candidates[0].content.parts[0].text;
                    
                    // Cleanup: Falls Gemini Markdown Backticks ```json schickt
                    rawText = rawText.replace(/```json/g, '').replace(/```/g, '').trim();
                    
                    const result = JSON.parse(rawText);

                    // Daten in die Zeile schreiben
                    item.description = result.description;
                    item.hours = result.hours;
                } else {
                    console.error("Gemini Error:", data);
                    alert("KI konnte keine Antwort generieren.");
                }

            } catch (error) {
                console.error(error);
                alert("Verbindungsfehler zur KI API.");
            } finally {
                item.loading = false;
            }
        },

        // --- SPEICHERN ---
        async saveProject(status) {
            if (!this.project.client_id) return alert("Bitte einen Kunden wählen.");
            if (!this.project.title) return alert("Bitte einen Projekttitel eingeben.");

            this.isSaving = true;

            const payload = {
                project: { ...this.project, status: status },
                items: this.items
            };

            try {
                const response = await fetch('modules/projects/save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    alert(status === 'sent' ? "Projekt an Kunden gesendet!" : "Entwurf gespeichert.");
                    // Optional: Redirect oder ID setzen
                    if(!this.project.id) this.project.id = result.project_id;
                } else {
                    alert("Fehler beim Speichern: " + result.message);
                }

            } catch (e) {
                alert("Systemfehler beim Speichern.");
            } finally {
                this.isSaving = false;
            }
        },

        // --- FILE UPLOAD LOGIC ---
        handleDrop(event, index) {
            this.items[index].isDragging = false;
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.uploadFile(files[0], index);
            }
        },

        handleFileSelect(event, index) {
            const files = event.target.files;
            if (files.length > 0) {
                this.uploadFile(files[0], index);
            }
        },

        async uploadFile(file, index) {
            let item = this.items[index];
            item.uploading = true;

            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch('modules/upload/handle.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    // Initialisiere Array falls undefined
                    if (!item.files) item.files = [];
                    
                    item.files.push({
                        original_name: result.original_name,
                        stored_name: result.stored_name,
                        path: result.path
                    });
                } else {
                    alert('Upload Fehler: ' + result.error);
                }
            } catch (e) {
                alert('Netzwerkfehler beim Upload');
            } finally {
                item.uploading = false;
            }
        },

        removeFile(itemIndex, fileIndex) {
            this.items[itemIndex].files.splice(fileIndex, 1);
        }
    }));
});