<?php
if (!isset($filterId) || !isset($namePrefix)) {
    throw new Exception("Paramètres manquants : filterId ou namePrefix");
}
$preset = $_GET[$namePrefix . '_preset'] ?? '';
$start  = $_GET[$namePrefix . '_start'] ?? '';
$end    = $_GET[$namePrefix . '_end'] ?? '';
?>

<form method="get" id="<?= $filterId ?>" class="d-flex flex-wrap align-items-center gap-2">
    <!-- Presets -->
    <select name="<?= $namePrefix ?>_preset" class="form-select form-select-sm w-auto">
        <option value="">Périodes</option>
        <option value="today"      <?= $preset==='today' ? 'selected' : '' ?>>Aujourd’hui</option>
        <option value="tomorrow"   <?= $preset==='tomorrow' ? 'selected' : '' ?>>Demain</option>
        <option value="this_week"  <?= $preset==='this_week' ? 'selected' : '' ?>>Cette semaine</option>
        <option value="last_week"  <?= $preset==='last_week' ? 'selected' : '' ?>>Semaine dernière</option>
        <option value="this_month" <?= $preset==='this_month' ? 'selected' : '' ?>>Ce mois</option>
        <option value="last_month" <?= $preset==='last_month' ? 'selected' : '' ?>>Mois dernier</option>
    </select>

    <span class="text-muted">ou</span>

    <!-- Plage personnalisée -->
    <div class="input-group input-group-sm w-auto">
        <span class="input-group-text">Du</span>
        <input type="date" name="<?= $namePrefix ?>_start" class="form-control" value="<?= htmlspecialchars($start) ?>">
        <span class="input-group-text">au</span>
        <input type="date" name="<?= $namePrefix ?>_end" class="form-control" value="<?= htmlspecialchars($end) ?>">
    </div>

    <button class="btn btn-sm btn-primary">Filtrer</button>
    <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>" class="btn btn-sm btn-outline-secondary">Réinitialiser</a>
</form>
