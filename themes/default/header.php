<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Helpers de SEO e Google Analytics -->
    <?php auto_seo($content ?? null); ?>
    <?php ga_analytics(); ?>

    <!-- Estilos globais básicos -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fcfcfc;
        }
        header {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            flex-wrap: wrap;
        }
        header h1 {
            margin: 0;
            color: #222;
        }
        nav a {
            margin-left: 15px;
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        nav a:hover {
            text-decoration: underline;
        }
        img, video, iframe {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        pre {
            background: #282c34;
            color: #abb2bf;
            padding: 15px;
            overflow-x: auto;
            border-radius: 8px;
            font-size: 0.9em;
        }
        blockquote {
            border-left: 4px solid #007bff;
            padding-left: 15px;
            margin-left: 0;
            color: #555;
            background: #f4f6f8;
            padding: 10px 15px;
            border-radius: 0 8px 8px 0;
        }
        .content-body {
            margin-top: 30px;
        }
        .meta-info {
            color: #777;
            font-size: 0.9em;
        }
        .tag {
            display: inline-block;
            background: #eef;
            color: #007bff;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
    </style>
</head>
<body>
    <header>
        <h1><a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?: '/' ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars(config('siteTitle') ?: 'Typper Site') ?></a></h1>
        <nav>
            <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?: '/' ?>">Início</a>
            <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?: '/' ?>/panel">Painel Admin</a>
        </nav>
    </header>