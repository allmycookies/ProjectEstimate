<?php
// modules/clients/index.php
require_once 'includes/db.php';

// Kunden abrufen
$result = $conn->query("SELECT *, (SELECT COUNT(*) FROM projects WHERE client_id = clients.id) as project_count FROM clients ORDER BY company_name ASC");
?>

<div class="container-fluid" x-data="clientManager()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-people"></i> Kundenstamm</h2>
        <button class="btn btn-primary" @click="openModal()">
            <i class="bi bi-plus-lg"></i> Neuer Kunde
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Firma</th>
                        <th>Ansprechpartner</th>
                        <th>E-Mail</th>
                        <th>Projekte</th>
                        <th class="text-end pe-4">Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?= htmlspecialchars($row['company_name']) ?></td>
                        <td><?= htmlspecialchars($row['contact_person']) ?></td>
                        <td><a href="mailto:<?= $row['email'] ?>"><?= $row['email'] ?></a></td>
                        <td><span class="badge bg-secondary rounded-pill"><?= $row['project_count'] ?></span></td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-secondary" 
                                    @click='editClient(<?= json_encode($row) ?>)'>
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="clientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="mode === 'add' ? 'Neuer Kunde' : 'Kunde bearbeiten'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Firmenname</label>
                        <input type="text" class="form-control" x-model="form.company_name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ansprechpartner</label>
                        <input type="text" class="form-control" x-model="form.contact_person">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-Mail Adresse</label>
                        <input type="email" class="form-control" x-model="form.email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse (für PDF)</label>
                        <textarea class="form-control" rows="3" x-model="form.address"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn btn-primary" @click="saveClient()">Speichern</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function clientManager() {
    return {
        mode: 'add',
        form: { id: null, company_name: '', contact_person: '', email: '', address: '' },
        modal: null,

        init() {
            this.modal = new bootstrap.Modal(document.getElementById('clientModal'));
        },
        openModal() {
            this.mode = 'add';
            this.form = { id: null, company_name: '', contact_person: '', email: '', address: '' };
            this.modal.show();
        },
        editClient(data) {
            this.mode = 'edit';
            this.form = { ...data }; // Klonen
            this.modal.show();
        },
        saveClient() {
            if(!this.form.company_name) return alert('Bitte Firmennamen angeben');

            fetch('modules/clients/save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.form)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert('Fehler: ' + data.message);
                }
            });
        }
    }
}
</script>