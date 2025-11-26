// modules/projects/editor.php
<?php
// modules/projects/editor.php
// Prüfen ob wir editieren oder neu anlegen
$project_id = $_GET['id'] ?? null;
$project_data = []; // Hier müssten wir Daten laden, falls ID existiert (für V1 erstelle ich leere Struktur)

// Dummy Clients für V1 (Später aus DB laden)
$clients = [
    ['id' => 1, 'name' => 'Musterfirma GmbH'],
    ['id' => 2, 'name' => 'StartUp Berlin AG']
];

// Lade Gemini Key aus der Datenbank
global $conn;
$gemini_key_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'gemini_key'");
$gemini_key = $gemini_key_result->fetch_assoc()['setting_value'] ?? '';
?>

<script>
    const SERVER_GEMINI_KEY = <?= json_encode($gemini_key) ?>;
</script>

<div class="container-fluid" x-data="projectEditor()">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 m-0"><i class="bi bi-file-earmark-spreadsheet"></i> Planung erstellen</h2>
        <div>
            <span x-show="isSaving" class="text-muted me-2"><span class="spinner-border spinner-border-sm"></span> Speichere...</span>
            <button class="btn btn-outline-dark me-2" @click="previewPDF()" x-show="project.id">
                <i class="bi bi-eye"></i> PDF Vorschau
            </button>
            <button class="btn btn-outline-secondary me-2" @click="saveProject('draft')">Entwurf speichern</button>
            <button class="btn btn-primary" @click="saveProject('sent')"><i class="bi bi-send"></i> An Kunden senden</button>
        </div>
    </div>

    <div class="card shadow-sm mb-5">
        <div class="card-body p-4">
            
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label text-muted small text-uppercase">Kunde</label>
                    <select class="form-select" x-model="project.client_id">
                        <option value="">-- Kunde wählen --</option>
                        <?php foreach($clients as $client): ?>
                            <option value="<?= $client['id'] ?>"><?= $client['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label text-muted small text-uppercase">Projekt Titel</label>
                    <input type="text" class="form-control fw-bold" x-model="project.title" placeholder="z.B. Redesign Website 2024">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small text-uppercase">Puffer Faktor</label>
                    <div class="input-group">
                        <span class="input-group-text">x</span>
                        <input type="number" step="0.1" class="form-control" x-model="project.risk_factor" placeholder="1.0">
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <table class="table table-borderless align-middle">
                <thead class="text-muted small text-uppercase border-bottom">
                    <tr>
                        <th style="width: 5%">#</th>
                        <th style="width: 30%">Aufgabe (Prompt)</th>
                        <th style="width: 40%">Beschreibung & Begründung (KI)</th>
                        <th style="width: 10%">Std.</th>
                        <th style="width: 5%">Magic</th>
                        <th style="width: 5%"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, index) in items" :key="index">
                        <tr class="border-bottom hover-bg">
                            <td class="text-center text-muted" x-text="index + 1"></td>
                            
                            <td class="align-top pt-3">
                                <input type="text" class="form-control fw-bold mb-2" 
                                       x-model="item.title" 
                                       placeholder="Was ist zu tun?"
                                       @keydown.enter.prevent="askGemini(index)">
                                <div class="text-muted small"><i class="bi bi-paperclip"></i> Keine Anhänge</div>
                            </td>

                            <td class="align-top pt-3">
                                <div class="dropzone-area p-2 rounded"
                                     :class="{'bg-primary bg-opacity-10 border border-primary border-dashed': item.isDragging}"
                                     @dragover.prevent="item.isDragging = true"
                                     @dragleave.prevent="item.isDragging = false"
                                     @drop.prevent="handleDrop($event, index)">
                                    
                                    <input type="text" class="form-control fw-bold mb-2" 
                                           x-model="item.title" 
                                           placeholder="Was ist zu tun?"
                                           @keydown.enter.prevent="askGemini(index)">
                                    
                                    <div class="mt-1">
                                        <template x-for="(file, fIndex) in item.files" :key="fIndex">
                                            <div class="d-flex align-items-center justify-content-between bg-white border rounded px-2 py-1 mb-1 small shadow-sm">
                                                <span class="text-truncate" style="max-width: 150px;">
                                                    <i class="bi bi-file-earmark"></i> <span x-text="file.original_name"></span>
                                                </span>
                                                <button class="btn btn-link text-danger p-0 ms-2" @click="removeFile(index, fIndex)">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                        </template>
                                        
                                        <label class="btn btn-link btn-sm p-0 text-decoration-none text-muted small cursor-pointer">
                                            <i class="bi bi-paperclip"></i> Datei hinzufügen
                                            <input type="file" class="d-none" @change="handleFileSelect($event, index)">
                                        </label>
                                         <span x-show="item.uploading" class="spinner-border spinner-border-sm text-muted ms-2"></span>
                                    </div>
                                </div>
                            </td>

                            <td class="align-top pt-3">
                                <input type="number" class="form-control text-end" x-model="item.hours" step="0.5">
                            </td>

                            <td class="align-top pt-3 text-center">
                                <button class="btn btn-sm btn-outline-primary border-0" 
                                        @click="askGemini(index)" 
                                        title="KI Schätzung starten">
                                    <i class="bi bi-stars"></i>
                                </button>
                            </td>

                            <td class="align-top pt-3 text-end">
                                <button class="btn btn-link text-danger p-0" @click="removeItem(index)">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div class="row mt-4">
                <div class="col-md-6">
                    <button class="btn btn-outline-dark btn-sm" @click="addItem()">
                        <i class="bi bi-plus-lg"></i> Zeile hinzufügen
                    </button>
                </div>
                <div class="col-md-6 text-end">
                    <div class="bg-light p-3 rounded d-inline-block text-start" style="min-width: 250px;">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Summe Stunden:</span>
                            <strong x-text="calculateTotalHours()"></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1 text-muted small">
                            <span>Puffer (<span x-text="((project.risk_factor - 1) * 100).toFixed(0) + '%'"></span>):</span>
                            <span x-text="calculateBufferHours()"></span>
                        </div>
                        <div class="border-top my-2"></div>
                        <div class="d-flex justify-content-between fs-5 text-primary">
                            <strong>Gesamt:</strong>
                            <strong x-text="calculateGrandTotal() + ' h'"></strong>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>