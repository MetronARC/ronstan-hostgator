<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?= base_url(); ?>/img/Logo.png" />
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
    <style>
        /* Additional styles for the dropdown */
        .machine-input,
        .date-input {
            border: 1px solid #ccc;
            padding: 10px;
            /* Increased padding for more height */
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
            /* Ensures padding is included in width */
        }

        /* Button styling */
        #fetch-data {
            display: inline-block;
            padding: 10px 20px;
            color: white;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 25px;
            text-align: center;
        }

        #fetch-data p {
            margin: 0;
            /* Remove default margin from p tag */
            color: white;
            /* Set text color to white */
            font-size: 18px;
            /* Increase font size */
        }

        /* Chart Container and Buttons styling */
        #chart-container {
            position: relative;
            /* Make the container a positioned element */
        }

        #reset-zoom,
        #move-left,
        #move-right {
            position: absolute;
            top: 10px;
            /* Adjust as needed */
            z-index: 10;
            /* Ensure it appears above other elements */
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        #reset-zoom {
            position: absolute;
            top: 0px;
            /* Adjust as needed */
            right: 10px;
            /* Adjust to position it correctly */
        }

        #move-left {
            position: absolute;
            top: 0px;
            /* Adjust as needed */
            right: 205px;
            /* Adjust to position it correctly */
        }

        #move-right {
            position: absolute;
            top: 0px;
            /* Adjust as needed */
            right: 110px;
            /* Adjust to position it correctly */
        }
    </style>
</head>

<body>
    <div class="container" data-aos="zoom-out">
        <!-- SIDEBAR -->
        <?= $this->include('template/sidebar') ?>
        <!-- END OF SIDEBAR -->

        <?= $this->renderSection('page-content') ?>
    </div>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="<?= base_url(); ?>js/index.js"></script>
    <script src="<?= base_url(); ?>js/sidebar.js"></script>
</body>

</html>