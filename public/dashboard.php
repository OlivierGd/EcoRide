<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'compteur.php';
$annee = (int)date('Y');
$annee_selection = empty($_GET['annee']) ? null : (int)$_GET['annee'];
$mois_selection = empty($_GET['mois']) ? null : $_GET['mois'];
if ($annee_selection !== null && $mois_selection !== null) {
    $total = nombre_vue_mois($annee_selection, $mois_selection);
} else {
    $total = recuperer_vue();
}

$mois = [
        '01' => 'Janvier',
        '02' => 'Février',
        '03' => 'Mars',
        '04' => 'Avril',
        '05' => 'Mai',
        '06' => 'Juin',
        '07' => 'Juillet',
        '08' => 'Août',
        '09' => 'Septembre',
        '10' => 'Octobre',
        '11' => 'Novembre',
        '12' => 'Décembre'
];
require 'header.php';
?>
<h1>Dashboard</h1>
<div class="py-5"></div>
<div class="row">
    <div class="col-md-4">
        <div class="list-group">
            <?php for ($i = 0; $i < 5; $i++) : ?>
            <a class="list-group-item <?= $annee - $i === $annee_selection ? 'active' : ''; ?>" href="dashboard.php?annee=<?= $annee - $i ?>"><?= $annee - $i ?></a>
            <?php if ($annee - $i === $annee_selection) : ?>
                <div class="list-group">
                    <?php foreach ($mois as $mois_key => $mois_value) : ?>
                        <a class="list-group-item <?= $mois_key === $mois_selection ? 'active' : ''; ?> " href="dashboard.php?annee=<?= $annee_selection ?>&mois=<?= $mois_key ?>"><?= $mois_value ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <?php
                require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'compteur.php';
                ajouter_vue();
                $vues = recuperer_vue();
                ?>
                Il y a <?= $vues ?> visite<?= $vues > 1 ? 's' : '' ?> de la page dashboard.
            </div>
        </div>
    </div>
</div>

<footer>
    <?php require 'footer.php'; ?>
</footer>
