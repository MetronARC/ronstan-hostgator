<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?= base_url(); ?>img/Logo.png" />
    <!-- Material Icon -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <!-- Font-Awesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.lineicons.com/4.0/lineicons.css" />
    <!-- Stylesheet -->
    <link rel="stylesheet" href="<?= base_url(); ?>css/style.css">
    <script type="text/javascript" src="<?= base_url(); ?>jquery/jquery.min.js"></script>
    <title><?= $title ?></title>
</head>

<body>
    <div class="container" data-aos="zoom-out">
        <!-- SIDEBAR -->
        <?= $this->include('template/sidebar') ?>
        <!-- END OF SIDEBAR -->


        <main>

            <?= $this->renderSection('page-content') ?>

        </main>

        <?= $this->include('template/right') ?>
    </div>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="<?= base_url(); ?>js/index.js"></script>
    <script src="<?= base_url(); ?>js/sidebar.js"></script>
</body>

</html>