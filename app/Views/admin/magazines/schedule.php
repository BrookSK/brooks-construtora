<?php $pageTitle = 'Agendamento de Revistas'; $currentPage = 'schedule'; ob_start(); ?>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Configurar Frequência de Geração</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="/admin/magazines/schedule/update">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Frequência</label>
                    <select class="form-select" name="magazine_frequency" id="frequency">
                        <option value="diario" <?= $settings['magazine_frequency'] === 'diario' ? 'selected' : '' ?>>Diário</option>
                        <option value="semanal" <?= $settings['magazine_frequency'] === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                        <option value="quinzenal" <?= $settings['magazine_frequency'] === 'quinzenal' ? 'selected' : '' ?>>Quinzenal</option>
                        <option value="mensal" <?= $settings['magazine_frequency'] === 'mensal' ? 'selected' : '' ?>>Mensal</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Vezes por período</label>
                    <input type="number" class="form-control" name="magazine_times_per_period" value="<?= htmlspecialchars($settings['magazine_times_per_period']) ?>" min="1" max="10">
                    <small class="text-muted">Ex: 2 vezes por semana, 1 vez por mês, etc.</small>
                </div>
                <div class="col-md-6" id="day-of-week-group">
                    <label class="form-label">Dia da semana</label>
                    <select class="form-select" name="magazine_day_of_week">
                        <option value="1" <?= $settings['magazine_day_of_week'] === '1' ? 'selected' : '' ?>>Segunda-feira</option>
                        <option value="2" <?= $settings['magazine_day_of_week'] === '2' ? 'selected' : '' ?>>Terça-feira</option>
                        <option value="3" <?= $settings['magazine_day_of_week'] === '3' ? 'selected' : '' ?>>Quarta-feira</option>
                        <option value="4" <?= $settings['magazine_day_of_week'] === '4' ? 'selected' : '' ?>>Quinta-feira</option>
                        <option value="5" <?= $settings['magazine_day_of_week'] === '5' ? 'selected' : '' ?>>Sexta-feira</option>
                        <option value="6" <?= $settings['magazine_day_of_week'] === '6' ? 'selected' : '' ?>>Sábado</option>
                        <option value="0" <?= $settings['magazine_day_of_week'] === '0' ? 'selected' : '' ?>>Domingo</option>
                    </select>
                </div>
                <div class="col-md-6" id="day-of-month-group">
                    <label class="form-label">Dia do mês</label>
                    <input type="number" class="form-control" name="magazine_day_of_month" value="<?= htmlspecialchars($settings['magazine_day_of_month']) ?>" min="1" max="28">
                </div>
            </div>

            <div class="alert alert-info mt-4">
                <i class="bi bi-info-circle"></i>
                <strong>Como funciona:</strong> Configure uma cron job no servidor para executar <code>php artisan magazine:generate</code> diariamente.
                O sistema verificará automaticamente se é hora de gerar uma nova revista baseado nestas configurações.
            </div>

            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Salvar Agendamento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('frequency').addEventListener('change', function() {
    const val = this.value;
    document.getElementById('day-of-week-group').style.display = (val === 'semanal' || val === 'quinzenal') ? 'block' : 'none';
    document.getElementById('day-of-month-group').style.display = val === 'mensal' ? 'block' : 'none';
});
document.getElementById('frequency').dispatchEvent(new Event('change'));
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
